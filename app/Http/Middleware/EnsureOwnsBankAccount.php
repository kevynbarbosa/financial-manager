<?php

namespace App\Http\Middleware;

use App\Models\BankAccount;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOwnsBankAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var BankAccount|null $account */
        $account = $request->route('account');
        $user = $request->user();

        abort_if(! $user, 403);
        abort_if(! $account, 404);
        abort_if($account->user_id !== $user->id, 403);

        return $next($request);
    }
}
