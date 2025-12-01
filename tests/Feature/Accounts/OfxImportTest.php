<?php

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\TransactionCategory;
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

it('auto-assigns category by matching description for the user', function () {
    $user = User::factory()->create();
    $category = TransactionCategory::factory()->for($user)->create();
    $trainingAccount = BankAccount::factory()->for($user)->create([
        'account_number' => 'TRAIN-001',
    ]);

    BankTransaction::factory()->for($trainingAccount)->create([
        'description' => 'Pagamento cartão',
        'transaction_category_id' => $category->id,
        'category' => $category->name,
        'occurred_at' => now()->subDay(),
    ]);

    actingAs($user);

    $file = UploadedFile::fake()->createWithContent('extrato.ofx', sampleOfxContent());

    $this->post(route('accounts.import-ofx'), [
        'ofx_file' => $file,
    ])->assertRedirect(route('accounts.index'));

    $account = BankAccount::where('user_id', $user->id)->where('account_number', '000111')->first();

    $this->assertDatabaseHas('bank_transactions', [
        'bank_account_id' => $account?->id,
        'external_id' => 'DEF456',
        'transaction_category_id' => $category->id,
        'category' => $category->name,
    ]);
});

it('auto-assigns ifood category when description starts with IFD*', function () {
    $user = User::factory()->create();
    $category = TransactionCategory::factory()->for($user)->state(['name' => 'Ifood'])->create();

    actingAs($user);

    $file = UploadedFile::fake()->createWithContent('ifood.ofx', sampleOfxIfoodContent());

    $this->post(route('accounts.import-ofx'), [
        'ofx_file' => $file,
    ])->assertRedirect(route('accounts.index'));

    $account = BankAccount::where('user_id', $user->id)->where('account_number', 'IFDACC')->first();

    $this->assertDatabaseHas('bank_transactions', [
        'bank_account_id' => $account?->id,
        'external_id' => 'IF001',
        'transaction_category_id' => $category->id,
        'category' => $category->name,
    ]);
});

it('imports OFX files from Nubank credit card statements', function () {
    $user = User::factory()->create();
    actingAs($user);

    $file = UploadedFile::fake()->createWithContent('nubank.ofx', sampleNubankOfxContent());

    $this->post(route('accounts.import-ofx'), [
        'ofx_file' => $file,
    ])->assertRedirect(route('accounts.index'));

    $account = BankAccount::where('user_id', $user->id)
        ->where('account_number', '5a259310-a12b-419c-80a3-73593b58786e')
        ->first();

    expect($account)->not->toBeNull();

    $this->assertDatabaseHas('bank_transactions', [
        'bank_account_id' => $account->id,
        'external_id' => 'fit-1',
        'description' => 'Yellow Burgers & Dogs',
    ]);
});

it('maps OTHER type to debit when amount is negative', function () {
    $user = User::factory()->create();
    actingAs($user);

    $file = UploadedFile::fake()->createWithContent('other-negative.ofx', sampleOtherNegativeContent());

    $this->post(route('accounts.import-ofx'), [
        'ofx_file' => $file,
    ])->assertRedirect(route('accounts.index'));

    $account = BankAccount::where('user_id', $user->id)
        ->where('account_number', 'OTHER001')
        ->first();

    expect($account)->not->toBeNull();

    $this->assertDatabaseHas('bank_transactions', [
        'bank_account_id' => $account->id,
        'external_id' => '010472',
        'type' => 'debit',
        'description' => 'DEBITO VISA ELECTRON BRASIL        29/11 FrutaGurtFrozen',
    ]);
});

it('imports Nubank OFX files with duplicated FITIDs by hashing date and amount', function () {
    $user = User::factory()->create();
    actingAs($user);

    $file = UploadedFile::fake()->createWithContent('nubank-dup.ofx', sampleNubankDuplicateFitIdContent());

    $this->post(route('accounts.import-ofx'), [
        'ofx_file' => $file,
    ])->assertRedirect(route('accounts.index'));

    $account = BankAccount::where('user_id', $user->id)
        ->where('account_number', '5a259310-a12b-419c-80a3-73593b58786e')
        ->first();

    expect($account)->not->toBeNull();

    $expectedDuplicatedHash = md5('fit-dup|20251102000000[-3:BRT]|-2.50|IOF sobre compra internacional');

    $this->assertDatabaseHas('bank_transactions', [
        'bank_account_id' => $account->id,
        'external_id' => 'fit-dup',
        'description' => 'Compra internacional',
    ]);

    $this->assertDatabaseHas('bank_transactions', [
        'bank_account_id' => $account->id,
        'external_id' => 'fit-dup-'.$expectedDuplicatedHash,
        'description' => 'IOF sobre compra internacional',
    ]);
});

