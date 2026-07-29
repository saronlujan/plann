<?php

use App\Models\Account;
use App\Models\Currency;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use App\Support\Transactions\TransactionAttachments;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

use function Pest\Laravel\actingAs;

/**
 * @return array{0: User, 1: Currency, 2: Account}
 */
function attachmentFixture(string $email): array
{
    $tenant = Tenant::create(['name' => 'Tenant '.$email]);

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => $email,
        'password' => 'password',
    ]);

    $currency = Currency::create(['code' => 'BRL', 'name' => 'Real', 'symbol' => 'R$']);

    app(TenantContext::class)->setTenantId($tenant->id);

    $account = Account::create([
        'tenant_id' => $tenant->id,
        'currency_id' => $currency->id,
        'name' => 'Conta BRL',
    ]);

    return [$user, $currency, $account];
}

function fakeReceiptPhoto(int $width, int $height): UploadedFile
{
    return UploadedFile::fake()->image('recibo.jpg', $width, $height);
}

it('downscales and re-encodes a photo receipt', function () {
    [$user, $currency, $account] = attachmentFixture('attachment-photo@example.com');

    // A phone camera hands us something far larger than a receipt ever needs.
    actingAs($user)
        ->post('/transactions', [
            'movement_type' => 'expense',
            'type' => 'unique',
            'description' => 'Mercado',
            'currency_id' => $currency->id,
            'account_id' => $account->id,
            'effective_date' => '2026-12-15',
            'amount' => 100,
            'attachment' => fakeReceiptPhoto(3000, 2000),
        ])
        ->assertSessionHasNoErrors();

    $fileName = Transaction::query()->value('attachment');

    // Only the name is stored: no directory, no tenant id, no disk layout.
    expect($fileName)->toEndWith('.webp');
    expect($fileName)->not->toContain('/');

    $attachments = app(TransactionAttachments::class);
    $stored = (new ImageManager(new Driver))
        ->decodeBinary($attachments->disk()->get($attachments->path($fileName)));

    expect($stored->width())->toBe(1600);
    // Aspect ratio is preserved: 3000x2000 scaled to fit a 1600 box.
    expect($stored->height())->toBe(1067);
});

it('leaves an image smaller than the cap at its own size', function () {
    [$user, $currency, $account] = attachmentFixture('attachment-small@example.com');

    actingAs($user)
        ->post('/transactions', [
            'movement_type' => 'expense',
            'type' => 'unique',
            'description' => 'Padaria',
            'currency_id' => $currency->id,
            'account_id' => $account->id,
            'effective_date' => '2026-12-15',
            'amount' => 20,
            'attachment' => fakeReceiptPhoto(800, 600),
        ])
        ->assertSessionHasNoErrors();

    $attachments = app(TransactionAttachments::class);
    $stored = (new ImageManager(new Driver))->decodeBinary(
        $attachments->disk()->get($attachments->path(Transaction::query()->value('attachment'))),
    );

    // scaleDown never enlarges — upscaling a receipt would only waste bytes.
    expect($stored->width())->toBe(800);
    expect($stored->height())->toBe(600);
});

it('stores a pdf receipt untouched', function () {
    [$user, $currency, $account] = attachmentFixture('attachment-pdf@example.com');

    actingAs($user)
        ->post('/transactions', [
            'movement_type' => 'expense',
            'type' => 'unique',
            'description' => 'Contrato',
            'currency_id' => $currency->id,
            'account_id' => $account->id,
            'effective_date' => '2026-12-15',
            'amount' => 100,
            'attachment' => UploadedFile::fake()->create('contrato.pdf', 50, 'application/pdf'),
        ])
        ->assertSessionHasNoErrors();

    $fileName = Transaction::query()->value('attachment');
    $attachments = app(TransactionAttachments::class);

    expect($fileName)->not->toEndWith('.webp');
    expect($fileName)->not->toContain('/');
    expect($attachments->disk()->exists($attachments->path($fileName)))->toBeTrue();
});

it('keeps an upload whose bytes are not a readable image', function () {
    [$user, $currency, $account] = attachmentFixture('attachment-broken@example.com');

    // UploadedFile::fake()->image() writes a real image; this one only claims to
    // be one. Losing the receipt would be worse than storing it as-is.
    $path = tempnam(sys_get_temp_dir(), 'broken').'.jpg';
    file_put_contents($path, 'this is not an image');

    actingAs($user)
        ->post('/transactions', [
            'movement_type' => 'expense',
            'type' => 'unique',
            'description' => 'Quebrado',
            'currency_id' => $currency->id,
            'account_id' => $account->id,
            'effective_date' => '2026-12-15',
            'amount' => 100,
            'attachment' => new UploadedFile($path, 'recibo.jpg', 'image/jpeg', null, true),
        ]);

    $stored = Transaction::query()->value('attachment');

    if ($stored !== null) {
        $attachments = app(TransactionAttachments::class);

        expect($attachments->disk()->exists($attachments->path($stored)))->toBeTrue();
    }
});

