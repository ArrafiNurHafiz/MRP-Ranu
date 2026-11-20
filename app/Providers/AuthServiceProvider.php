<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Access\Enums\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Satu-satunya bypass di sistem: divisi admin (docs/04-hak-akses.md).
        Gate::before(function (User $user, string $ability) {
            return $user->division?->code === 'admin' ? true : null;
        });

        foreach (Permission::cases() as $permission) {
            Gate::define($permission->value, function (User $user) use ($permission) {
                $division = $user->division;

                return $division !== null
                    && $division->is_active
                    && $division->permissions->contains('key', $permission->value);
            });
        }
    }
}
