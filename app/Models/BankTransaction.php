<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class BankTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_account_id',
        'description',
        'amount',
        'type',
        'occurred_at',
        'category',
        'external_id',
        'transaction_category_id',
        'metadata',
        'is_transfer',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'occurred_at' => 'datetime',
        'is_transfer' => 'boolean',
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function account(): BelongsTo
    {
        return $this->bankAccount();
    }

    public function categoryRelation(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class, 'transaction_category_id');
    }

    protected function description(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $this->normalizeDescription($value)
        );
    }

    private function normalizeDescription(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = preg_replace('/\s+/', ' ', trim($value));

        return $normalized ?? '';
    }
}
