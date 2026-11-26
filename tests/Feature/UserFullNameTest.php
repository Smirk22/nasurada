<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class UserFullNameTest extends TestCase
{
    use DatabaseMigrations;

    private function admin(): User
    {
        return User::create([
            'name' => 'System Administrator',
            'username' => 'admin-full-name',
            'email' => 'admin-full-name@example.com',
            'password' => bcrypt('password'),
            'role' => 'Admin',
        ]);
    }

    public function test_registered_full_name_is_saved_and_displayed_in_user_management(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), [
                'name' => 'Maria Santos',
                'username' => 'maria.registrar',
                'email' => 'maria@example.com',
                'password' => 'Str0ng!Password123',
                'role' => 'Registrar',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('accounts', [
            'username' => 'maria.registrar',
            'name' => 'Maria Santos',
        ]);

        $this->get(route('adashboard'))
            ->assertOk()
            ->assertSee('Maria Santos');
    }

    public function test_updated_full_name_is_displayed_in_user_management(): void
    {
        $user = User::create([
            'name' => 'Old Name',
            'username' => 'old.name',
            'email' => 'old.name@example.com',
            'password' => bcrypt('password'),
            'role' => 'Registrar',
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.users.update', $user), [
                'name' => 'New Full Name',
                'username' => $user->username,
                'email' => $user->email,
                'role' => 'Registrar',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('accounts', [
            'id' => $user->id,
            'name' => 'New Full Name',
        ]);

        $this->get(route('adashboard'))
            ->assertOk()
            ->assertSee('New Full Name');
    }
}
