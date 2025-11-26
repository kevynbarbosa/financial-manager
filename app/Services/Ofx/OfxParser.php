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
        $body = $this->escapeInvalidEntities($body);
        $normalized = $this->normalizeOfx($body);

        $xml = $this->loadXml($normalized) ?? $this->loadXml($body);

        if (! $xml instanceof SimpleXMLElement) {
            $fallback = $this->parseWithRegex($normalized) ?? $this->parseWithRegex($body);

            if ($fallback !== null) {
                return $fallback;
            }

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

        if (! is_string($body)) {
            throw new RuntimeException('Não foi possível normalizar o arquivo OFX.');
        }

        return $body;
    }

    protected function normalizeOfx(string $body): string
    {
        $bodyWithBreaks = preg_replace('/></', ">\n<", $body);

        return $this->closeUnterminatedTags($bodyWithBreaks);
    }

    protected function loadXml(string $body): ?SimpleXMLElement
    {
        libxml_use_internal_errors(true);

        $flags = LIBXML_NOERROR
            | LIBXML_NOWARNING
            | LIBXML_NONET
            | LIBXML_PARSEHUGE
            | LIBXML_NOCDATA
            | LIBXML_RECOVER;

        $xml = simplexml_load_string($body, SimpleXMLElement::class, $flags);

        libxml_clear_errors();

        return $xml ?: null;
    }

    protected function closeUnterminatedTags(string $body): string
    {
        $lines = preg_split('/\r?\n/', $body);

        $normalized = array_map(function (string $line) {
            $trimmed = trim($line);

            if (preg_match('/^<([A-Z0-9_]+)>([^<]+)$/i', $trimmed, $matches)) {
                [$full, $tag, $value] = $matches;

                return sprintf('<%s>%s</%s>', $tag, trim($value), $tag);
            }

            return $trimmed;
        }, $lines);

        return implode(PHP_EOL, $normalized);
    }

    protected function escapeInvalidEntities(string $body): string
    {
        return preg_replace('/&(?![a-zA-Z]+;)/', '&amp;', $body);
    }

    protected function parseWithRegex(string $body): ?array
    {
        $accountNumber = $this->tagValue($body, 'ACCTID');
        $accountType = strtolower($this->tagValue($body, 'ACCTTYPE') ?? 'checking');
        $currency = $this->tagValue($body, 'CURDEF') ?? 'BRL';
        $bankId = $this->tagValue($body, 'BANKID');
        $institution = $this->tagValue($body, 'ORG');

        if (! $accountNumber) {
            return null;
        }

        preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/si', $body, $matches);

        $transactions = [];

        foreach ($matches[1] as $chunk) {
            $rawType = strtolower($this->tagValue($chunk, 'TRNTYPE') ?? 'debit');
            $amount = (float) ($this->tagValue($chunk, 'TRNAMT') ?? 0);
            $memo = $this->tagValue($chunk, 'MEMO') ?? 'Transação OFX';
            $fitId = $this->tagValue($chunk, 'FITID') ?? '';
            $dateString = $this->tagValue($chunk, 'DTPOSTED') ?? '';

            $transactions[] = [
                'raw_type' => $rawType,
                'type' => $this->mapTransactionType($rawType),
                'amount' => $amount,
                'description' => trim($memo),
                'external_id' => $this->externalIdFromRaw($fitId, $dateString, $amount, $memo),
                'occurred_at' => $this->parseDate($dateString),
            ];
        }

        return [
            'account' => [
                'number' => $accountNumber,
                'type' => $accountType,
                'bank_id' => $bankId,
                'institution' => $institution,
                'currency' => $currency,
                'name' => 'Conta '.$accountNumber,
            ],
            'transactions' => $transactions,
        ];
    }

    protected function tagValue(string $body, string $tag): ?string
    {
        if (preg_match('/<'.$tag.'>\s*([^<]+)\s*<\/'.$tag.'>/i', $body, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    protected function externalIdFromRaw(string $fitId, string $date, float|string $amount, string $memo): string
    {
        $fitId = trim($fitId);

        if ($fitId !== '') {
            return $fitId;
        }

        $payload = sprintf(
            '%s|%s|%s',
            $date !== '' ? $date : now()->format('YmdHis'),
            (string) $amount,
            trim($memo)
        );

        return Str::uuid().'-'.md5($payload);
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
            'name' => $account?->ACCTID ? 'Conta '.trim((string) $account->ACCTID) : null,
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

        return Str::uuid().'-'.md5($payload);
    }
}
