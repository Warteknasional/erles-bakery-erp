<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_login_with_valid_credentials(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'admin@erlesbakery.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login berhasil.',
            ])
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email', 'phone', 'role'],
                    'token',
                    'token_type',
                ],
            ]);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'admin@erlesbakery.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Email atau password salah.',
            ]);
    }

    public function test_login_validation_errors(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validasi data gagal.',
            ])
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_unauthenticated_user_cannot_access_me(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_access_me_and_logout(): void
    {
        $admin = User::where('email', 'admin@erlesbakery.com')->first();
        $token = $admin->createToken('test_token')->plainTextToken;

        // Test /api/me
        $meResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/me');

        $meResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'email' => 'admin@erlesbakery.com',
                    'role' => 'admin',
                ],
            ]);

        // Test /api/logout
        $logoutResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout');

        $logoutResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logout berhasil.',
            ]);

        $this->assertCount(0, $admin->tokens);
    }

    public function test_role_middleware_allows_authorized_role(): void
    {
        $admin = User::where('email', 'admin@erlesbakery.com')->first();
        $token = $admin->createToken('test_token')->plainTextToken;

        \Illuminate\Support\Facades\Route::get('/api/test-admin-only', function () {
            return response()->json(['success' => true]);
        })->middleware(['auth:sanctum', 'role:admin']);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/test-admin-only');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_role_middleware_forbids_unauthorized_role(): void
    {
        $staff = User::factory()->create(['role' => 'karyawan']);
        $token = $staff->createToken('test_token')->plainTextToken;

        \Illuminate\Support\Facades\Route::get('/api/test-admin-role', function () {
            return response()->json(['success' => true]);
        })->middleware(['auth:sanctum', 'role:admin']);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/test-admin-role');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akses ditolak. Anda tidak memiliki izin untuk tindakan ini.',
            ]);
    }
}
