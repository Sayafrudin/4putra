<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Keamanan endpoint autentikasi: rate limit brute force + log percobaan gagal.
 */
class LoginSecurityTest extends TestCase
{
    private function makeAdmin(): array
    {
        return [
            'email' => 'admin-loginsec-test@4putra.test',
            'password' => 'password123',
        ];
    }

    public function test_login_is_rate_limited_after_five_attempts(): void
    {
        $credentials = $this->makeAdmin();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => $credentials['email'],
                'password' => 'password-salah-'.($i + 1),
            ])->assertStatus(302); // redirect balik dengan error, bukan 429
        }

        // Percobaan ke-6 dalam 1 menit wajib diblokir
        $this->post('/login', [
            'email' => $credentials['email'],
            'password' => 'password-salah-6',
        ])->assertStatus(429);
    }

    public function test_failed_login_is_logged_with_email_and_ip(): void
    {
        Log::spy();

        $this->from('/login')->post('/login', [
            'email' => 'penyusup@contoh.test',
            'password' => 'password-salah',
        ])->assertRedirect('/login');

        Log::shouldHaveReceived('warning')->once()->withArgs(function (string $message, array $context) {
            return $message === 'Percobaan login gagal'
                && $context['email'] === 'penyusup@contoh.test'
                && ! empty($context['ip']);
        });
    }
}
