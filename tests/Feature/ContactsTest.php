<?php

use App\Models\Contact;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

function contactsUser(string $email): User
{
    $tenant = Tenant::create(['name' => 'Tenant '.$email]);
    app(TenantContext::class)->setTenantId($tenant->id);

    return User::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa',
        'email' => $email,
        'password' => 'password',
        'locale' => 'pt',
    ]);
}

test('authenticated users may view their contacts', function () {
    $user = contactsUser('contacts@example.com');
    Contact::create(['tenant_id' => $user->tenant_id, 'name' => 'ACME', 'type' => 'provider']);

    actingAs($user)
        ->get('/contacts')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Contacts/Index')
            ->has('contacts', 1)
            ->has('typeOptions', 4));
});

test('users may create a contact', function () {
    $user = contactsUser('contact-create@example.com');

    actingAs($user)
        ->post('/contacts', [
            'name' => 'Fornecedor X',
            'type' => 'provider',
            'email' => 'x@example.com',
            'phone' => '(11) 90000-0000',
            'document' => '12.345.678/0001-90',
            'notes' => 'Entrega mensal',
        ])
        ->assertRedirect();

    $contact = Contact::query()->where('name', 'Fornecedor X')->first();
    expect($contact)->not->toBeNull();
    expect($contact?->type->value)->toBe('provider');
    expect($contact?->tenant_id)->toBe($user->tenant_id);
});

test('contact creation validates required fields and email', function () {
    $user = contactsUser('contact-valid@example.com');

    actingAs($user)
        ->post('/contacts', ['name' => '', 'type' => 'client', 'email' => 'not-an-email'])
        ->assertSessionHasErrors(['name', 'email']);

    actingAs($user)
        ->post('/contacts', ['name' => 'Sem tipo', 'type' => 'invalid'])
        ->assertSessionHasErrors('type');
});

test('users may update and delete a contact', function () {
    $user = contactsUser('contact-ud@example.com');
    $contact = Contact::create(['tenant_id' => $user->tenant_id, 'name' => 'Cliente A', 'type' => 'client']);

    actingAs($user)
        ->patch('/contacts/'.$contact->id, ['name' => 'Cliente B', 'type' => 'provider'])
        ->assertRedirect();

    $contact->refresh();
    expect($contact->name)->toBe('Cliente B');
    expect($contact->type->value)->toBe('provider');

    actingAs($user)->delete('/contacts/'.$contact->id)->assertRedirect();
    expect(Contact::query()->whereKey($contact->id)->exists())->toBeFalse();
});

test('a tenant cannot modify another tenant contact', function () {
    $victim = contactsUser('victim-contact@example.com');
    $victimContact = Contact::create(['tenant_id' => $victim->tenant_id, 'name' => 'Privado', 'type' => 'client']);

    $attacker = contactsUser('attacker-contact@example.com');
    app(TenantContext::class)->clear();

    actingAs($attacker)
        ->patch('/contacts/'.$victimContact->id, ['name' => 'Hack', 'type' => 'client'])
        ->assertNotFound();

    actingAs($attacker)
        ->delete('/contacts/'.$victimContact->id)
        ->assertNotFound();

    expect($victimContact->fresh()?->name)->toBe('Privado');
});
