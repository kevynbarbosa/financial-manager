<?php

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\User;
use Illuminate\Http\UploadedFile;

use function Pest\Laravel\actingAs;

it('imports an OFX file, creating the account and skipping duplicates', function () {
    $user = User::factory()->create();
    actingAs($user);

    $file = UploadedFile::fake()->createWithContent('extrato.ofx', sampleOfxContent());

    $response = $this->post(route('accounts.import-ofx'), [
        'ofx_file' => $file,
    ]);

    $response->assertRedirect(route('accounts.index'));

    $account = BankAccount::where('user_id', $user->id)->where('account_number', '000111')->first();
    expect($account)->not->toBeNull();

    $this->assertDatabaseHas('bank_transactions', [
        'bank_account_id' => $account->id,
        'external_id' => 'ABC123',
        'description' => 'Depósito salário',
    ]);

    $this->assertDatabaseHas('bank_transactions', [
        'bank_account_id' => $account->id,
        'external_id' => 'DEF456',
        'description' => 'Pagamento cartão',
    ]);
});

it('does not duplicate transactions with the same FITID', function () {
    $user = User::factory()->create();
    $account = BankAccount::factory()->for($user)->create([
        'account_number' => '000111',
    ]);
    BankTransaction::factory()->for($account)->create([
        'external_id' => 'ABC123',
        'amount' => 1500,
        'type' => 'credit',
        'description' => 'Depósito salário',
    ]);

    actingAs($user);

    $file = UploadedFile::fake()->createWithContent('extrato.ofx', sampleOfxContent());

    $this->post(route('accounts.import-ofx'), [
        'ofx_file' => $file,
    ])->assertRedirect(route('accounts.index'));

    expect($account->transactions()->count())->toBe(2); // uma existente + uma nova
    $this->assertDatabaseHas('bank_transactions', [
        'bank_account_id' => $account->id,
        'external_id' => 'DEF456',
        'description' => 'Pagamento cartão',
    ]);
});

function sampleOfxContent(): string
{
    return <<<OFX
OFXHEADER:100
DATA:OFXSGML
VERSION:102
SECURITY:NONE
ENCODING:USASCII
CHARSET:1252
COMPRESSION:NONE
OLDFILEUID:NONE
NEWFILEUID:NONE

<OFX>
  <SIGNONMSGSRSV1>
    <SONRS>
      <FI>
        <ORG>Banco Codex</ORG>
        <FID>123</FID>
      </FI>
    </SONRS>
  </SIGNONMSGSRSV1>
  <BANKMSGSRSV1>
    <STMTTRNRS>
      <STMTRS>
        <CURDEF>BRL</CURDEF>
        <BANKACCTFROM>
          <BANKID>123</BANKID>
          <ACCTID>000111</ACCTID>
          <ACCTTYPE>CHECKING</ACCTTYPE>
        </BANKACCTFROM>
        <BANKTRANLIST>
          <STMTTRN>
            <TRNTYPE>CREDIT</TRNTYPE>
            <DTPOSTED>20250101120000</DTPOSTED>
            <TRNAMT>1500.00</TRNAMT>
            <FITID>ABC123</FITID>
            <MEMO>Depósito salário</MEMO>
          </STMTTRN>
          <STMTTRN>
            <TRNTYPE>DEBIT</TRNTYPE>
            <DTPOSTED>20250105100000</DTPOSTED>
            <TRNAMT>-245.90</TRNAMT>
            <FITID>DEF456</FITID>
            <MEMO>Pagamento cartão</MEMO>
          </STMTTRN>
        </BANKTRANLIST>
      </STMTRS>
    </STMTTRS>
  </BANKMSGSRSV1>
</OFX>
OFX;
}
