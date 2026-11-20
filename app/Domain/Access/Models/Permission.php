<?php

declare(strict_types=1);

namespace App\Domain\Access\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = ['key', 'module', 'description'];
}
