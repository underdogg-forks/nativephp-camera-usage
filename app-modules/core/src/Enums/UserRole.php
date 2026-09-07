<?php

namespace Modules\Core\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';
    case ASSIST = 'assist';
    case CUSTOMER_ADMIN = 'client_admin';
    case CUSTOMER = 'client';

    public static function elevated(): array
    {
        return [self::SUPER_ADMIN->value, self::ADMIN->value, self::ASSIST->value];
    }

    public static function nonAdmin(): array
    {
        return [self::CUSTOMER_ADMIN->value, self::CUSTOMER->value];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
