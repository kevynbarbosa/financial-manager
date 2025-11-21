<?php

namespace App\Enums\Concerns;

use App\Enums\Contracts\OptionableEnum;

trait HasOptions
{
    /**
        * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            static::cases()
        );
    }
}
