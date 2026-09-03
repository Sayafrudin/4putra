<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\User;
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
        // Varian hanya dirender di grid induk, bukan sebagai card tersendiri
        $this->assertSame(1, substr_count($response->getContent(), 'alt="Uji IRN Unik"'));
        $this->assertSame(1, substr_count($response->getContent(), 'alt="Uji IRN Albino Unik"'));
    }
}
