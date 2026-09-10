<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CollectionVariantTest extends TestCase
{
    protected function tearDown(): void
    {
        Collection::where('category', 'uji-varian')->delete();
        parent::tearDown();
    }

    private function makeAdmin(): User
    {
        return User::updateOrCreate(
            ['email' => 'admin-variant-test@4putra.test'],
            [
                'name' => 'Admin Variant Test',
                'password' => bcrypt('password123'),
                'role' => 'admin',
            ]
        );
    }

    private function makeCollection(array $attrs = []): Collection
    {
        return Collection::create(array_merge([
            'name' => 'Uji IRN',
            'category' => 'uji-varian',
        ], $attrs));
    }

    public function test_parent_has_variants_and_variant_has_parent(): void
    {
        $parent = $this->makeCollection();
        $child = $this->makeCollection(['name' => 'Uji IRN Albino', 'parent_id' => $parent->id]);

        // Komparasi longgar sesuai konvensi TiDB (stringify fetches)
        $this->assertEquals($parent->id, $child->parent->id);
        $this->assertTrue($parent->variants->contains(fn ($v) => $v->id == $child->id));
    }

    public function test_admin_can_store_variant_with_parent(): void
    {
        $parent = $this->makeCollection();
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->postJson(route('admin.collections.store'), [
                'name' => 'Uji IRN CT',
                'category' => 'uji-varian',
                'parent_id' => $parent->id,
            ])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $variant = Collection::where('name', 'Uji IRN CT')->first();
        $this->assertNotNull($variant);
        $this->assertEquals($parent->id, $variant->parent_id);
    }

    public function test_admin_can_store_multiple_variants_under_same_parent(): void
    {
        $parent = $this->makeCollection();
        $admin = $this->makeAdmin();

        foreach (['Uji IRN Varian 1', 'Uji IRN Varian 2'] as $nama) {
            $this->actingAs($admin)
                ->postJson(route('admin.collections.store'), [
                    'name' => $nama,
                    'category' => 'uji-varian',
                    'parent_id' => $parent->id,
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);
        }

        // Kedua varian tersimpan di bawah induk yang sama (kedalaman 1 level)
        $this->assertCount(2, Collection::where('parent_id', $parent->id)->get());
    }

    public function test_admin_cannot_nest_variant_under_variant_or_self(): void
    {
        $parent = $this->makeCollection();
        $variant = $this->makeCollection(['name' => 'Uji IRN Albino', 'parent_id' => $parent->id]);
        $admin = $this->makeAdmin();

        // Varian tidak boleh dijadikan induk (maks 1 level)
        $this->actingAs($admin)
            ->postJson(route('admin.collections.store'), [
                'name' => 'Uji Kedalaman',
                'category' => 'uji-varian',
                'parent_id' => $variant->id,
            ])
            ->assertStatus(422);

        // Induk tidak boleh menjadi induk dirinya sendiri
        $this->actingAs($admin)
            ->putJson(route('admin.collections.update', ['collection' => $parent]), [
                'name' => 'Uji IRN',
                'category' => 'uji-varian',
                'parent_id' => $parent->id,
            ])
            ->assertStatus(422);
    }

    public function test_admin_cannot_store_variant_with_unknown_parent(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->postJson(route('admin.collections.store'), [
                'name' => 'Uji Yatim',
                'category' => 'uji-varian',
                'parent_id' => 99999999,
            ])
            ->assertStatus(422);
    }

    public function test_destroy_parent_promotes_variants_to_top_level(): void
    {
        $parent = $this->makeCollection();
        $variant = $this->makeCollection(['name' => 'Uji IRN Albino', 'parent_id' => $parent->id]);
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->delete(route('admin.collections.destroy', ['collection' => $parent]))
            ->assertRedirect(route('admin.collections.index'));

        $this->assertNull($variant->refresh()->parent_id);
    }

    public function test_public_page_renders_variant_only_inside_parent_grid(): void
    {
        $parent = $this->makeCollection(['name' => 'Uji IRN Unik']);
        $this->makeCollection(['name' => 'Uji IRN Albino Unik', 'parent_id' => $parent->id]);

        $response = $this->get('/collections');

        $response->assertStatus(200);
        // Card induk dirender server-side tepat sekali
        $this->assertSame(1, substr_count($response->getContent(), 'alt="Uji IRN Unik"'));
        // Varian TIDAK dirender sebagai card/alt tersendiri
        $this->assertSame(0, substr_count($response->getContent(), 'alt="Uji IRN Albino Unik"'));
        // Nama varian hadir tepat sekali di data JSON modal (@js($variantData))
        $this->assertSame(1, substr_count($response->getContent(), 'Uji IRN Albino Unik'));
    }

    public function test_admin_index_renders_collection_table(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('admin.collections.index'));

        $response->assertStatus(200);
        $response->assertSee('Manajemen Koleksi Burung');
        $response->assertSee('Foto Tersimpan');
        $response->assertSee('Upload Foto Baru');

        $admin->delete();
    }

    public function test_admin_store_accepts_multiple_gallery_images(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->postJson(route('admin.collections.store'), [
                'name' => 'Uji Multi Foto',
                'category' => 'uji-varian',
                'cloudinary_urls' => [
                    'https://res.cloudinary.com/demo/image/upload/foto1.jpg',
                    'https://res.cloudinary.com/demo/image/upload/foto2.jpg',
                    'https://res.cloudinary.com/demo/image/upload/foto3.jpg',
                ],
            ])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $col = Collection::where('name', 'Uji Multi Foto')->first();
        $this->assertCount(3, $col->images);
        // Cover = foto pertama
        $this->assertSame('https://res.cloudinary.com/demo/image/upload/foto1.jpg', $col->image_path);
    }

    public function test_admin_update_removes_photo_by_index_and_syncs_cover(): void
    {
        $admin = $this->makeAdmin();
        $col = $this->makeCollection([
            'name' => 'Uji Hapus Foto',
            'images' => [
                'https://res.cloudinary.com/demo/image/upload/cover.jpg',
                'https://res.cloudinary.com/demo/image/upload/foto2.jpg',
                'https://res.cloudinary.com/demo/image/upload/foto3.jpg',
            ],
        ]);

        // Hapus foto indeks 0 (cover) — cover naik ke foto berikutnya
        $this->actingAs($admin)
            ->putJson(route('admin.collections.update', ['collection' => $col]), [
                'name' => 'Uji Hapus Foto',
                'category' => 'uji-varian',
                'remove_images' => ['0'],
            ])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $fresh = $col->refresh();
        $this->assertCount(2, $fresh->images);
        $this->assertSame('https://res.cloudinary.com/demo/image/upload/foto2.jpg', $fresh->image_path);
    }

    public function test_public_page_shows_photo_badge_and_modal_gallery(): void
    {
        $this->makeCollection([
            'name' => 'Uji Badge Foto',
            'images' => [
                'https://res.cloudinary.com/demo/image/upload/cover.jpg',
                'https://res.cloudinary.com/demo/image/upload/foto2.jpg',
            ],
        ]);
        Cache::forget('public.collections.v2');

        $response = $this->get('/collections');
        $response->assertStatus(200);
        $content = $response->getContent();
        // Badge "+N Foto" tampil di card (teks bergantung locale aktif)
        $this->assertTrue(
            str_contains($content, '+2 Foto') || str_contains($content, '+2 Photos'),
            'Badge foto tidak dirender.'
        );
        // URL galeri masuk data JSON modal
        $this->assertStringContainsString('foto2.jpg', $content);

        Cache::forget('public.collections.v2');
    }

    public function test_public_variant_carries_multiple_photos_for_lightbox(): void
    {
        $parent = $this->makeCollection(['name' => 'Uji Induk Varian Foto']);
        $this->makeCollection([
            'name' => 'Uji Varian Multi Foto',
            'parent_id' => $parent->id,
            'images' => [
                'https://res.cloudinary.com/demo/image/upload/var-1.jpg',
                'https://res.cloudinary.com/demo/image/upload/var-2.jpg',
                'https://res.cloudinary.com/demo/image/upload/var-3.jpg',
            ],
        ]);

        $response = $this->get('/collections');
        $response->assertStatus(200);
        $content = $response->getContent();

        // Data JSON modal memuat semua foto varian (thumbnail + full untuk lightbox)
        $this->assertStringContainsString('var-1.jpg', $content);
        $this->assertStringContainsString('var-2.jpg', $content);
        $this->assertStringContainsString('var-3.jpg', $content);
        // URL zoom w_1600 untuk lightbox varian
        $this->assertStringContainsString('w_1600', $content);
    }
}
