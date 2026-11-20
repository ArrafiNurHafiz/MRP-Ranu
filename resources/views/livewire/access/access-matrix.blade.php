<div>
    <h1 class="mb-6 text-2xl font-semibold">{{ __('Access matrix') }}</h1>

    <div class="mb-6">
        <label for="division" class="block text-sm">{{ __('Division') }}</label>
        <select id="division" wire:model.live="divisionId" class="mt-1 rounded border-gray-300">
            @foreach ($divisions as $division)
                <option value="{{ $division->id }}">{{ $division->name }}</option>
            @endforeach
        </select>
    </div>

    <form wire:submit="save">
        @foreach ($permissions->groupBy('module') as $module => $items)
            <section class="mb-6">
                <h2 class="mb-2 font-semibold">{{ $module }}</h2>
                @foreach ($items as $permission)
                    <label class="flex items-center gap-2 py-1 text-sm">
                        <input type="checkbox" value="{{ $permission->key }}" wire:model="selected">
                        <span>{{ $permission->description }}</span>
                    </label>
                @endforeach
            </section>
        @endforeach

        <button type="submit" class="rounded bg-blue-600 px-4 py-2 text-white">{{ __('Save') }}</button>
    </form>
</div>
