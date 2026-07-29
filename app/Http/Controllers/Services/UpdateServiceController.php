<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Service\UpdateServiceRequest;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;

class UpdateServiceController extends Controller
{
    public function __invoke(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        $this->authorize('update', $service);

        // Repricing only changes what future lines are offered at: the lines
        // already recorded keep the amount they were agreed at.
        $service->update($request->validated());

        return back();
    }
}
