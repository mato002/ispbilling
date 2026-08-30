<?php

namespace Tests\Feature\Navigation;

use App\Models\User;
use App\Support\Discovery\FeatureRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_feature_registry_finds_form_controls_for_super_admin(): void
    {
        $this->actingAs($this->superAdmin());

        $matches = app(FeatureRegistry::class)->search('form controls');

        $this->assertNotEmpty($matches);
        $this->assertTrue(
            collect($matches)->contains(fn (array $entry) => $entry['label'] === 'Form controls'),
        );
    }

    public function test_feature_registry_finds_customers_for_isp_user(): void
    {
        $this->actingAs($this->ispAdmin());

        $matches = app(FeatureRegistry::class)->search('customers');

        $this->assertNotEmpty($matches);
        $this->assertTrue(
            collect($matches)->contains(fn (array $entry) => $entry['label'] === 'Customers'),
        );
    }

    public function test_feature_discovery_search_endpoint_returns_matches(): void
    {
        $this->actingAs($this->superAdmin());

        $response = $this->getJson(route('feature-discovery.search', ['q' => 'finance']));

        $response->assertOk();
        $response->assertJsonStructure(['results' => [['id', 'label', 'path', 'url']]]);
        $this->assertTrue(
            collect($response->json('results'))->contains(fn (array $entry) => $entry['label'] === 'Finance'),
        );
    }

    public function test_command_palette_is_rendered_in_admin_dashboard(): void
    {
        $response = $this->actingAs($this->superAdmin())->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('id="pb-command-palette-title"', false);
        $response->assertSee('window.__pbDiscovery', false);
    }

    protected function superAdmin(): User
    {
        return User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    protected function ispAdmin(): User
    {
        return User::factory()->create([
            'role' => 'isp_admin',
            'is_active' => true,
        ]);
    }
}
