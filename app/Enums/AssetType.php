<?php

namespace App\Enums;

enum AssetType: string
{
    case Image = 'image';
    // case Video = 'video';
    // case Audio = 'audio';
    // case Document = 'document';

    public function acceptedMimeTypes(): array
    {
        return match ($this) {
            self::Image => ['image/jpeg', 'image/png'],
        // self::Video => ['video/mp4', 'video/x-matroska'],
        // self::Audio => ['audio/mpeg', 'audio/wav'],
        // self::Document => ['application/pdf', 'application/msword'],
        };
    }

    public function validationRules(): array
    {
        return match ($this) {
            self::Image => ['required', 'file', 'mimetypes:' . implode(',', $this->acceptedMimeTypes()), 'max:10240'],
        };
    }

    public static function getValidationRules(string|self $type): array
    {
        $type = self::tryFrom($type);

        if ($type) {
            return $type->validationRules();
        }

        return ['required', 'file'];
    }
}
