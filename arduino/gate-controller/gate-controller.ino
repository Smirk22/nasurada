/*
 * IETI SEVS gate controller — Arduino UNO R4 WiFi
 *
 * Flow per tap:
 *   1. Read the MFRC522 UID.
 *   2. POST it to Laravel /api/rfid-scan (Wi-Fi first, W5500 Ethernet fallback).
 *   3. Laravel decides if the card is valid and returns "beep" + optional "sms".
 *   4. Buzzer sounds ONLY when Laravel says beep = true (invalid scan).
 *   5. If an SMS payload is returned, the A7670C modem texts the student.
 *
 * No ArduinoJson dependency: the small response is parsed with helpers below.
 */

#include <SPI.h>
#include <Ethernet.h>
#include <MFRC522.h>
#include <WiFiS3.h>

// ---------------------------------------------------------------------------
// EDIT THESE TO MATCH YOUR SETUP
// ---------------------------------------------------------------------------
const char *WIFI_SSID = "YOUR_WIFI_SSID";
const char *WIFI_PASSWORD = "YOUR_WIFI_PASSWORD";

// Hostname or IP of the PC running Laravel (same as APP_URL in .env).
const char *SERVER_HOST = "192.168.100.13";
const uint16_t SERVER_PORT = 8000;
const char *SERVER_PATH = "/api/rfid-scan";

constexpr unsigned long GSM_BAUD = 115200;

// Pins
constexpr uint8_t ETH_CS_PIN = 10;
constexpr uint8_t ETH_RST_PIN = 8;
constexpr uint8_t RFID_SS_PIN = 9;
constexpr uint8_t RFID_RST_PIN = 7;
constexpr uint8_t BUZZER_PIN = 6;
// Set to false if your buzzer module triggers on a LOW input.
constexpr bool BUZZER_ACTIVE_HIGH = true;

// Ethernet fallback identity (use a free IP on your LAN)
byte ethMac[] = { 0xDE, 0xAD, 0xBE, 0xEF, 0xFE, 0xED };
IPAddress ethFixedIp(192, 168, 100, 177);

constexpr unsigned long WIFI_RETRY_MS = 30000;
constexpr unsigned long HTTP_TIMEOUT_MS = 8000;

MFRC522 rfid(RFID_SS_PIN, RFID_RST_PIN);

enum class NetMode { None, Wifi, Ethernet };

unsigned long wifiRetryAt = 0;
bool gsmReady = false;

// ---------------------------------------------------------------------------
// Logging
// ---------------------------------------------------------------------------
void logStep(const char *msg)
{
  Serial.println(msg);
  Serial.flush();
}

void logValue(const char *label, const String &value)
{
  Serial.print(label);
  Serial.println(value);
  Serial.flush();
}

// Never leave two SPI devices selected at the same time.
void setSpiChipSelectsIdle()
{
  pinMode(ETH_CS_PIN, OUTPUT);
  pinMode(RFID_SS_PIN, OUTPUT);
  digitalWrite(ETH_CS_PIN, HIGH);
  digitalWrite(RFID_SS_PIN, HIGH);
}

// ---------------------------------------------------------------------------
// Tiny JSON helpers (enough for the Laravel response, no library needed)
// ---------------------------------------------------------------------------
bool jsonBool(const String &json, const char *key, bool &out)
{
  const String needle = String("\"") + key + "\"";
  int pos = json.indexOf(needle);

  if (pos < 0) {
    return false;
  }

  pos = json.indexOf(':', pos + needle.length());
  if (pos < 0) {
    return false;
  }

  pos++;
  while (pos < json.length() && json[pos] == ' ') {
    pos++;
  }

  if (json.substring(pos, pos + 4) == "true") {
    out = true;
    return true;
  }

  if (json.substring(pos, pos + 5) == "false") {
    out = false;
    return true;
  }

  return false;
}

bool jsonString(const String &json, const char *key, String &out)
{
  const String needle = String("\"") + key + "\"";
  int pos = json.indexOf(needle);

  if (pos < 0) {
    return false;
  }

  pos = json.indexOf(':', pos + needle.length());
  if (pos < 0) {
    return false;
  }

  pos++;
  while (pos < json.length() && json[pos] == ' ') {
    pos++;
  }

  if (pos >= json.length() || json[pos] != '"') {
    return false;
  }

  pos++;
  out = "";

  while (pos < json.length()) {
    char c = json[pos++];

    if (c == '"') {
      return true;
    }

    if (c == '\\' && pos < json.length()) {
      const char escaped = json[pos++];

      switch (escaped) {
        case 'n': c = '\n'; break;
        case 'r': c = '\r'; break;
        case 't': c = '\t'; break;
        case '"': c = '"'; break;
        case '\\': c = '\\'; break;
        default: c = escaped; break;
      }
    }

    out += c;
  }

  return false;
}

