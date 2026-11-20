<?php

declare(strict_types=1);

use App\Domain\Access\Models\Division;
use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Database\QueryException;
use Livewire\Livewire;

it('shows the login page to guests', function () {
    $this->get('/login')->assertOk();
});

it('redirects guests away from the home page', function () {
    $this->get('/')->assertRedirect(route('login'));
});

it('authenticates the seeded admin', function () {
    Livewire::test(Login::class)
        ->set('email', 'admin@ranu.test')
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect(route('home'));

    $this->assertAuthenticated();
});

it('rejects wrong credentials', function () {
    Livewire::test(Login::class)
        ->set('email', 'admin@ranu.test')
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest();
});

it('rejects login for an inactive division', function () {
    $division = Division::factory()->create(['is_active' => false]);
    $user = User::factory()->create(['division_id' => $division->id]);

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest();
});

it('throttles repeated failed logins', function () {
    foreach (range(1, 5) as $attempt) {
        Livewire::test(Login::class)
            ->set('email', 'admin@ranu.test')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors('email');
    }

    Livewire::test(Login::class)
        ->set('email', 'admin@ranu.test')
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');

    // Percobaan ke-6 ditolak rate limiter bahkan dengan password benar.
    Livewire::test(Login::class)
        ->set('email', 'admin@ranu.test')
        ->set('password', 'password')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest();
});

it('logs out an authenticated user', function () {
    $this->actingAs(userForDivision('admin'))
        ->post('/logout')
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('requires a division at the database level', function () {
    expect(fn () => User::factory()->create(['division_id' => null]))
        ->toThrow(QueryException::class);
});
