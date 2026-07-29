<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;

class DeleteServiceController extends Controller
{
    public function __invoke(Service $service): RedirectResponse
    {
        $this->authorize('delete', $service);

        // The lines that named this service keep their amounts and become
        // unattributed, so no transaction changes value. See Service::booted().
        $service->delete();

        return back();
    }
}
