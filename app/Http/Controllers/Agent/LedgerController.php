<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\StatementRequest;
use App\Services\LedgerService;
use Inertia\Inertia;
use Inertia\Response;

class LedgerController extends Controller
{
    public function __invoke(StatementRequest $request, LedgerService $ledger): Response
    {
        [$from, $to] = $request->range();

        return Inertia::render('agent/Ledger', [
            'statement' => $ledger->statement($request->user()->agency, $from, $to),
            'filters' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
        ]);
    }
}
