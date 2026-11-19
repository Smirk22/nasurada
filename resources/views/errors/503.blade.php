<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance - {{ $siteName ?? config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-white rounded-2xl shadow border border-gray-100 p-8 text-center">
        <div class="mx-auto flex items-center justify-center w-14 h-14 rounded-full bg-amber-100 text-amber-700 mb-4">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">System temporarily unavailable</h1>
        <p class="text-sm text-gray-500 mt-2">
            {{ $siteName ?? config('app.name') }} is undergoing scheduled maintenance.
            Please try again shortly.
        </p>
        <p class="text-xs text-gray-400 mt-6">If you are a Super Admin, sign in to turn maintenance mode off.</p>
        <a href="{{ route('login') }}" class="inline-block mt-4 px-5 py-2 bg-ieti-green hover:bg-green-800 text-white rounded-lg text-sm font-semibold transition">
            Go to sign in
        </a>
    </div>
</body>
</html>