bool jsonObjectExists(const String &json, const char *key)
{
  const String needle = String("\"") + key + "\"";
  int pos = json.indexOf(needle);

  if (pos < 0) {
    return false;
  }

  pos = json.indexOf(':', pos + needle.length());
  if (pos < 0) {
    return false;
  }

  pos++;
  while (pos < json.length() && json[pos] == ' ') {
    pos++;
  }

  return pos < json.length() && json[pos] == '{';
}

// ---------------------------------------------------------------------------
// Buzzer — only called for scans Laravel marked invalid
// ---------------------------------------------------------------------------
void setBuzzer(bool on)
{
  digitalWrite(BUZZER_PIN, BUZZER_ACTIVE_HIGH ? on : !on);
}

void beepInvalidScan()
{
  for (int i = 0; i < 2; i++) {
    setBuzzer(true);
    delay(120);
    setBuzzer(false);
    delay(100);
  }
}

// ---------------------------------------------------------------------------
// Network: Wi-Fi first, W5500 Ethernet as the fallback
// ---------------------------------------------------------------------------
bool ensureWifi()
{
  if (WiFi.status() == WL_CONNECTED) {
    return true;
  }

  if (millis() < wifiRetryAt) {
    return false;
  }

  logStep("[NET] Connecting to Wi-Fi...");
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  const unsigned long start = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - start < 10000) {
    delay(250);
  }

  if (WiFi.status() == WL_CONNECTED) {
    logValue("[NET] Wi-Fi connected:", WiFi.localIP().toString());
    wifiRetryAt = 0;
    return true;
  }

  logStep("[NET] Wi-Fi unavailable; will retry later.");
  wifiRetryAt = millis() + WIFI_RETRY_MS;
  return false;
}

bool ensureEthernet()
{
  if (Ethernet.hardwareStatus() == EthernetNoHardware) {
    return false;
  }

  if (Ethernet.linkStatus() == LinkOFF) {
    return false;
  }

  return true;
}

bool ensureNetwork(NetMode &mode)
{
  if (ensureWifi()) {
    mode = NetMode::Wifi;
    return true;
  }

  if (ensureEthernet()) {
    mode = NetMode::Ethernet;
    return true;
  }

  mode = NetMode::None;
  return false;
}

// ---------------------------------------------------------------------------
// HTTP POST through either WiFiClient or EthernetClient (both are Client)
// ---------------------------------------------------------------------------
bool postScan(NetMode mode, const String &body, String &responseBody, int &httpStatus)
{
  WiFiClient wifiClient;
  EthernetClient ethClient;
  Client *client = (mode == NetMode::Wifi) ? static_cast<Client *>(&wifiClient)
                                          : static_cast<Client *>(&ethClient);

  setSpiChipSelectsIdle();

  if (!client->connect(SERVER_HOST, SERVER_PORT)) {
    client->stop();
    return false;
  }

  client->print(F("POST "));
  client->print(SERVER_PATH);
  client->println(F(" HTTP/1.1"));
  client->print(F("Host: "));
  client->print(SERVER_HOST);
  client->print(':');
  client->println(SERVER_PORT);
  client->println(F("Content-Type: application/json"));
  client->println(F("Connection: close"));
  client->print(F("Content-Length: "));
  client->println(body.length());
  client->println();
  client->print(body);

  String raw;
  raw.reserve(1024);
  const unsigned long start = millis();

  while (millis() - start < HTTP_TIMEOUT_MS) {
    while (client->available() > 0) {
      raw += static_cast<char>(client->read());

      if (raw.length() >= 4096) {
        break;
      }
    }

    const int headerEnd = raw.indexOf("\r\n\r\n");
    if (headerEnd >= 0) {
      const int lengthPos = raw.indexOf("Content-Length:");
      const int lineEnd = lengthPos >= 0 ? raw.indexOf("\r\n", lengthPos) : -1;
      if (lineEnd > lengthPos) {
        const int contentLength = raw.substring(lengthPos + 15, lineEnd).toInt();
        if (contentLength > 0 && raw.length() >= headerEnd + 4 + contentLength) {
          break;
        }
      }
    }

    if (!client->connected() && client->available() == 0) {
      break;
    }

    delay(2);
  }

  client->stop();
  setSpiChipSelectsIdle();

  if (raw.length() == 0) {
    return false;
  }

  const int firstSpace = raw.indexOf(' ');
  if (firstSpace < 0) {
    return false;
  }

  httpStatus = raw.substring(firstSpace + 1).toInt();
  if (httpStatus <= 0) {
    return false;
  }

  const int bodyStart = raw.indexOf("\r\n\r\n");
  responseBody = bodyStart >= 0 ? raw.substring(bodyStart + 4) : raw;

  return true;
}

