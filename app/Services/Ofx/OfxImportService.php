<?php

namespace App\Services\Ofx;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\User;
use App\Services\Transactions\TransactionCategorizer;
use Illuminate\Http\UploadedFile;

class OfxImportService
{
    public function __construct(
        private readonly OfxParser $parser,
        private readonly TransactionCategorizer $categorizer,
    ) {}

    public function import(User $user, UploadedFile $file): array
    {
        $data = $this->parser->parse($file->get());

        $accountData = $data['account'];
        $accountNumber = $accountData['number'] ?: 'ofx-' . md5($file->getClientOriginalName());
        $account = BankAccount::firstOrCreate(
            [
                'user_id' => $user->id,
                'account_number' => $accountNumber,
            ],
            [
                'name' => $accountData['name'] ?? sprintf('Conta %s', $accountNumber),
                'institution' => $accountData['institution'],
                'account_type' => $accountData['type'] ?? 'checking',
                'currency' => $accountData['currency'] ?? 'BRL',
                'metadata' => [
                    'ofx' => [
                        'bank_id' => $accountData['bank_id'] ?? null,
                        'source' => 'import',
                    ],
                ],
            ]
        );

        $created = 0;
        $skipped = 0;

        foreach ($data['transactions'] as $transaction) {
            /** @var BankTransaction|null $existing */
            $existing = $account->transactions()
                ->where('external_id', $transaction['external_id'])
                ->first();

            if ($existing) {
                $skipped++;
                continue;
            }

            $suggestedCategory = $this->categorizer->suggestCategory($user, $transaction['description']);

            $account->transactions()->create([
                'description' => $transaction['description'] ?: 'Transação OFX',
                'amount' => $transaction['amount'],
                'type' => $transaction['type'],
                'occurred_at' => $transaction['occurred_at'],
                'external_id' => $transaction['external_id'],
                'transaction_category_id' => $suggestedCategory['id'] ?? null,
                'category' => $suggestedCategory['name'] ?? null,
                'metadata' => [
                    'ofx' => [
                        'raw_type' => $transaction['raw_type'],
                    ],
                ],
            ]);

            $created++;
        }

        $totals = $account->transactions()
            ->selectRaw("SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END) as total_credit")
            ->selectRaw("SUM(CASE WHEN type = 'debit' THEN ABS(amount) ELSE 0 END) as total_debit")
            ->first();

        $credits = (float) (optional($totals)->total_credit ?? 0);
        $debits = (float) (optional($totals)->total_debit ?? 0);
        $balance = $credits - $debits;

        $account->forceFill(['balance' => $balance])->save();

        return [
            'account' => $account,
            'created' => $created,
            'skipped' => $skipped,
            'total' => count($data['transactions']),
        ];
    }
}