function sampleOfxContent(): string
{
    return <<<'OFX'
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

function sampleNubankOfxContent(): string
{
    return <<<'OFX'
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
<STATUS>
<CODE>0</CODE>
<SEVERITY>INFO</SEVERITY>
</STATUS>
<DTSERVER>20251103142506[0:GMT]</DTSERVER>
<LANGUAGE>POR</LANGUAGE>
<FI>
<ORG>NU PAGAMENTOS S.A.</ORG>
<FID>260</FID>
</FI>
</SONRS>
</SIGNONMSGSRSV1>
<CREDITCARDMSGSRSV1>
<CCSTMTTRNRS>
<TRNUID>1001</TRNUID>
<STATUS>
<CODE>0</CODE>
<SEVERITY>INFO</SEVERITY>
</STATUS>
<CCSTMTRS>
<CURDEF>BRL</CURDEF>
<CCACCTFROM>
<ACCTID>5a259310-a12b-419c-80a3-73593b58786e</ACCTID>
</CCACCTFROM>
<BANKTRANLIST>
<DTSTART>20251031000000[-3:BRT]</DTSTART>
<DTEND>20251130000000[-3:BRT]</DTEND>
<STMTTRN>
<TRNTYPE>DEBIT</TRNTYPE>
<DTPOSTED>20251102000000[-3:BRT]</DTPOSTED>
<TRNAMT>-97.00</TRNAMT>
<FITID>fit-1</FITID>
<MEMO>Yellow Burgers & Dogs</MEMO>
</STMTTRN>
<STMTTRN>
<TRNTYPE>DEBIT</TRNTYPE>
<DTPOSTED>20251102000000[-3:BRT]</DTPOSTED>
<TRNAMT>-10.49</TRNAMT>
<FITID>fit-2</FITID>
<MEMO>Oxxo Sorvete Neves</MEMO>
</STMTTRN>
</BANKTRANLIST>
</CCSTMTRS>
</CCSTMTTRNRS>
</CREDITCARDMSGSRSV1>
</OFX>
OFX;
}

function sampleNubankDuplicateFitIdContent(): string
{
    return <<<'OFX'
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
<STATUS>
<CODE>0</CODE>
<SEVERITY>INFO</SEVERITY>
</STATUS>
<DTSERVER>20251103142506[0:GMT]</DTSERVER>
<LANGUAGE>POR</LANGUAGE>
<FI>
<ORG>NU PAGAMENTOS S.A.</ORG>
<FID>260</FID>
</FI>
</SONRS>
</SIGNONMSGSRSV1>
<CREDITCARDMSGSRSV1>
<CCSTMTTRNRS>
<TRNUID>1001</TRNUID>
<STATUS>
<CODE>0</CODE>
<SEVERITY>INFO</SEVERITY>
</STATUS>
<CCSTMTRS>
<CURDEF>BRL</CURDEF>
<CCACCTFROM>
<ACCTID>5a259310-a12b-419c-80a3-73593b58786e</ACCTID>
</CCACCTFROM>
<BANKTRANLIST>
<DTSTART>20251031000000[-3:BRT]</DTSTART>
<DTEND>20251130000000[-3:BRT]</DTEND>
<STMTTRN>
<TRNTYPE>DEBIT</TRNTYPE>
<DTPOSTED>20251102000000[-3:BRT]</DTPOSTED>
<TRNAMT>-97.00</TRNAMT>
<FITID>fit-dup</FITID>
<MEMO>Compra internacional</MEMO>
</STMTTRN>
<STMTTRN>
<TRNTYPE>DEBIT</TRNTYPE>
<DTPOSTED>20251102000000[-3:BRT]</DTPOSTED>
<TRNAMT>-2.50</TRNAMT>
<FITID>fit-dup</FITID>
<MEMO>IOF sobre compra internacional</MEMO>
</STMTTRN>
</BANKTRANLIST>
</CCSTMTRS>
</CCSTMTTRNRS>
</CREDITCARDMSGSRSV1>
</OFX>
OFX;
}

function sampleOtherNegativeContent(): string
{
    return <<<'OFX'
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
        <FID>321</FID>
      </FI>
    </SONRS>
  </SIGNONMSGSRSV1>
  <BANKMSGSRSV1>
    <STMTTRNRS>
      <STMTRS>
        <CURDEF>BRL</CURDEF>
        <BANKACCTFROM>
          <BANKID>321</BANKID>
          <ACCTID>OTHER001</ACCTID>
          <ACCTTYPE>CHECKING</ACCTTYPE>
        </BANKACCTFROM>
        <BANKTRANLIST>
          <STMTTRN>
            <TRNTYPE>OTHER</TRNTYPE>
            <DTPOSTED>20251201000000[-3:GMT]</DTPOSTED>
            <TRNAMT>-46.00</TRNAMT>
            <FITID>010472</FITID>
            <CHECKNUM>010472</CHECKNUM>
            <PAYEEID>0</PAYEEID>
            <MEMO>DEBITO VISA ELECTRON BRASIL        29/11 FrutaGurtFrozen</MEMO>
          </STMTTRN>
        </BANKTRANLIST>
      </STMTRS>
    </STMTTRNRS>
  </BANKMSGSRSV1>
</OFX>
OFX;
}

function sampleOfxIfoodContent(): string
{
    return <<<'OFX'
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
        <FID>987</FID>
      </FI>
    </SONRS>
  </SIGNONMSGSRSV1>
  <BANKMSGSRSV1>
    <STMTTRNRS>
      <STMTRS>
        <CURDEF>BRL</CURDEF>
        <BANKACCTFROM>
          <BANKID>987</BANKID>
          <ACCTID>IFDACC</ACCTID>
          <ACCTTYPE>CHECKING</ACCTTYPE>
        </BANKACCTFROM>
        <BANKTRANLIST>
          <STMTTRN>
            <TRNTYPE>DEBIT</TRNTYPE>
            <DTPOSTED>20250111120000</DTPOSTED>
            <TRNAMT>-79.90</TRNAMT>
            <FITID>IF001</FITID>
            <MEMO>IFD*IFOOD PEDIDO</MEMO>
          </STMTTRN>
        </BANKTRANLIST>
      </STMTRS>
    </STMTTRNRS>
  </BANKMSGSRSV1>
</OFX>
OFX;
}
