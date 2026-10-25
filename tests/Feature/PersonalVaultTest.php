<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Keep these test-only keys independent from any local .env values.
    config()->set('studentvault.encryption.aes.key', base64_encode(str_repeat('a', 32)));
    config()->set('studentvault.encryption.des.key', base64_encode(str_repeat('b', 8)));
    config()->set('studentvault.encryption.rc4.key', base64_encode(str_repeat('c', 32)));
});

it('stores the label and personal fields as ciphertext and shows them only after authentication', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('personal-vault.store'), [
        'label' => 'My student profile',
        'category' => 'student_profile',
        'fields' => [
            'student_id' => 'STUDENT-987654',
            'university' => 'Institut Teknologi Sepuluh Nopember',
        ],
    ])->assertRedirect(route('personal-vault.index'));

    $row = DB::table('personal_data')->where('user_id', $user->id)->first();

    expect($row)->not->toBeNull()
        ->and($row->encrypted_label)->not->toContain('My student profile')
        ->and($row->encrypted_fields)->not->toContain('STUDENT-987654')
        ->and($row->encrypted_fields)->not->toContain('Institut Teknologi Sepuluh Nopember');

    $this->get(route('personal-vault.index'))
        ->assertOk()
        ->assertSee('My student profile')
        ->assertSee('STUDENT-987654')
        ->assertSee('Institut Teknologi Sepuluh Nopember');
});

it('prevents one user from editing another users personal entry', function (): void {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $owner->personalVaultEntries()->create([
        'encrypted_label' => 'not-relevant-for-authorization-check',
        'category' => 'student_profile',
        'encrypted_fields' => '{}',
        'identity_file_id' => null,
    ]);
    $entryId = DB::table('personal_data')->where('user_id', $owner->id)->value('id');

    $this->actingAs($otherUser)
        ->get(route('personal-vault.edit', $entryId))
        ->assertNotFound();
});

it('does not accept an identity file belonging to another user', function (): void {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $identityFile = $otherUser->vaultFiles()->create([
        'original_name' => 'private-id.png',
        'stored_name' => 'private-id.vault',
        'category' => 'identity',
        'cipher' => 'aes',
        'mime_type' => 'image/png',
        'original_size' => 10,
        'original_hash' => hash('sha256', 'sample'),
        'course_id' => null,
    ]);

    $this->actingAs($owner)->post(route('personal-vault.store'), [
        'label' => 'Identity card',
        'category' => 'identity',
        'identity_file_id' => $identityFile->id,
        'fields' => ['identity_number' => 'ID-12345'],
    ])->assertSessionHasErrors('identity_file_id');

    expect(DB::table('personal_data')->where('user_id', $owner->id)->count())->toBe(0);
});
