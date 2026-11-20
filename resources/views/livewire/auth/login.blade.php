<div class="mx-auto max-w-sm">
    <h1 class="mb-6 text-2xl font-semibold">{{ __('Sign in') }}</h1>

    <form wire:submit="login" class="space-y-4">
        <div>
            <label for="email" class="block text-sm">{{ __('Email') }}</label>
            <input id="email" type="email" wire:model="email" autocomplete="username"
                   class="mt-1 w-full rounded border-gray-300">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm">{{ __('Password') }}</label>
            <input id="password" type="password" wire:model="password" autocomplete="current-password"
                   class="mt-1 w-full rounded border-gray-300">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="rounded bg-blue-600 px-4 py-2 text-white">{{ __('Sign in') }}</button>
    </form>
</div>
