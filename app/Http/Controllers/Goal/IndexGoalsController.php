<?php

namespace App\Http\Controllers\Goal;

use App\Http\Controllers\Controller;
use App\Models\Goal;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexGoalsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $tenant = $request->user()?->tenant()->firstOrFail();

        return Inertia::render('Goals/Index', [
            'goals' => Goal::query()
                ->with('currency')
                ->orderBy('name')
                ->get()
                ->map(fn (Goal $goal): array => [
                    'id' => $goal->id,
                    'name' => $goal->name,
                    'currency_id' => $goal->currency_id,
                    'currency_code' => $goal->currency->code,
                    'target_amount' => $goal->target_amount,
                    'current_amount' => $goal->current_amount,
                    'target_date' => $goal->target_date?->toDateString(),
                ])
                ->all(),
            'currencyOptions' => $tenant->activeCurrencies()
                ->orderBy('code')
                ->get(['currencies.id', 'currencies.code', 'currencies.name'])
                ->map(fn ($currency): array => [
                    'id' => $currency->id,
                    'code' => $currency->code,
                    'name' => $currency->name,
                ])
                ->all(),
        ]);
    }
}
