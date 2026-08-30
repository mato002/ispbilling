<?php

namespace Tests\Unit;

use App\Support\FormSettingsManager;
use App\Support\FormSettingsService;
use App\Models\ISP;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormSettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolved_fields_use_registry_defaults_when_no_overrides(): void
    {
        $service = app(FormSettingsService::class);
        $fields = $service->resolvedFields('customer', 1);

        $this->assertTrue($fields['full_name']['required']);
        $this->assertTrue($fields['full_name']['visible']);
        $this->assertFalse($fields['email']['required']);
    }

    public function test_manager_can_hide_optional_field(): void
    {
        $isp = ISP::query()->create([
            'name' => 'Test ISP',
            'slug' => 'test-isp-'.uniqid(),
            'company_name' => 'Test Co',
            'email' => 'test@example.com',
            'phone' => '0700000000',
            'is_active' => true,
        ]);
        $manager = app(FormSettingsManager::class);
        $manager->ensureForms($isp->id, 'isp');

        $manager->save($isp->id, 'isp', [
            'customer' => [
                'is_active' => true,
                'fields' => [
                    'notes' => [
                        'visibility' => 'hidden',
                        'requirement' => 'optional',
                        'read_only' => false,
                        'default_value' => '',
                    ],
                ],
            ],
        ]);

        $fields = app(FormSettingsService::class)->resolvedFields('customer', $isp->id);
        $this->assertFalse($fields['notes']['visible']);
        $this->assertTrue($fields['notes']['hidden']);
    }
}
