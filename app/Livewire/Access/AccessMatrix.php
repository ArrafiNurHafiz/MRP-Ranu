<?php

declare(strict_types=1);

namespace App\Livewire\Access;

use App\Domain\Access\Enums\Permission;
use App\Domain\Access\Models\Division;
use App\Domain\Access\Models\Permission as PermissionModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class AccessMatrix extends Component
{
    public int $divisionId = 0;

    /** @var list<string> */
    public array $selected = [];

    public function mount(): void
    {
        Gate::authorize(Permission::AccessDivisionsManage->value);

        $this->divisionId = (int) Division::orderBy('id')->value('id');
        $this->loadMatrix();
    }

    public function updatedDivisionId(): void
    {
        $this->loadMatrix();
    }

    public function save(): void
    {
        Gate::authorize(Permission::AccessDivisionsManage->value);

        $division = Division::findOrFail($this->divisionId);
        $validKeys = array_column(Permission::cases(), 'value');
        $keys = array_values(array_intersect($this->selected, $validKeys));

        DB::transaction(function () use ($division, $keys) {
            $division->permissions()->sync(PermissionModel::whereIn('key', $keys)->pluck('id'));
        });

        session()->flash('status', __('Access matrix saved.'));
    }

    public function render()
    {
        return view('livewire.access.access-matrix', [
            'divisions' => Division::orderBy('id')->get(),
            'permissions' => PermissionModel::orderBy('module')->orderBy('key')->get(),
        ])->layout('components.layouts.app');
    }

    private function loadMatrix(): void
    {
        $this->selected = Division::find($this->divisionId)?->permissions->pluck('key')->all() ?? [];
    }
}
