<?php

namespace App\Http\Controllers;

use App\Actions\BankAccounts\ImportBankAccountOfx;
use App\Actions\BankAccounts\ListBankAccounts;
use App\Actions\BankAccounts\UpdateBankAccount;
use App\Enums\AccountType;
use App\Http\Requests\ImportOfxRequest;
use App\Http\Requests\UpdateBankAccountRequest;
use App\Models\BankAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InertiaUI\Modal\Modal as ModalResponse;

class BankAccountController extends Controller
{
    public function index(Request $request, ListBankAccounts $listBankAccounts): Response
    {
        return Inertia::render('accounts/Index', $listBankAccounts($request));
    }

    public function importOfx(ImportOfxRequest $request, ImportBankAccountOfx $importBankAccountOfx): RedirectResponse
    {
        return $importBankAccountOfx($request);
    }

    public function edit(BankAccount $account): ModalResponse
    {
        return Inertia::modal('accounts/modals/EditBankAccount', [
            'account' => [
                'id' => $account->id,
                'name' => $account->name,
                'institution' => $account->institution,
                'account_type' => $account->account_type,
                'account_number' => $account->account_number,
                'currency' => $account->currency,
            ],
            'accountTypes' => AccountType::options(),
        ])->baseRoute('accounts.index');
    }

    public function update(UpdateBankAccountRequest $request, BankAccount $account, UpdateBankAccount $updateBankAccount): RedirectResponse
    {
        return $updateBankAccount($request, $account);
    }
}
