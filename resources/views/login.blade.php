<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="{{ asset('bootstrap.min.css') }}">
    @vite(['resources/css/login.css'])
</head>
<body class="d-flex justify-content-end align-items-center min-vh-100">
    <img id="background-image"class="position-absolute top-0 start-0 vh-100 z-n1 opacity-75" style="width: 60%; object-fit: cover; filter: brightness(0.8);" src="{{ asset('images/school.jpg') }}">
    <div class="position-relative d-flex justify-content-center align-items-center flex-column vh-100" style="background: linear-gradient(135deg, #1fa851 0%, #30d359 50%, #19c945 100%); width: 700px;">
        <div id="logo-container" class="position-absolute top-0 end-0" style="height: 80px; border: 1px solid black; background: #19c945; width: 700px; border-radius: 0 0 10px 10px; box-shadow: 0 0 10px #000000;">
            <img id="school-logo" class="position-absolute top-0 end-0 z-n1" style="width: 76px; opacity: 0.9; object-fit: cover; filter: brightness(0.6);" src="{{ asset('images/ietilogo.png') }}">
            <img id="group-logo" class="position-absolute top-0 start-0 z-n1" style="width: 130px; opacity: 0.9; object-fit: cover; filter: brightness(0.6);" src="{{ asset('images/group-logo.png') }}">
        </div>
        <h1 id="welcome-heading" style="font-size: 40px; margin-bottom: 30px; font-family: 'Arial', sans-serif; color: #000000;">Welcome User!</h1>
        <input type="text" class="username" placeholder="Username" />
        <input type="password" class="password" placeholder="Password" />
        <button class="loginbutton">
            Login
        </button>
        <a href="{{ route('register') }}" class="register-link" style="margin-top: 20px; color: #000000; text-decoration: underline;">
            Don't have an account? Register
        </a>
    </div>
</body>
</html>