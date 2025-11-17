<?php

namespace App\Services\Ofx;

use Carbon\Carbon;
use Illuminate\Support\Str;
use RuntimeException;
use SimpleXMLElement;

class OfxParser
{
    public function parse(string $contents): array
    {
        $body = $this->extractXmlBody($contents);
        $xml = simplexml_load_string($body);

        if (! $xml instanceof SimpleXMLElement) {
            throw new RuntimeException('Não foi possível interpretar o arquivo OFX enviado.');
        }

        $statement = $xml->BANKMSGSRSV1->STMTTRNRS->STMTRS
            ?? $xml->CREDITCARDMSGSRSV1->CCSTMTTRNRS->CCSTMTRS
            ?? null;

        if (! $statement) {
            throw new RuntimeException('O arquivo OFX não contém informações bancárias válidas.');
        }

        $accountData = $this->parseAccount($xml, $statement);
        $transactions = $this->parseTransactions($statement);

        return [
            'account' => $accountData,
            'transactions' => $transactions,
        ];
    }

    protected function extractXmlBody(string $contents): string
    {
        $start = stripos($contents, '<OFX');

        if ($start === false) {
            throw new RuntimeException('Arquivo OFX inválido.');
        }

        $body = substr($contents, $start);

        $body = $this->closeUnterminatedTags($body);
        $body = $this->escapeInvalidEntities($body);

        if (! is_string($body)) {
            throw new RuntimeException('Não foi possível normalizar o arquivo OFX.');
        }

        return $body;
    }

    protected function closeUnterminatedTags(string $body): string
    {
        $lines = preg_split('/\r?\n/', $body);

        foreach ($lines as $index => $line) {
            if (! preg_match('/^<([A-Z0-9_]+)>(.*)$/i', $line, $matches)) {
                continue;
            }

            $tag = $matches[1];
            $value = $matches[2];

            if ($value === '' || str_contains($value, '</')) {
                continue;
            }

            $lines[$index] = sprintf('<%s>%s</%s>', $tag, $value, $tag);
        }

        return implode(PHP_EOL, $lines);
    }

    protected function escapeInvalidEntities(string $body): string
    {
        return preg_replace('/&(?![a-zA-Z]+;)/', '&amp;', $body);
    }

    protected function parseAccount(SimpleXMLElement $xml, SimpleXMLElement $statement): array
    {
        $fi = $xml->SIGNONMSGSRSV1->SONRS->FI ?? null;
        $account = $statement->BANKACCTFROM ?? $statement->CCACCTFROM ?? null;

        $accountNumber = $account?->ACCTID ? trim((string) $account->ACCTID) : null;
        $accountType = $account?->ACCTTYPE ? strtolower((string) $account->ACCTTYPE) : null;
        $bankId = $account?->BANKID ? trim((string) $account->BANKID) : null;
        $institution = $fi?->ORG ? trim((string) $fi->ORG) : null;

        return [
            'number' => $accountNumber,
            'type' => $accountType ?: 'checking',
            'bank_id' => $bankId,
            'institution' => $institution,
            'currency' => $statement->CURDEF ? trim((string) $statement->CURDEF) : 'BRL',
            'name' => $account?->ACCTID ? 'Conta ' . trim((string) $account->ACCTID) : null,
        ];
    }

    protected function parseTransactions(SimpleXMLElement $statement): array
    {
        $transactions = [];
        $transactionNodes = $statement->BANKTRANLIST->STMTTRN ?? [];

        foreach ($transactionNodes as $node) {
            $rawType = strtolower(trim((string) ($node->TRNTYPE ?? 'debit')));
            $transactions[] = [
                'raw_type' => $rawType,
                'type' => $this->mapTransactionType($rawType),
                'amount' => (float) ($node->TRNAMT ?? 0),
                'description' => trim((string) ($node->MEMO ?? 'Transação OFX')),
                'external_id' => $this->externalIdFor($node),
                'occurred_at' => $this->parseDate((string) ($node->DTPOSTED ?? '')),
            ];
        }

        return $transactions;
    }

    protected function mapTransactionType(string $type): string
    {
        $creditTypes = ['credit', 'dep', 'directdep', 'div', 'int', 'other'];

        return in_array($type, $creditTypes, true) ? 'credit' : 'debit';
    }

    protected function parseDate(?string $value): Carbon
    {
        $value = trim((string) $value);

        if ($value === '') {
            return now();
        }

        if (preg_match('/^\d{14}/', $value, $matches)) {
            return Carbon::createFromFormat('YmdHis', $matches[0], 'UTC')->setTimezone(config('app.timezone'));
        }

        if (preg_match('/^\d{8}/', $value, $matches)) {
            return Carbon::createFromFormat('Ymd', $matches[0], 'UTC')->setTimezone(config('app.timezone'));
        }

        return Carbon::parse($value);
    }

    protected function externalIdFor(SimpleXMLElement $node): string
    {
        $fitId = trim((string) ($node->FITID ?? ''));

        if ($fitId !== '') {
            return $fitId;
        }

        $payload = sprintf(
            '%s|%s|%s',
            (string) ($node->DTPOSTED ?? now()->format('YmdHis')),
            (string) ($node->TRNAMT ?? '0'),
            trim((string) ($node->MEMO ?? ''))
        );

        return Str::uuid() . '-' . md5($payload);
    }
}