// POST once, retrying over Ethernet if Wi-Fi fails mid-request.
bool postScanWithFallback(const String &body, String &responseBody, int &httpStatus)
{
  NetMode mode = NetMode::None;

  if (!ensureNetwork(mode)) {
    logStep("[NET] No Wi-Fi or Ethernet link; scan not sent.");
    return false;
  }

  logStep(mode == NetMode::Wifi ? "[NET] Posting scan over Wi-Fi..." : "[NET] Posting scan over Ethernet...");

  if (postScan(mode, body, responseBody, httpStatus)) {
    return true;
  }

  if (mode != NetMode::Wifi || !ensureEthernet()) {
    return false;
  }

  logStep("[NET] Wi-Fi request failed; retrying over Ethernet...");
  wifiRetryAt = millis() + WIFI_RETRY_MS;

  return postScan(NetMode::Ethernet, body, responseBody, httpStatus);
}

// ---------------------------------------------------------------------------
// A7670C GSM modem (Serial1 on pins D0/D1)
// ---------------------------------------------------------------------------
void gsmDrain()
{
  const unsigned long start = millis();
  while (Serial1.available() > 0 && millis() - start < 30) {
    Serial1.read();
    delay(1);
  }
}

bool gsmWaitFor(const char *expected, unsigned long timeout, String *reply = nullptr)
{
  String buffer;
  buffer.reserve(256);
  const unsigned long start = millis();

  while (millis() - start < timeout) {
    while (Serial1.available() > 0) {
      buffer += static_cast<char>(Serial1.read());
    }

    if (expected != nullptr && buffer.indexOf(expected) >= 0) {
      if (reply != nullptr) {
        *reply = buffer;
      }
      return true;
    }

    if (buffer.indexOf("ERROR") >= 0) {
      if (reply != nullptr) {
        *reply = buffer;
      }
      return false;
    }

    delay(10);
  }

  if (reply != nullptr) {
    *reply = buffer;
  }

  return false;
}

bool gsmCommand(const char *command, const char *expected, unsigned long timeout, String *reply = nullptr)
{
  gsmDrain();
  Serial1.println(command);
  return gsmWaitFor(expected, timeout, reply);
}

void gsmInit()
{
  Serial1.begin(GSM_BAUD);

  if (!gsmCommand("AT", "OK", 3000)) {
    logStep("[GSM] Modem did not answer AT. Check baud/wiring/power.");
    return;
  }

  gsmCommand("ATE0", "OK", 2000);
  gsmCommand("AT+CMGF=1", "OK", 2000);                 // SMS text mode
  gsmCommand("AT+CSCS=\"GSM\"", "OK", 2000);           // GSM 7-bit charset

  bool simReady = false;
  for (int attempt = 0; attempt < 10; attempt++) {
    String reply;
    if (gsmCommand("AT+CPIN?", "READY", 2000, &reply)) {
      simReady = true;
      break;
    }
    delay(1000);
  }

  if (!simReady) {
    logStep("[GSM] SIM not ready; SMS will be skipped.");
    return;
  }

  for (int attempt = 0; attempt < 20; attempt++) {
    String reply;
    if (gsmCommand("AT+CEREG?", "OK", 2000, &reply)
        && (reply.indexOf(",1") >= 0 || reply.indexOf(",5") >= 0)) {
      gsmReady = true;
      logStep("[GSM] Registered on the network; SMS ready.");
      return;
    }
    delay(1000);
  }

  logStep("[GSM] Not registered on the network yet; SMS will be skipped.");
}

bool gsmSendSms(const String &phone, const String &message)
{
  if (!gsmReady) {
    logStep("[GSM] Skipping SMS: modem/network not ready.");
    return false;
  }

  const String command = String("AT+CMGS=\"") + phone + "\"";
  if (!gsmCommand(command.c_str(), ">", 5000)) {
    logStep("[GSM] Could not start SMS (no '>' prompt).");
    return false;
  }

  Serial1.print(message);
  Serial1.write(static_cast<uint8_t>(0x1A)); // Ctrl+Z sends the message

  String reply;
  if (!gsmWaitFor("+CMGS:", 25000, &reply)) {
    logValue("[GSM] SMS failed:", reply);
    return false;
  }

  logValue("[GSM] SMS sent to", phone);
  return true;
}

