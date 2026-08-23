<?php

declare(strict_types=1);

use App\Mail\EmailVerificationCodeMail;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

it('registers without requiring username or avatar and generates username automatically', function () {
    Mail::fake();
    Storage::fake('public');

    $this->post('/api/v1/auth/register', [
        'first_name' => 'Ahmed',
        'last_name' => 'Hassan',
        'username' => 'requested_name',
        'email' => 'ahmed@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'avatar' => UploadedFile::fake()->image('avatar.jpg'),
    ])->assertCreated();

    $user = User::query()->where('email', 'ahmed@example.com')->firstOrFail();

    expect($user->username)
        ->not->toBe('requested_name')
        ->toStartWith('ahmed_hassan_')
        ->and($user->avatar)->toBeNull();

    Storage::disk('public')->assertDirectoryEmpty('avatars');
    Mail::assertSent(EmailVerificationCodeMail::class);
});

it('generates username automatically when creating users through the factory', function () {
    $user = User::factory()->create([
        'first_name' => 'Factory',
        'last_name' => 'User',
    ]);

    expect($user->username)
        ->toStartWith('factory_user_')
        ->and(User::query()->where('username', $user->username)->count())->toBe(1);
});

it('validates and normalizes phone during registration', function () {
    Mail::fake();

    $this->postJson('/api/v1/auth/register', [
        'first_name' => 'Phone',
        'last_name' => 'User',
        'email' => 'phone@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone' => '+90 555 111 22 33',
    ])->assertCreated();

    $this->assertDatabaseHas('users', [
        'email' => 'phone@example.com',
        'phone' => '+905551112233',
    ]);
});

it('rejects invalid phone during registration', function () {
    $this->postJson('/api/v1/auth/register', [
        'first_name' => 'Invalid',
        'last_name' => 'Phone',
        'email' => 'invalid-phone@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone' => '12345',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('phone');
});

it('updates profile username and avatar while enforcing username uniqueness', function () {
    Storage::fake('public');

    User::factory()->create([
        'username' => 'taken_name',
    ]);

    $user = User::factory()->create([
        'username' => 'old_name',
    ]);

    $this->actingAs($user, 'sanctum')
        ->patch('/api/v1/profile', [
            'username' => 'New_Name',
            'avatar' => UploadedFile::fake()->image('avatar.png'),
        ])
        ->assertOk()
        ->assertJsonPath('data.user.username', 'new_name');

    $user->refresh();

    expect($user->username)
        ->toBe('new_name')
        ->and($user->avatar)->toStartWith('avatars/');

    Storage::disk('public')->assertExists($user->avatar);

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/v1/profile', [
            'username' => 'Taken_Name',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('username');
});

it('updates profile phone with normalization and uniqueness validation', function () {
    User::factory()->create([
        'phone' => '+905551112233',
    ]);

    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/v1/profile', [
            'phone' => '+90 555 222 33 44',
        ])
        ->assertOk();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'phone' => '+905552223344',
    ]);

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/v1/profile', [
            'phone' => '+90 555 111 22 33',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('phone');
});
