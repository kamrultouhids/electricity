<?php

namespace App\Enums;

enum UserTypeEnum: string
{
    case Admin = 'admin';
    case User = 'user';

    /**
     * Get a human-readable label for the user type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::User => 'User',
        };
    }

    /**
     * Get all enum values as an array.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
