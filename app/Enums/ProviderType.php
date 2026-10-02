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

    /** @return array<self> */
    public static function configured(): array
    {
        return array_values(array_filter(self::cases(), fn (self $provider) => $provider->isConfigured()));
    }

    public function isConfigured(): bool
    {
        return filled(config("services.{$this->value}.client_id"))
            && filled(config("services.{$this->value}.client_secret"));
    }

    public function label(): string
    {
        return match ($this) {
            self::GITHUB => 'GitHub',
            self::GOOGLE => 'Google',
        };
    }
}
