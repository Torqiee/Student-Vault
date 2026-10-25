<?php

use App\Models\Course;
use App\Models\User;

beforeEach(function () {
    config()->set('studentvault.encryption.aes.key', base64_encode(str_repeat('a', 32)));
    config()->set('studentvault.encryption.des.key', base64_encode(str_repeat('b', 8)));
    config()->set('studentvault.encryption.rc4.key', base64_encode(str_repeat('c', 16)));
});

it('redirects guests to login', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('shows an empty workspace with working quick actions', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('No notes yet.')
        ->assertSee('Your vault is empty.')
        ->assertSee(route('notes.create'))
        ->assertSee(route('vault.create'))
        ->assertViewHas('notesCount', 0)
        ->assertViewHas('filesCount', 0)
        ->assertViewHas('coursesCount', 0)
        ->assertViewHas('storageBytes', 0);
});

it('shows only the signed-in users counts storage and recent resources', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $user->notes()->create(['title' => 'My course note', 'content' => 'Private']);
    $otherUser->notes()->create(['title' => 'Other user secret', 'content' => 'Private']);
    Course::factory()->for($user)->create(['name' => 'My course']);
    Course::factory()->for($otherUser)->create(['name' => 'Other course']);
    $user->vaultFiles()->create([
        'original_name' => 'my-file.pdf', 'stored_name' => 'mine.vault', 'category' => 'documents',
        'cipher' => 'aes', 'mime_type' => 'application/pdf', 'original_size' => 2048,
        'original_hash' => str_repeat('a', 64),
    ]);
    $otherUser->vaultFiles()->create([
        'original_name' => 'other-file.pdf', 'stored_name' => 'other.vault', 'category' => 'documents',
        'cipher' => 'aes', 'mime_type' => 'application/pdf', 'original_size' => 9999,
        'original_hash' => str_repeat('b', 64),
    ]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertViewHas('notesCount', 1)
        ->assertViewHas('filesCount', 1)
        ->assertViewHas('coursesCount', 1)
        ->assertViewHas('storageBytes', 2048)
        ->assertSee('My course note')
        ->assertSee('my-file.pdf')
        ->assertDontSee('Other user secret')
        ->assertDontSee('other-file.pdf');
});

it('limits recent notes and files to five each', function () {
    $user = User::factory()->create();

    for ($index = 1; $index <= 6; $index++) {
        $user->notes()->create(['title' => "Note {$index}", 'content' => null]);
        $user->vaultFiles()->create([
            'original_name' => "file-{$index}.pdf", 'stored_name' => "file-{$index}.vault",
            'category' => 'documents', 'cipher' => 'aes', 'mime_type' => 'application/pdf',
            'original_size' => 1, 'original_hash' => str_repeat('a', 64),
        ]);
    }

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertViewHas('notesCount', 6)
        ->assertViewHas('filesCount', 6)
        ->assertViewHas('recentNotes', fn ($notes) => $notes->count() === 5)
        ->assertViewHas('recentFiles', fn ($files) => $files->count() === 5);
});
