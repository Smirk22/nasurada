Option Explicit

Dim fso, shell, baseDir, url, port, healthUrl

Set fso = CreateObject("Scripting.FileSystemObject")
Set shell = CreateObject("WScript.Shell")

baseDir = fso.GetParentFolderName(WScript.ScriptFullName)
url = "http://localhost:8000/login"
port = 8000
healthUrl = "http://127.0.0.1:" & port & "/login"

EnsureAssets
If Not IsServerUp() Then
    StartServer
    WaitForServer 120
End If

shell.Run url, 1, False

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

Function IsServerUp()
    Dim http

    IsServerUp = False
    On Error Resume Next

    Set http = CreateObject("WinHttp.WinHttpRequest.5.1")
    http.SetTimeouts 500, 500, 500, 500
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

Sub WaitForServer(seconds)
    Dim i

    For i = 1 To seconds * 2
        If IsServerUp() Then Exit Sub
        WScript.Sleep 500
    Next
End Sub
