<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Service\StoreServiceRequest;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;

class StoreServiceController extends Controller
{
    public function __invoke(StoreServiceRequest $request): RedirectResponse
    {
        Service::query()->create($request->validated());

        return back();
    }
}
