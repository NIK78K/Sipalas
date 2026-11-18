<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_user_management(): void
    {
        $response = $this->get(route('users.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_regular_user_cannot_access_user_management(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get(route('users.index'));
        $response->assertForbidden();
    }

    public function test_admin_cannot_access_user_management(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('users.index'));
        $response->assertForbidden();
    }

    public function test_operator_can_view_user_management_page(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);

        $response = $this->actingAs($operator)->get(route('users.index'));
        $response->assertOk();
    }

    public function test_operator_can_create_new_user(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);

        $response = $this->actingAs($operator)->post(route('users.store'), [
            'name' => 'Budi Santoso',
            'username' => 'budis',
            'email' => 'budi@bps.go.id',
            'password' => 'password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'username' => 'budis',
            'email' => 'budi@bps.go.id',
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_operator_can_update_user(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $target = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($operator)->put(route('users.update', $target), [
            'name' => 'Updated Name',
            'username' => $target->username,
            'email' => 'newemail@bps.go.id',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'name' => 'Updated Name',
            'email' => 'newemail@bps.go.id',
            'role' => 'admin',
        ]);
    }

    public function test_operator_can_toggle_user_status(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $target = User::factory()->create(['role' => 'user', 'is_active' => true]);

        $response = $this->actingAs($operator)->patch(route('users.toggle', $target));

        $response->assertRedirect();
        $this->assertFalse($target->fresh()->is_active);
    }

    public function test_sole_operator_cannot_deactivate_oneself(): void
    {
        $operator = User::factory()->create(['role' => 'operator', 'is_active' => true]);

        $response = $this->actingAs($operator)->patch(route('users.toggle', $operator));

        $response->assertForbidden();
        $this->assertTrue($operator->fresh()->is_active);
    }

    public function test_sole_operator_cannot_demote_oneself(): void
    {
        $operator = User::factory()->create(['role' => 'operator', 'is_active' => true]);

        $response = $this->actingAs($operator)->put(route('users.update', $operator), [
            'name' => $operator->name,
            'username' => $operator->username,
            'email' => $operator->email,
            'role' => 'user',
            'is_active' => true,
        ]);

        $response->assertForbidden();
        $this->assertEquals('operator', $operator->fresh()->role);
    }

    public function test_operator_can_reset_password(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $target = User::factory()->create(['password' => Hash::make('oldpassword')]);

        $response = $this->actingAs($operator)->post(route('users.reset-password', $target), [
            'password' => 'newpass12345',
        ]);

        $response->assertRedirect();
        $this->assertTrue(Hash::check('newpass12345', $target->fresh()->password));
    }

    public function test_user_can_login_with_username_or_email(): void
    {
        $user = User::factory()->create([
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        // Login with email
        $responseEmail = $this->post(route('login.store'), [
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);
        $responseEmail->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);

        // Logout
        $this->post(route('logout'));
        $this->assertGuest();

        // Login with username
        $responseUsername = $this->post(route('login.store'), [
            'email' => 'johndoe',
            'password' => 'password123',
        ]);
        $responseUsername->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'username' => 'blockeduser',
            'email' => 'blocked@example.com',
            'password' => Hash::make('password123'),
            'is_active' => false,
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'blockeduser',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }
}
