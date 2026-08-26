<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class AdminSessionPingTest extends TestCase
{
    public function test_guest_cannot_ping_admin_session(): void
    {
        $response = $this->getJson('/admin/ping');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_ping_and_receive_valid_csrf_token(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'admin-ping-test@4putra.test'],
            [
                'name' => 'Admin Ping Test',
                'password' => bcrypt('password123'),
                'role' => 'admin',
            ]
        );

        $response = $this->actingAs($user)->getJson('/admin/ping');

        $response->assertStatus(200)
            ->assertJson([
                'ok' => true,
            ])
            ->assertJsonStructure([
                'ok',
                'csrf',
            ]);

        $this->assertNotEmpty($response->json('csrf'));

        $user->delete();
    }

    public function test_authenticated_user_can_ping_via_post_method(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'admin-ping-test@4putra.test'],
            [
                'name' => 'Admin Ping Test',
                'password' => bcrypt('password123'),
                'role' => 'admin',
            ]
        );

        $response = $this->actingAs($user)->postJson('/admin/ping');

        $response->assertStatus(200)
            ->assertJson([
                'ok' => true,
            ]);

        $user->delete();
    }
}
