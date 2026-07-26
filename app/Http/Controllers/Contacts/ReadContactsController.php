<?php

namespace App\Http\Controllers\Contacts;

use App\Enums\ContactType;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReadContactsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Contacts/Index', [
            'contacts' => Contact::query()
                ->orderBy('name')
                ->get(['id', 'name', 'type', 'email', 'phone', 'document', 'notes'])
                ->map(fn (Contact $contact): array => [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'type' => $contact->type->value,
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                    'document' => $contact->document,
                    'notes' => $contact->notes,
                ])
                ->all(),
            'typeOptions' => array_map(
                fn (ContactType $type): array => ['value' => $type->value, 'label' => $type->label()],
                ContactType::cases(),
            ),
        ]);
    }
}
