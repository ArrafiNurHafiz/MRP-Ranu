<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'MRP Ranu')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-50 text-gray-900">
    <nav class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
            <a href="{{ route('home') }}" class="font-semibold">{{ __('MRP Ranu') }}</a>
            @auth
                <div class="flex items-center gap-4 text-sm">
                    <span>{{ auth()->user()->name }} — {{ auth()->user()->division->name }}</span>
                    @can(\App\Domain\Access\Enums\Permission::AccessDivisionsManage->value)
                        <a href="{{ route('access.matrix') }}" class="underline">{{ __('Access matrix') }}</a>
                    @endcan
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit">{{ __('Log out') }}</button>
                    </form>
                </div>
            @endauth
        </div>
    </nav>

    <main class="mx-auto max-w-6xl px-4 py-8">
        @if (session('status'))
            <p class="mb-4 rounded bg-green-100 p-3 text-green-800">{{ session('status') }}</p>
        @endif
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
