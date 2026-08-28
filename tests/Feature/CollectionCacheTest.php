<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CollectionCacheTest extends TestCase
{
    public function test_admin_destroy_invalidates_public_collections_cache(): void
    {
        $collection = Collection::create([
            'name' => 'Uji Cache Koleksi',
            'category' => 'Uji',
        ]);

        // Hangatkan cache publik
        $this->get('/collections')->assertStatus(200);
        $this->assertTrue(Cache::has('public.collections'));

        $user = User::updateOrCreate(
            ['email' => 'admin-uji-cache-collection@4putra.test'],
            [
                'name' => 'Admin Uji Cache',
                'password' => bcrypt('password123'),
                'role' => 'admin',
            ]
        );

        $deleteUrl = route('admin.collections.destroy', $collection->getKey());
        fwrite(STDERR, 'DIAG URL=' . $deleteUrl . ' FIND=' . (Collection::find($collection->getKey()) ? 'OK' : 'NULL')
            . ' INDEX=' . $this->actingAs($user)->get(route('admin.collections.index'))->status() . PHP_EOL);

        $response = $this->actingAs($user)->delete($deleteUrl);

        if (! $response->isRedirect()) {
            fwrite(STDERR, 'DIAG STATUS=' . $response->status() . ' BODY=' . substr(strip_tags($response->getContent()), 0, 300) . PHP_EOL);
        }

        $response->assertRedirect(route('admin.collections.index'));

        $this->assertFalse(Cache::has('public.collections'));
        $this->assertFalse(Cache::has('admin.collections'));

        $user->delete();
    }
}
