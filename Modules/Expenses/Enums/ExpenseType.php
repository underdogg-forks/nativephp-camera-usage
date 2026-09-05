<?php

namespace Modules\Expenses\Enums;

enum ExpenseType: string
{
    case FIXED = 'fixed';
    case ONE_TIME = 'one_time';
    case RECURRING = 'recurring';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::FIXED => 'Fixed',
            self::ONE_TIME => 'One Time',
            self::RECURRING => 'Recurring',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::FIXED => 'sky',
            self::ONE_TIME => 'gray',
            self::RECURRING => 'blue',
        };
    }
}
