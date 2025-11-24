<?php

namespace App\Services\Transactions;

use App\Models\BankTransaction;
use App\Models\User;

class TransactionAuthorizationService
{
    public function ensureOwnsTransaction(BankTransaction $transaction, ?User $user): void
    {
        abort_if(! $user, 403, 'Usuário não autenticado.');

        $transaction->loadMissing('account:id,user_id');

        abort_if($transaction->account?->user_id !== $user->id, 403, 'Operação não permitida.');
    }
}
