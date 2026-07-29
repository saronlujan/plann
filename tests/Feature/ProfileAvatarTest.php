<?php

use App\Models\Tenant;
use App\Models\User;
use App\Support\Profile\AvatarStorage;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

use function Pest\Laravel\actingAs;

function avatarUser(string $email): User
{
    $tenant = Tenant::create(['name' => 'Tenant '.$email]);

    return User::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Pessoa Teste',
        'email' => $email,
        'password' => 'password',
    ]);
}

it('crops the upload to a square and stores it as webp', function () {
    $user = avatarUser('avatar-crop@example.com');

    actingAs($user)
        ->post(route('profile.avatar.update'), [
            'avatar' => UploadedFile::fake()->image('foto.jpg', 1200, 800),
            'crop_x' => 200,
            'crop_y' => 0,
            'crop_size' => 800,
        ])
        ->assertRedirect();

    $user->refresh();
    $avatars = app(AvatarStorage::class);

    expect($user->avatar)->toEndWith('.webp');
    // Only the file name is stored; the folder is rebuilt from the user.
    expect($user->avatar)->not->toContain('/');

    $stored = (new ImageManager(new Driver))
        ->decodeBinary($avatars->disk()->get($avatars->path($user, $user->avatar)));

    expect($stored->width())->toBe($stored->height());
});

it('never crops beyond the image, whatever the browser asks for', function () {
    $user = avatarUser('avatar-clamp@example.com');

    // A region far outside the picture would make the image library throw.
    actingAs($user)
        ->post(route('profile.avatar.update'), [
            'avatar' => UploadedFile::fake()->image('foto.jpg', 400, 400),
            'crop_x' => 9999,
            'crop_y' => 9999,
            'crop_size' => 9999,
        ])
        ->assertRedirect();

    $user->refresh();
    $avatars = app(AvatarStorage::class);

    expect($user->avatar)->not->toBeNull();
    expect($avatars->disk()->exists($avatars->path($user, $user->avatar)))->toBeTrue();
});

it('replaces the previous picture instead of piling files up', function () {
    $user = avatarUser('avatar-replace@example.com');
    $avatars = app(AvatarStorage::class);

    actingAs($user)->post(route('profile.avatar.update'), [
        'avatar' => UploadedFile::fake()->image('um.jpg', 600, 600),
        'crop_x' => 0,
        'crop_y' => 0,
        'crop_size' => 600,
    ]);

    $first = (string) $user->refresh()->avatar;

    actingAs($user)->post(route('profile.avatar.update'), [
        'avatar' => UploadedFile::fake()->image('dois.jpg', 600, 600),
        'crop_x' => 0,
        'crop_y' => 0,
        'crop_size' => 600,
    ]);

    $second = (string) $user->refresh()->avatar;

    expect($second)->not->toBe($first);
    expect($avatars->disk()->exists($avatars->path($user, $first)))->toBeFalse();
    expect($avatars->disk()->exists($avatars->path($user, $second)))->toBeTrue();
});

it('serves the picture only to the account that owns it', function () {
    $owner = avatarUser('avatar-owner@example.com');

    actingAs($owner)->post(route('profile.avatar.update'), [
        'avatar' => UploadedFile::fake()->image('foto.jpg', 600, 600),
        'crop_x' => 0,
        'crop_y' => 0,
        'crop_size' => 600,
    ]);

    actingAs($owner)->get(route('profile.avatar'))->assertOk();

    // Somebody else's session reads their own avatar route — and has none.
    $other = avatarUser('avatar-other@example.com');

    actingAs($other)->get(route('profile.avatar'))->assertNotFound();
});

it('rejects a file that is not an image', function () {
    $user = avatarUser('avatar-invalid@example.com');

    actingAs($user)
        ->post(route('profile.avatar.update'), [
            'avatar' => UploadedFile::fake()->create('contrato.pdf', 10, 'application/pdf'),
            'crop_x' => 0,
            'crop_y' => 0,
            'crop_size' => 100,
        ])
        ->assertSessionHasErrors('avatar');

    expect($user->refresh()->avatar)->toBeNull();
});

it('exposes the uploaded picture through the shared auth prop', function () {
    $user = avatarUser('avatar-shared@example.com');

    actingAs($user)->post(route('profile.avatar.update'), [
        'avatar' => UploadedFile::fake()->image('foto.jpg', 600, 600),
        'crop_x' => 0,
        'crop_y' => 0,
        'crop_size' => 600,
    ]);

    // The header reads avatar_url; an uploaded picture has to surface there or
    // it would be saved and never shown.
    actingAs($user)->get(route('profile.edit'))->assertSuccessful()
        ->assertInertia(function ($page): void {
            expect($page->toArray()['props']['auth']['user']['avatar_url'])
                ->toContain('profile/avatar');
        });
});
