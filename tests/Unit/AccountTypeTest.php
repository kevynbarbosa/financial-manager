<?php

use App\Enums\AccountType;

it('returns options with value and label', function () {
    expect(AccountType::options())->toBe([
        ['value' => 'checking', 'label' => 'Conta corrente'],
        ['value' => 'savings', 'label' => 'Conta poupança'],
        ['value' => 'credit', 'label' => 'Cartão de crédito'],
        ['value' => 'investment', 'label' => 'Investimentos'],
        ['value' => 'business', 'label' => 'Conta empresarial'],
    ]);
});
