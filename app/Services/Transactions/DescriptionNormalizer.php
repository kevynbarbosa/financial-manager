<?php

namespace App\Services\Transactions;

class DescriptionNormalizer
{
    public static function normalize(string $description): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim($description));

        return mb_strtolower($normalized ?? '');
    }
}
