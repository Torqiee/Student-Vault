<?php

use App\Models\PersonalVaultEntry;
use App\Models\User;
use App\Services\Vault\PersonalVaultService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {

    // Disable Vite when running backend feature tests.
    $this->withoutVite();

    config()->set(
        'studentvault.encryption.aes.key',
        base64_encode(str_repeat('a', 32))
    );

    config()->set(
        'studentvault.encryption.des.key',
        base64_encode(str_repeat('b', 8))
    );

    config()->set(
        'studentvault.encryption.rc4.key',
        base64_encode(str_repeat('c', 32))
    );
});

// Helper untuk membuat data Personal Vault terenkripsi.

function createPrivateVaultEntry(
    User $owner,
    string $label = 'Private Entry',
    string $studentId = 'STUDENT-12345'
): PersonalVaultEntry {
    return app(PersonalVaultService::class)->create($owner, [
        'label' => $label,
        'category' => 'student_profile',
        'fields' => [
            'student_id' => $studentId,
        ],
    ]);
}


// Test 1: Guest tidak boleh membuka Personal Vault.
it('requires authentication to access personal vault', function (): void {
    $this->get(route('personal-vault.index'))
        ->assertRedirect(route('login'));

    $this->get(route('personal-vault.create'))
        ->assertRedirect(route('login'));
});

// Test 2: Pengguna hanya melihat data miliknya sendiri.
it('only displays personal data belonging to the authenticated user', function (): void {
    $owner = User::factory()->create();
    $requester = User::factory()->create();

    createPrivateVaultEntry($owner, 'Owners Private Data');
    createPrivateVaultEntry($requester, 'Requesters Private Data');

    $this->actingAs($requester)
        ->get(route('personal-vault.index'))
        ->assertOk()
        ->assertSee('Requesters Private Data')
        ->assertDontSee('Owners Private Data');
});

// Test 3: Pengguna lain tidak boleh membuka data pemilik.
it('prevents another user from accessing a private vault entry', function (): void {
    $owner = User::factory()->create();
    $requester = User::factory()->create();

    $entry = createPrivateVaultEntry($owner, 'Private Student Data');

    $this->actingAs($requester);
    $this->withoutExceptionHandling();

    expect(fn () => $this->get(
        route('personal-vault.edit', $entry->id)
    ))->toThrow(ModelNotFoundException::class);
});

// Test 4: Pengguna lain tidak boleh mengubah data pemilik.
it('prevents another user from updating a private vault entry', function (): void {
    $owner = User::factory()->create();
    $requester = User::factory()->create();

    $entry = createPrivateVaultEntry($owner, 'Original Private Data');

    $this->actingAs($requester);
    $this->withoutExceptionHandling();

    expect(fn () => $this->put(
        route('personal-vault.update', $entry->id),
        [
            'label' => 'Modified Data',
            'category' => 'student_profile',
        ]
    ))->toThrow(ModelNotFoundException::class);

    expect($entry->fresh()->encrypted_label)
        ->toBe($entry->encrypted_label);
});

// Test 5: Pengguna lain tidak boleh menghapus data pemilik.
it('prevents another user from deleting a private vault entry', function (): void {
    $owner = User::factory()->create();
    $requester = User::factory()->create();

    $entry = createPrivateVaultEntry($owner, 'Private Data');

    $this->actingAs($requester);
    $this->withoutExceptionHandling();

    expect(fn () => $this->delete(
        route('personal-vault.destroy', $entry->id)
    ))->toThrow(ModelNotFoundException::class);

    expect($entry->fresh())->not->toBeNull();
});
