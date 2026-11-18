<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

/**
 * Tambah akun via Kelola Akun User: password OPSIONAL.
 * Tanpa password → hash acak (tak bisa login password), akun langsung
 * bisa dipakai login "Masuk dengan Google" karena emailnya terdaftar.
 */
class AdminUserStoreTest extends TestCase
{
    private array $emails = [
        'store-admin@4putra.test',
        'store-baru@4putra.test',
        'store-google@4putra.test',
    ];

    protected function tearDown(): void
    {
        User::whereIn('email', $this->emails)->delete();
        User::whereIn('username', ['petugas-lapangan', 'tanpa-jalur-login', 'email-pertama', 'email-kedua'])->delete();

        parent::tearDown();
    }

    private function admin(): User
    {
        return User::updateOrCreate(['email' => 'store-admin@4putra.test'], [
            'name' => 'Admin Store',
            'username' => 'store-admin',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);
    }

    public function test_tambah_user_tanpa_password_berhasil_lalu_bisa_login_google(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->postJson('/admin/users', [
            'name' => 'Petinggi Baru',
            'username' => 'petinggi-baru',
            'email' => 'store-baru@4putra.test',
            'role' => 'user',
        ])->assertOk()->assertJson(['status' => 'OK']);

        $user = User::where('email', 'store-baru@4putra.test')->first();
        $this->assertNotNull($user, 'Akun wajib terbentuk tanpa password');
        $this->assertNotSame('', $user->password);
        $this->assertFalse(Hash::check('password-tebak', $user->password), 'Password acak, tak bisa ditebak');

        // Email terdaftar → login Google langsung jalan
        $abstractUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $abstractUser->shouldReceive('getId')->andReturn('g-store-1');
        $abstractUser->shouldReceive('getEmail')->andReturn('store-baru@4putra.test');
        $abstractUser->shouldReceive('getName')->andReturn('Petinggi Baru');

        Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
        Socialite::shouldReceive('redirectUrl')->andReturnSelf();
        Socialite::shouldReceive('user')->andReturn($abstractUser);

        $this->get('/auth/google/callback');
        $this->assertAuthenticatedAs($user->fresh());
        $this->assertNotEmpty($user->fresh()->google_id);
    }

    public function test_password_opsional_tetap_divalidasi_saat_diisi(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->postJson('/admin/users', [
            'name' => 'Pendek',
            'username' => 'pendek',
            'email' => 'store-baru@4putra.test',
            'role' => 'user',
            'password' => 'pendek',
            'password_confirmation' => 'pendek',
        ])->assertStatus(422)->assertJsonValidationErrors('password');

        $this->actingAs($admin)->postJson('/admin/users', [
            'name' => 'Cocok',
            'username' => 'cocok',
            'email' => 'store-baru@4putra.test',
            'role' => 'user',
            'password' => 'password123',
            'password_confirmation' => 'beda',
        ])->assertStatus(422)->assertJsonValidationErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'store-baru@4putra.test']);
    }

    public function test_email_boleh_kosong_asalkan_password_diisi(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->postJson('/admin/users', [
            'name' => 'Petugas Lapangan',
            'username' => 'petugas-lapangan',
            'email' => null,
            'role' => 'user',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertOk()->assertJson(['status' => 'OK']);

        $user = User::where('username', 'petugas-lapangan')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email);

        // Login username+password tetap bisa tanpa email
        $this->post('/logout');
        $this->post('/login', ['username' => 'petugas-lapangan', 'password' => 'password123']);
        $this->assertAuthenticatedAs($user->fresh());

        $user->delete();
    }

    public function test_email_dan_password_keduanya_kosong_ditolak(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->postJson('/admin/users', [
            'name' => 'Nggak Bisa Login',
            'username' => 'tanpa-jalur-login',
            'role' => 'user',
        ])->assertStatus(422)->assertJsonValidationErrors('password');

        $this->assertDatabaseMissing('users', ['username' => 'tanpa-jalur-login']);
    }

    public function test_email_duplikat_tetap_ditolak(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->postJson('/admin/users', [
            'name' => 'Pertama', 'username' => 'email-pertama', 'email' => 'store-baru@4putra.test',
            'role' => 'user', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertOk();

        $this->actingAs($admin)->postJson('/admin/users', [
            'name' => 'Kedua', 'username' => 'email-kedua', 'email' => 'store-baru@4putra.test',
            'role' => 'user', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertStatus(422)->assertJsonValidationErrors('email');

        $this->assertDatabaseMissing('users', ['username' => 'email-kedua']);
    }

    public function test_daftar_user_memuat_akun_google_terlink(): void
    {
        $admin = $this->admin();

        User::updateOrCreate(['email' => 'store-google@4putra.test'], [
            'name' => 'User Google',
            'username' => 'store-google',
            'password' => bcrypt('password123'),
            'role' => 'user',
            'google_id' => 'g-store-77',
        ]);

        $this->actingAs($admin)->get('/admin/users')
            ->assertOk()
            ->assertSee('store-google@4putra.test');
    }
}
