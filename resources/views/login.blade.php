<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('bootstrap.min.css') }}">
    @vite(['resources/css/login.css'])
</head>
<body class="d-flex justify-content-end align-items-center min-vh-100">

    <img id="background-image"class="position-absolute top-0 start-0 vh-100 z-n1 opacity-75" style="width: 60%; object-fit: cover; filter: brightness(0.8);" src="{{ asset('images/school.jpg') }}">

    <div class="position-relative d-flex justify-content-center align-items-center flex-column vh-100" style="background: white; width: 700px;">
        
        <div id="logo-container" class="position-absolute top-0 end-0" style="height: 80px; border: 0.5px; background: #46CB18; width: 700px; border-radius: 0 0 10px 10px; box-shadow: 0 0 10px #000000;">
            <img id="school-logo" class="position-absolute top-0 end-0 z-n1" style="width: 76px; opacity: 0.9; object-fit: cover; filter: brightness(0.6);" src="{{ asset('images/ietilogo.png') }}">
            <img id="group-logo" class="position-absolute top-0 start-0 z-n1" style="width: 130px; opacity: 0.9; object-fit: cover; filter: brightness(0.6);" src="{{ asset('images/group-logo.png') }}">
        </div>

        <h1 id="welcome-heading" style="font-size: 40px; margin-bottom: 30px; font-family: 'Inter', sans-serif; color: black;">Welcome User!</h1>

        <form  action="{{ route('login') }}" method="POST" class="position-relative d-flex flex-column align-items-center" style="width: 700px;">
            @csrf
         <input type="text" class="username" name="username" placeholder="Username" required />
         <input type="password" class="password" name="password" placeholder="Password" required />
         <button class="loginbutton">
            Login
         </button>
             @if ($errors->any())
              <p>{{ $errors->first() }}</p>
             @endif
        </form>

        <a href="{{ route('register') }}" class="register-link" style="margin-top: 20px; color: #000000; text-decoration: underline;">
            Don't have an account? Register
        </a>

        <footer class="position-absolute bottom-0 start-0 w-100 text-center py-3" style="background: #46CB18; border-radius: 10px 10px 0 0;  box-shadow: 0 0 10px #000000;">
        </footer>

    </div>

</body>
</html>