<?php

namespace App\Http\Controllers\Register;

use App\Http\Controllers\Controller;
use App\Http\Requests\Register\RegisterRequest;
use App\Models\Currency;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StoreUserController extends Controller
{
    public function __invoke(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated): User {
            $currency = Currency::query()->firstOrCreate([
                'code' => 'BRL',
            ], [
                'name' => 'Brazilian Real',
                'symbol' => 'R$',
            ]);

            $tenant = Tenant::query()->create([
                'name' => $validated['name'],
                'locale' => 'pt',
            ]);

            $tenant->syncCurrencyActivations([$currency->id]);
            $tenant->ensureCurrencyAssets([$currency->id]);

            return User::query()->create([
                'tenant_id' => $tenant->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);
        });

        Auth::login($user);

        $request->session()->regenerate();

        return to_route('dashboard');
    }
}
