<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_can_sign_in_with_valid_credentials(): void
    {
        $password = 'ClaveSegura123!';

        $user = User::factory()->create([
            'email' => 'tecnico@techsupport.test',
            'password' => $password,
            'role' => 'tecnico',
            'is_active' => true,
        ]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => $password,
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_password_is_rejected(): void
    {
        $user = User::factory()->create([
            'password' => 'ClaveSegura123!',
            'is_active' => true,
        ]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'Incorrecta123!',
        ]);

        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_account_cannot_sign_in(): void
    {
        $password = 'ClaveSegura123!';

        $user = User::factory()->create([
            'password' => $password,
            'is_active' => false,
        ]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => $password,
        ]);

        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_authenticated_user_can_sign_out(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('logout'));

        $response->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_guest_cannot_access_user_administration(): void
    {
        $response = $this->get(route('admin.users.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_administrator_can_access_user_administration(): void
    {
        $admin = User::factory()->create([
            'role' => 'administrador',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.users.index'));

        $response->assertOk();
    }

    public function test_technician_cannot_access_user_administration(): void
    {
        $technician = User::factory()->create([
            'role' => 'tecnico',
            'is_active' => true,
        ]);

        $response = $this->actingAs($technician)
            ->get(route('admin.users.index'));

        $response->assertForbidden();
    }

    public function test_requester_cannot_access_user_administration(): void
    {
        $requester = User::factory()->create([
            'role' => 'solicitante',
            'is_active' => true,
        ]);

        $response = $this->actingAs($requester)
            ->get(route('admin.users.index'));

        $response->assertForbidden();
    }

    public function test_inactive_authenticated_user_is_logged_out(): void
    {
        $user = User::factory()->create([
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)
            ->get(route('dashboard'));

        $response->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_administrator_cannot_deactivate_own_account(): void
    {
        $admin = User::factory()->create([
            'role' => 'administrador',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->patch(route('admin.users.toggle-status', $admin));

        $response->assertSessionHasErrors('account');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'is_active' => 1,
        ]);
    }

    public function test_administrator_can_create_a_user(): void
    {
        $admin = User::factory()->create([
            'role' => 'administrador',
            'is_active' => true,
        ]);

        $password = 'NuevaClave123!';

        $response = $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Tecnico de prueba',
                'email' => 'nuevo.tecnico@techsupport.test',
                'role' => 'tecnico',
                'password' => $password,
                'password_confirmation' => $password,
            ]);

        $response->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'name' => 'Tecnico de prueba',
            'email' => 'nuevo.tecnico@techsupport.test',
            'role' => 'tecnico',
            'is_active' => 1,
        ]);

        $createdUser = User::where(
            'email',
            'nuevo.tecnico@techsupport.test'
        )->firstOrFail();

        $this->assertTrue(
            Hash::check($password, $createdUser->password)
        );
    }

    public function test_administrator_cannot_register_duplicate_email(): void
    {
        $admin = User::factory()->create([
            'role' => 'administrador',
            'is_active' => true,
        ]);

        $existingUser = User::factory()->create([
            'email' => 'existente@techsupport.test',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Usuario duplicado',
                'email' => $existingUser->email,
                'role' => 'solicitante',
                'password' => 'NuevaClave123!',
                'password_confirmation' => 'NuevaClave123!',
            ]);

        $response->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 2);
    }

    public function test_administrator_cannot_assign_unsupported_role(): void
    {
        $admin = User::factory()->create([
            'role' => 'administrador',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Usuario de prueba',
                'email' => 'usuario.rol@techsupport.test',
                'role' => 'superusuario',
                'password' => 'NuevaClave123!',
                'password_confirmation' => 'NuevaClave123!',
            ]);

        $response->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', [
            'email' => 'usuario.rol@techsupport.test',
        ]);
    }
}
