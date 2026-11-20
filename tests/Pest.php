<?php

declare(strict_types=1);

use App\Domain\Access\Models\Division;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature');
uses(TestCase::class)->in('Unit');

function userForDivision(string $code): User
{
    $division = Division::where('code', $code)->firstOrFail();

    return User::factory()->create(['division_id' => $division->id]);
}
