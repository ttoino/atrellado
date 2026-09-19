<?php

namespace App\Enums;

enum ProviderType: string
{
    case GITHUB = 'github';
    case GOOGLE = 'google';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
};
