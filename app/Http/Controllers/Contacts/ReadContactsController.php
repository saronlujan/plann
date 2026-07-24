<?php

namespace App\Http\Controllers\Contacts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReadContactsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Contacts/Index');
    }
}
