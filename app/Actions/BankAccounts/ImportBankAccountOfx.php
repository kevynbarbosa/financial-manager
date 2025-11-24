<?php

namespace App\Actions\BankAccounts;

use App\Http\Requests\ImportOfxRequest;
use App\Services\Ofx\OfxImportService;
use Illuminate\Http\RedirectResponse;

class ImportBankAccountOfx
{
    public function __construct(private OfxImportService $importService)
    {
    }

    public function __invoke(ImportOfxRequest $request): RedirectResponse
    {
        try {
            $result = $this->importService->import($request->user(), $request->file('ofx_file'));
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('accounts.index')
                ->with('error', 'Não conseguimos importar o arquivo OFX. Verifique o arquivo e tente novamente.');
        }

        $message = sprintf(
            'Importação concluída para %s: %d nova(s) transação(ões) e %d já existente(s).',
            $result['account']->name,
            $result['created'],
            $result['skipped']
        );

        return redirect()
            ->route('accounts.index')
            ->with('success', $message);
    }
}
