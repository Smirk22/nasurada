# IETI SEVS gate controller (UNO R4 WiFi)

Firmware for the RFID gate: MFRC522 reader + buzzer + A7670C GSM modem + W5500 Ethernet, talking to the Laravel app at `POST /api/rfid-scan`.

## What happens on every tap

1. The RC522 UID is read and posted to Laravel as JSON.
2. Laravel checks enrollment, validity, and the entry log, then answers with a `beep` flag and (for a valid **time-in**) an SMS payload.
3. The buzzer sounds **only** when `beep` is `true` (rejected/unknown card). Valid entries, time-outs, registration scans, and network failures are silent.
4. If the answer contains `sms`, the A7670C sends that exact text to the student's phone number. Laravel does **not** call the UniSMS API for these device scans.
5. Wi-Fi is tried first. If it is down or the POST fails, the same request is retried over the W5500 using the same `scan_id`, so Laravel replays the original decision instead of toggling the entry twice. Wi-Fi is retried again after 30 seconds.

Laravel request/response example:

```http
POST /api/rfid-scan HTTP/1.1
Content-Type: application/json

{"uid":"A1B2C3D4","sms_channel":"gsm","scan_id":"12345-a1b2c3"}
```

```json
{
  "status": "success",
  "action": "time_in",
  "student": "Ana Reyes",
  "beep": false,
  "sms": {
    "student_id": 12,
    "phone": "+639171234567",
    "message": "Hi Ana, your RFID was scanned at 8:15 AM on November 27, 2026. Entry granted."
  }
}
```

Rejected scans answer `4xx` with `"beep": true` and no `sms`, so the modem never texts anyone for an invalid card.

## Wiring

| Module | Module pin | UNO R4 WiFi pin |
|---|---|---|
| **MFRC522** | VCC | 3.3 V (never 5 V) |
| | GND | GND |
| | SDA / SS | D9 |
| | RST | D7 |
| | MOSI | D11 |
| | MISO | D12 |
| | SCK | D13 |
| **W5500** | VCC / GND | 5 V / GND (follow your module) |
| | CS / SS | D10 |
| | RST | D8 |
| | MOSI / MISO / SCK | D11 / D12 / D13 |
| **Buzzer driver** | IN | D6 |
| | VCC / GND | As required by the module |
| **A7670C** | TX | Level shifter → **D0 (Serial1 RX)** |
| | RX | Level shifter ← **D1 (Serial1 TX)** |
| | VCC | External 3.8–4.2 V supply, **≥ 2 A peak** |
| | GND | Same ground as the Arduino |

Notes:

- On the UNO R4 WiFi, USB debug is `Serial`, GSM is `Serial1` on D0/D1, and Wi-Fi uses the board's internal link to the ESP32 — they do not fight over pins.
- The A7670C is not 5 V tolerant. Use a logic-level shifter on both UART lines and a separate supply (the Arduino 5 V pin cannot deliver the transmit current spikes).
- If your buzzer module triggers on LOW, change `BUZZER_ACTIVE_HIGH` to `false`.

## Setup

1. Install Arduino IDE 2.x and the **Arduino UNO R4 Boards** core. `WiFiS3` comes with that core.
2. In Library Manager install **Ethernet** (Arduino) and **MFRC522** (Miguel Balboa). No JSON library is required.
3. Edit the settings at the top of `gate-controller.ino`:
   - `WIFI_SSID`, `WIFI_PASSWORD`
   - `SERVER_HOST` and `SERVER_PORT` — must match the LAN address shown by `ops\start-sevs.vbs` / `APP_URL` in `.env` (default `192.168.100.13:8000`).
   - `ethFixedIp` — pick an unused IP on the same subnet as the Laravel PC.
   - `GSM_BAUD` if your A7670C was configured to something other than 115200.
4. On the Windows PC run `ops\allow-lan-access.bat` as administrator so the Arduino can reach port 8000. Confirm `http://<SERVER_HOST>:8000/up` opens from another device on the LAN.
5. Upload the sketch and open Serial Monitor at 115200. You should see the RC522 version, the Ethernet IP, Wi-Fi IP, and `[GSM] Registered on the network; SMS ready.`

## Laravel requirements

- Students need a `phone_number` and a current RFID tag in the registrar page. Laravel normalizes numbers like `09171234567` to `+639171234567` before handing them to the modem.
- A first tap of the day is a `time_in` and produces an SMS. The next tap is a `time_out` and produces no SMS.
- Unenrolled/expired/unknown cards are logged as denied entries and make the buzzer beep twice.
- The existing UniSMS API flow is unchanged for callers that omit `sms_channel` (browser/tests).

## Troubleshooting

| Symptom | Check |
|---|---|
| `[NET] Wi-Fi unavailable` every time | SSID/password, 2.4 GHz network, or run `allow-lan-access.bat` on the PC |
| Ethernet never used | Cable/link LED, CS/RST wiring, `ethFixedIp` conflicts with another device |
| `[GSM] Modem did not answer AT` | Baud rate, TX/RX crossed correctly, level shifter, powered supply, common ground |
| `[GSM] SIM not ready` | SIM inserted/unlocked, antenna connected |
| `[GSM] Not registered on the network yet` | Wait for LTE coverage; the sketch retries at startup and skips SMS until registered |
| Buzzer never sounds | `BUZZER_ACTIVE_HIGH` polarity, module IN pin, rejected-card test (unknown tag) |
| Buzzer sounds on valid taps | Laravel returned `beep: true` — check the student's enrollment/school year in the registrar page |
| No SMS on a valid tap | Look for `[SCAN] Accepted:` in Serial Monitor; Laravel only returns `sms` on `time_in`, and only when the student has a phone number |
