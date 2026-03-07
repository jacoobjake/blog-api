<?php

namespace App\Enums;

enum BlogJsonContentType: string
{
    case COMPRESSED_BASE64 = 'compressed_base64';

    public function contentValueType(): string
    {
        return match ($this) {
            BlogJsonContentType::COMPRESSED_BASE64 => 'string',
        };
    }
    public function contentValidationRule(): array|string
    {
        return match ($this) {
            BlogJsonContentType::COMPRESSED_BASE64 => ['required', 'string'],
        };
    }

    public static function getContentValueType(string $type): string
    {
        $type = BlogJsonContentType::tryFrom($type);
        return $type?->contentValueType() ?? 'string';
    }

    public static function getContentValidationRule(string $type): array|string
    {
        $type = BlogJsonContentType::tryFrom($type);
        return $type?->contentValidationRule() ?? [];
    }
}
