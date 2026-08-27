<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    @vite ('resources/css/login.css')
</head>
<body class="loginbody">
    <img class="school" src="{{ asset('images/school.jpg') }}">
    <div class="loginbox">
        <div class="containerlogo">
            <img class="school" src="{{ asset('images/ietilogo.png') }}">
        </div>
        <h1 class="Welcome">Welcome User!</h1>
        <input type="text" class="username" placeholder="Username" />
        <input type="password" class="password" placeholder="Password" />
        <button class="loginbutton">
            Login
        </button>
    </div>
</body>
</html>