<?php

namespace App\Actions\BankAccounts;

use App\Http\Requests\UpdateBankAccountRequest;
use App\Models\BankAccount;
use Illuminate\Http\RedirectResponse;

class UpdateBankAccount
{
    public function __invoke(UpdateBankAccountRequest $request, BankAccount $account): RedirectResponse
    {
        $account->update($request->validated());

        return redirect()
            ->route('accounts.index')
            ->with('success', 'Conta atualizada com sucesso.');
    }
}
