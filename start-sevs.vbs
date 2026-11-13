Option Explicit

Dim fso, shell, baseDir, url, port, healthUrl, lanIp

Set fso = CreateObject("Scripting.FileSystemObject")
Set shell = CreateObject("WScript.Shell")

baseDir = fso.GetParentFolderName(WScript.ScriptFullName)
port = 8000
lanIp = GetLanIp()

If lanIp <> "" Then
    url = "http://" & lanIp & ":" & port & "/login"
Else
    url = "http://localhost:" & port & "/login"
End If
healthUrl = "http://127.0.0.1:" & port & "/login"

EnsureAssets
If Not IsServerUp() Then
    StartServer
    WaitForServer 120
End If

shell.Run url, 1, False
ShowAddress

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
    serverCmd = "cmd /c cd /d """ & baseDir & """ && php artisan serve --host=0.0.0.0 --port=" & port
    shell.Run serverCmd, 0, False
End Sub

Sub ShowAddress()
    Dim message

    If lanIp = "" Then Exit Sub

    message = "IETI SEVS is running." & vbCrLf & vbCrLf & _
        "Open from any device on this network:" & vbCrLf & _
        url & vbCrLf & vbCrLf & _
        "If another device cannot connect, run allow-lan-access.bat " & _
        "as administrator to open TCP port " & port & " in Windows Firewall."

    shell.Popup message, 10, "IETI SEVS", 64
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

Function IsServerUp()
    Dim http

    IsServerUp = False
    On Error Resume Next

    Set http = CreateObject("WinHttp.WinHttpRequest.5.1")
    http.SetTimeouts 500, 500, 500, 500
    http.Open "GET", healthUrl, False
    http.Send

    ' Any HTTP response means a server is already listening on the port.
    ' Do not start a second one just because the app returned an error page.
    If Err.Number = 0 Then
        If http.Status > 0 Then
            IsServerUp = True
        End If
    End If

    Err.Clear
    On Error GoTo 0
End Function

Sub WaitForServer(seconds)
    Dim i

    For i = 1 To seconds * 2
        If IsServerUp() Then Exit Sub
        WScript.Sleep 500
    Next
End Sub