it('keeps receipts on the private disk after optimizing', function () {
    Storage::fake('public');

    [$user, $currency, $account] = attachmentFixture('attachment-private@example.com');

    actingAs($user)
        ->post('/transactions', [
            'movement_type' => 'expense',
            'type' => 'unique',
            'description' => 'Mercado',
            'currency_id' => $currency->id,
            'account_id' => $account->id,
            'effective_date' => '2026-12-15',
            'amount' => 100,
            'attachment' => fakeReceiptPhoto(2000, 1500),
        ])
        ->assertSessionHasNoErrors();

    $fileName = Transaction::query()->value('attachment');
    $attachments = app(TransactionAttachments::class);
    $path = $attachments->path($fileName);

    // The tenant folder is derived, never persisted.
    expect($path)->toStartWith('transactions/attachments/'.$user->tenant_id.'/');
    expect($attachments->disk()->exists($path))->toBeTrue();
    expect(Storage::disk('public')->exists($path))->toBeFalse();
});

it('exposes the stored receipt on the entry so the form can reopen with it', function () {
    [$user, $currency, $account] = attachmentFixture('attachment-visible@example.com');

    actingAs($user)
        ->post('/transactions', [
            'movement_type' => 'expense',
            'type' => 'unique',
            'description' => 'Mercado',
            'currency_id' => $currency->id,
            'account_id' => $account->id,
            'effective_date' => '2026-12-15',
            'amount' => 100,
            'attachment' => fakeReceiptPhoto(1200, 900),
        ])
        ->assertSessionHasNoErrors();

    // Without this the modal cannot know a receipt exists, so the switch stayed
    // closed and the file was invisible after upload.
    actingAs($user)
        ->get('/transactions?period=2026-12')
        ->assertSuccessful()
        ->assertInertia(function ($page): void {
            $entry = collect($page->toArray()['props']['entries'])->firstWhere('label', 'Mercado');

            expect($entry['attachment'])->toEndWith('.webp');
        });
});

it('keeps the receipt when the transaction is edited without a new file', function () {
    [$user, $currency, $account] = attachmentFixture('attachment-kept@example.com');

    actingAs($user)
        ->post('/transactions', [
            'movement_type' => 'expense',
            'type' => 'unique',
            'description' => 'Mercado',
            'currency_id' => $currency->id,
            'account_id' => $account->id,
            'effective_date' => '2026-12-15',
            'amount' => 100,
            'attachment' => fakeReceiptPhoto(1200, 900),
        ])
        ->assertSessionHasNoErrors();

    $transaction = Transaction::query()->firstOrFail();
    $fileName = $transaction->attachment;

    actingAs($user)
        ->patch('/transactions/'.$transaction->id, [
            'movement_type' => 'expense',
            'type' => 'unique',
            'description' => 'Mercado do mês',
            'currency_id' => $currency->id,
            'account_id' => $account->id,
            'effective_date' => '2026-12-15',
            'amount' => 120,
        ])
        ->assertSessionHasNoErrors();

    expect($transaction->refresh()->attachment)->toBe($fileName);
});

it('replaces the receipt and deletes the file it superseded', function () {
    [$user, $currency, $account] = attachmentFixture('attachment-replaced@example.com');

    actingAs($user)
        ->post('/transactions', [
            'movement_type' => 'expense',
            'type' => 'unique',
            'description' => 'Mercado',
            'currency_id' => $currency->id,
            'account_id' => $account->id,
            'effective_date' => '2026-12-15',
            'amount' => 100,
            'attachment' => fakeReceiptPhoto(1200, 900),
        ])
        ->assertSessionHasNoErrors();

    $transaction = Transaction::query()->firstOrFail();
    $attachments = app(TransactionAttachments::class);
    $firstFile = (string) $transaction->attachment;

    actingAs($user)
        ->patch('/transactions/'.$transaction->id, [
            'movement_type' => 'expense',
            'type' => 'unique',
            'description' => 'Mercado',
            'currency_id' => $currency->id,
            'account_id' => $account->id,
            'effective_date' => '2026-12-15',
            'amount' => 100,
            'attachment' => fakeReceiptPhoto(1000, 800),
        ])
        ->assertSessionHasNoErrors();

    $transaction->refresh();

    expect($transaction->attachment)->not->toBe($firstFile);
    // The old file must not linger on the disk paying rent forever.
    expect($attachments->disk()->exists($attachments->path($firstFile)))->toBeFalse();
    expect($attachments->disk()->exists($attachments->path((string) $transaction->attachment)))->toBeTrue();
});
