<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use App\Enums\Contracts\OptionableEnum;

enum AccountType: string implements OptionableEnum
{
    use HasOptions;

    case Checking = 'checking';
    case Savings = 'savings';
    case Credit = 'credit';
    case Investment = 'investment';
    case Business = 'business';

    public function label(): string
    {
        return match ($this) {
            self::Checking => 'Conta corrente',
            self::Savings => 'Conta poupança',
            self::Credit => 'Cartão de crédito',
            self::Investment => 'Investimentos',
            self::Business => 'Conta empresarial',
        };
    }
}
