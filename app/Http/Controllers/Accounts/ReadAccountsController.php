<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReadAccountsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Accounts/Index');
    }
}