// ---------------------------------------------------------------------------
// Scan handling
// ---------------------------------------------------------------------------
String buildUid(const MFRC522::Uid &uid)
{
  String result;
  result.reserve(uid.size * 2);

  for (byte i = 0; i < uid.size; i++) {
    if (uid.uidByte[i] < 0x10) {
      result += '0';
    }
    result += String(uid.uidByte[i], HEX);
  }

  result.toUpperCase();
  return result;
}

void processScan(const String &uid)
{
  logValue("[SCAN] UID:", uid);

  // scan_id makes an Ethernet retry of a dropped Wi-Fi request idempotent.
  // sms_channel=gsm tells Laravel to return the SMS text instead of calling
  // its own UniSMS API, so the physical modem is the one that sends it.
  const String scanId = String(millis()) + "-" + String(random(0, 0xFFFFFF), HEX);
  const String body = String("{\"uid\":\"") + uid
                      + "\",\"sms_channel\":\"gsm\",\"scan_id\":\"" + scanId + "\"}";

  String responseBody;
  int httpStatus = 0;

  if (!postScanWithFallback(body, responseBody, httpStatus)) {
    // A network failure is not an invalid card — stay silent as requested.
    logStep("[SCAN] No Laravel response; buzzer stays silent.");
    return;
  }

  String status;
  jsonString(responseBody, "status", status);
  logValue("[SCAN] Laravel status:", status + " (HTTP " + String(httpStatus) + ")");

  bool beep = false;
  if (!jsonBool(responseBody, "beep", beep)) {
    // Backwards compatibility with an older Laravel build: only beep on 4xx.
    beep = httpStatus >= 400 && httpStatus < 500;
  }

  if (beep) {
    String message;
    if (jsonString(responseBody, "message", message)) {
      logValue("[SCAN] Rejected:", message);
    }
    beepInvalidScan();
  } else {
    String student;
    if (jsonString(responseBody, "student", student)) {
      logValue("[SCAN] Accepted:", student);
    }
  }

  // Laravel only includes "sms" for a valid time-in with sms_channel=gsm.
  if (jsonObjectExists(responseBody, "sms")) {
    String phone;
    String message;
    if (jsonString(responseBody, "phone", phone) && jsonString(responseBody, "message", message)) {
      gsmSendSms(phone, message);
    }
  }
}

// ---------------------------------------------------------------------------
// Setup / loop
// ---------------------------------------------------------------------------
void setup()
{
  Serial.begin(115200);
  while (!Serial && millis() < 3000) {
    // Wait briefly for the USB monitor; do not block a headless gate forever.
  }

  pinMode(BUZZER_PIN, OUTPUT);
  setBuzzer(false);
  pinMode(ETH_RST_PIN, OUTPUT);
  digitalWrite(ETH_RST_PIN, HIGH);

  logStep("[BOOT] Starting setup...");
  SPI.begin();
  setSpiChipSelectsIdle();

  logStep("[INIT] RC522...");
  rfid.PCD_Init();
  delay(50);
  const byte version = rfid.PCD_ReadRegister(rfid.VersionReg);
  Serial.print(F("Reader version: 0x"));
  Serial.println(version, HEX);
  Serial.flush();

  logStep("[INIT] W5500 fallback...");
  Ethernet.init(ETH_CS_PIN);
  if (Ethernet.begin(ethMac) == 0) {
    logStep("[INIT] DHCP failed; using fixed Ethernet IP");
    Ethernet.begin(ethMac, ethFixedIp);
  }
  delay(100);
  if (Ethernet.hardwareStatus() != EthernetNoHardware) {
    logValue("[INIT] Ethernet IP:", Ethernet.localIP().toString());
    logStep(Ethernet.linkStatus() == LinkON ? "[INIT] Ethernet cable connected."
                                            : "[INIT] Ethernet cable not connected.");
  } else {
    logStep("[INIT] No W5500 found; Wi-Fi will be the only network.");
  }

  setSpiChipSelectsIdle();
  ensureWifi();
  gsmInit();
  setSpiChipSelectsIdle();

  logStep("[INIT] Done. Waiting for RFID taps...");
}

void loop()
{
  setSpiChipSelectsIdle();

  if (rfid.PICC_IsNewCardPresent() && rfid.PICC_ReadCardSerial()) {
    const String uid = buildUid(rfid.uid);
    processScan(uid);

    rfid.PICC_HaltA();
    rfid.PCD_StopCrypto1();
    setSpiChipSelectsIdle();
  }

  delay(100);
}
