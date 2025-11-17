<?php

namespace App\Services\Ofx;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class OfxImportService
{
    public function __construct(private readonly OfxParser $parser) {}

    public function import(User $user, UploadedFile $file): array
    {
        $data = $this->parser->parse($file->get());

        $accountData = $data['account'];
        $accountNumber = $accountData['number'] ?: 'ofx-' . md5($file->getClientOriginalName());

        logger($accountNumber);
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

            $account->transactions()->create([
                'description' => $transaction['description'] ?: 'Transação OFX',
                'amount' => $transaction['amount'],
                'type' => $transaction['type'],
                'occurred_at' => $transaction['occurred_at'],
                'external_id' => $transaction['external_id'],
                'metadata' => [
                    'ofx' => [
                        'raw_type' => $transaction['raw_type'],
                    ],
                ],
            ]);

            $created++;
        }

        return [
            'account' => $account,
            'created' => $created,
            'skipped' => $skipped,
            'total' => count($data['transactions']),
        ];
    }
}
