<?php

declare(strict_types=1);

namespace App\Domain\Access\Models;

use Database\Factories\DivisionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Division extends Model
{
    /** @use HasFactory<DivisionFactory> */
    use HasFactory, SoftDeletes;

    protected static function newFactory(): DivisionFactory
    {
        return DivisionFactory::new();
    }

    protected $fillable = ['code', 'name', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'division_permissions')->withTimestamps();
    }
}
