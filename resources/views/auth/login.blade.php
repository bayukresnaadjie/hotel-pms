<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/><title>Login · {{ config('hotel.nama') }}</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-gradient-to-br from-stone-900 via-stone-800 to-amber-900 min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-sm">
    <div class="text-center mb-8">
        <div class="w-16 h-16 bg-amber-500 rounded-2xl flex items-center justify-center mx-auto mb-4 text-3xl shadow-lg">🏨</div>
        <h1 class="font-bold text-white text-2xl">{{ config('hotel.nama') }}</h1>
        <p class="text-amber-300 text-sm mt-1">Property Management System</p>
    </div>
    <div class="bg-white rounded-2xl shadow-xl p-7">
        @if($errors->any())<div class="bg-red-50 border border-red-200 text-red-600 text-sm px-4 py-3 rounded-lg mb-4">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('login.post') }}" class="space-y-4">@csrf
            <div><label class="block text-xs font-semibold text-stone-600 mb-1.5">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus class="w-full border border-stone-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"/></div>
            <div><label class="block text-xs font-semibold text-stone-600 mb-1.5">Password</label>
            <input type="password" name="password" required class="w-full border border-stone-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500"/></div>
            <button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 text-stone-900 font-bold py-3 rounded-xl transition">Masuk</button>
        </form>
        <div class="mt-5 p-3 bg-stone-50 rounded-xl text-xs text-stone-500 space-y-1">
            <div class="font-semibold text-stone-600 mb-1">Akun Demo:</div>
            <div>👑 admin@hotel.test — Admin</div>
            <div>🛎️ frontdesk@hotel.test — Front Desk</div>
            <div>🧹 hk1@hotel.test — Housekeeping</div>
            <div>💼 finance@hotel.test — Finance</div>
            <div class="text-stone-400 mt-1">Password: password</div>
        </div>
    </div>
</div>
</body>
</html>
