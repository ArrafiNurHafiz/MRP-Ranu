<x-layouts.app>
    <h1 class="text-2xl font-semibold">{{ __('Welcome') }}, {{ auth()->user()->name }}</h1>
    <p class="mt-2 text-sm text-gray-600">{{ __('Division') }}: {{ auth()->user()->division->name }}</p>
</x-layouts.app>
