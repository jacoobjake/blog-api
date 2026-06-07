<?php

namespace Tests\Unit\Enums;

use App\Enums\AssetType;
use Tests\TestCase;

class AssetTypeTest extends TestCase
{
    public function test_image_accepted_mime_types(): void
    {
        $this->assertSame(
            ['image/jpeg', 'image/png'],
            AssetType::Image->acceptedMimeTypes(),
        );
    }

    public function test_image_validation_rules_contain_required_and_file(): void
    {
        $rules = AssetType::Image->validationRules();

        $this->assertContains('required', $rules);
        $this->assertContains('file', $rules);
    }

    public function test_image_validation_rules_contain_mimetypes_constraint(): void
    {
        $rules = AssetType::Image->validationRules();

        $mimeRule = collect($rules)->first(fn($r) => str_starts_with($r, 'mimetypes:'));
        $this->assertNotNull($mimeRule);
        $this->assertStringContainsString('image/jpeg', $mimeRule);
        $this->assertStringContainsString('image/png', $mimeRule);
    }

    public function test_get_validation_rules_resolves_image_type(): void
    {
        $rules = AssetType::getValidationRules('image');

        $this->assertContains('required', $rules);
        $this->assertContains('file', $rules);
    }

    public function test_get_validation_rules_falls_back_for_invalid_type(): void
    {
        $rules = AssetType::getValidationRules('invalid_type');

        $this->assertSame(['required', 'file'], $rules);
    }
}
