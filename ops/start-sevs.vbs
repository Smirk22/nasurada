Option Explicit

' IETI SEVS launcher
' - Opens the app on this PC via http://localhost (no "Not secure" warning)
' - Starts the Laravel server only if it is not already running
' - Never starts a second server when port 8000 is already in use
' - Shows the LAN address for phones/tablets on the same network

Const PORT = 8000
Const LOGIN_PATH = "/login"

Dim fso, shell, baseDir, localUrl, healthUrl
Dim startedServer

Set fso = CreateObject("Scripting.FileSystemObject")
Set shell = CreateObject("WScript.Shell")

' Script lives in ops/; project root is one level up
baseDir = fso.GetParentFolderName(WScript.ScriptFullName)
If LCase(fso.GetFileName(baseDir)) = "ops" Then
    baseDir = fso.GetParentFolderName(baseDir)
End If
localUrl = "http://localhost:" & PORT & LOGIN_PATH
healthUrl = "http://127.0.0.1:" & PORT & "/up"

EnsureAssets

startedServer = False

If IsPortListening() Then
    ' A server is already running or still booting. Wait for it to answer
    ' instead of launching a duplicate process on the same port.
    If Not IsServerUp() Then WaitForServer 60
Else
    StartServer
    startedServer = True
    WaitForServer 60
End If

If Not IsServerUp() Then
    MsgBox "IETI SEVS could not be started." & vbCrLf & vbCrLf & _
        "Make sure PHP is installed and available in PATH, then try again." & vbCrLf & _
        "You can also run start-sevs.bat from a Command Prompt to see the error.", _
        16, "IETI SEVS"
    WScript.Quit 1
End If

' localhost is treated as a secure origin by Edge/Chrome, so no
' "Not secure" warning appears in the address bar on this PC.
shell.Run localUrl, 1, False

If startedServer Then ShowAddress

' ---------------------------------------------------------------------------

Sub EnsureAssets()
    If Not fso.FolderExists(baseDir & "\node_modules") Then
        shell.Run "cmd /c cd /d """ & baseDir & """ && npm install", 0, True
    End If

    If Not fso.FileExists(baseDir & "\public\build\manifest.json") Then
        shell.Run "cmd /c cd /d """ & baseDir & """ && npm run build", 0, True
    End If
End Sub

Sub StartServer()
    Dim serverCmd
    serverCmd = "cmd /c cd /d """ & baseDir & """ && php artisan serve --host=0.0.0.0 --port=" & PORT
    shell.Run serverCmd, 0, False
End Sub

Sub ShowAddress()
    Dim message, lanIp, lanUrl

    lanIp = GetLanIp()
    If lanIp = "" Then Exit Sub

    lanUrl = "http://" & lanIp & ":" & PORT & LOGIN_PATH

    message = "IETI SEVS is running." & vbCrLf & vbCrLf & _
        "On this PC:" & vbCrLf & _
        "http://localhost:" & PORT & vbCrLf & vbCrLf & _
        "From other devices on this network:" & vbCrLf & _
        lanUrl & vbCrLf & vbCrLf & _
        "If another device cannot connect, run allow-lan-access.bat " & _
        "as administrator to open TCP port " & PORT & " in Windows Firewall."

    shell.Popup message, 5, "IETI SEVS", 64
End Sub

Function IsServerUp()
    Dim http

    IsServerUp = False
    On Error Resume Next

    Set http = CreateObject("WinHttp.WinHttpRequest.5.1")
    http.SetTimeouts 1000, 1000, 1000, 2000
    http.Open "GET", healthUrl, False
    http.Send

    If Err.Number = 0 Then
        If http.Status = 200 Or http.Status = 302 Then
            IsServerUp = True
        End If
    End If

    Err.Clear
    On Error GoTo 0
End Function

Function IsPortListening()
    Dim exec, output

    IsPortListening = False
    On Error Resume Next

    Set exec = shell.Exec("cmd /c netstat -ano -p tcp | findstr LISTENING | findstr /C:"":" & PORT & " """)
    Do While exec.Status = 0
        WScript.Sleep 50
    Loop
    output = exec.StdOut.ReadAll

    If InStr(output, ":" & PORT) > 0 Then IsPortListening = True

    Err.Clear
    On Error GoTo 0
End Function

Sub WaitForServer(seconds)
    Dim i

    For i = 1 To seconds * 4
        If IsServerUp() Then Exit Sub
        WScript.Sleep 250
    Next
End Sub

Function GetLanIp()
    Dim wmi, adapters, adapter, addresses, address

    GetLanIp = ""
    On Error Resume Next

    Set wmi = GetObject("winmgmts:\\.\root\cimv2")
    Set adapters = wmi.ExecQuery("SELECT IPAddress, DefaultIPGateway FROM Win32_NetworkAdapterConfiguration WHERE IPEnabled = True")

    For Each adapter In adapters
        If Not IsNull(adapter.DefaultIPGateway) Then
            If UBound(adapter.DefaultIPGateway) >= 0 Then
                If Not IsNull(adapter.IPAddress) Then
                    For Each address In adapter.IPAddress
                        If IsLanIpv4(address) Then
                            GetLanIp = address
                            Exit Function
                        End If
                    Next
                End If
            End If
        End If
    Next

    On Error GoTo 0
End Function

Function IsLanIpv4(value)
    IsLanIpv4 = False

    If InStr(value, ".") = 0 Then Exit Function
    If InStr(value, ":") > 0 Then Exit Function
    If Left(value, 4) = "127." Then Exit Function
    If Left(value, 8) = "169.254." Then Exit Function

    IsLanIpv4 = True
End Function
