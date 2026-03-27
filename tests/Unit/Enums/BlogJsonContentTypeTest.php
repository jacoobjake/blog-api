<?php

namespace Tests\Unit\Enums;

use App\Enums\BlogJsonContentType;
use Tests\TestCase;

class BlogJsonContentTypeTest extends TestCase
{
    public function test_compressed_base64_content_value_type_is_string(): void
    {
        $this->assertSame('string', BlogJsonContentType::COMPRESSED_BASE64->contentValueType());
    }

    public function test_compressed_base64_validation_rule(): void
    {
        $rules = BlogJsonContentType::COMPRESSED_BASE64->contentValidationRule();

        $this->assertContains('required', $rules);
        $this->assertContains('string', $rules);
    }

    public function test_get_content_value_type_resolves_known_type(): void
    {
        $this->assertSame('string', BlogJsonContentType::getContentValueType('compressed_base64'));
    }

    public function test_get_content_value_type_falls_back_for_unknown_type(): void
    {
        $this->assertSame('string', BlogJsonContentType::getContentValueType('unknown'));
    }

    public function test_get_content_validation_rule_resolves_known_type(): void
    {
        $rules = BlogJsonContentType::getContentValidationRule('compressed_base64');

        $this->assertContains('required', $rules);
        $this->assertContains('string', $rules);
    }

    public function test_get_content_validation_rule_returns_empty_for_unknown_type(): void
    {
        $this->assertSame([], BlogJsonContentType::getContentValidationRule('unknown'));
    }
}
