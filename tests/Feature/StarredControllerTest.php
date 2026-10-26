<?php

use App\Models\User;

beforeEach(function () {
    $this->markTestSkipped('Phase 7 is pending redesign.');
    config()->set('studentvault.encryption.aes.key', base64_encode(str_repeat('a', 32)));
    config()->set('studentvault.encryption.des.key', base64_encode(str_repeat('b', 8)));
    config()->set('studentvault.encryption.rc4.key', base64_encode(str_repeat('c', 16)));
});

it('requires login to view starred resources', function () {
    $this->get(route('starred.index'))->assertRedirect(route('login'));
});

it('shows only the signed in users starred notes and files', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $user->notes()->create([
        'title' => 'Algorithms Cheat Sheet',
        'content' => 'Important',
        'starred_at' => now(),
    ]);
    $user->notes()->create([
        'title' => 'Database Summary',
        'content' => 'Later',
    ]);
    $user->vaultFiles()->create([
        'original_name' => 'starred-file.pdf',
        'stored_name' => 'starred-file.vault',
        'category' => 'documents',
        'cipher' => 'aes',
        'mime_type' => 'application/pdf',
        'original_size' => 2048,
        'original_hash' => str_repeat('a', 64),
        'starred_at' => now(),
    ]);
    $user->vaultFiles()->create([
        'original_name' => 'unstarred-file.pdf',
        'stored_name' => 'unstarred-file.vault',
        'category' => 'documents',
        'cipher' => 'aes',
        'mime_type' => 'application/pdf',
        'original_size' => 1024,
        'original_hash' => str_repeat('b', 64),
    ]);

    $otherUser->notes()->create([
        'title' => 'Other Starred Note',
        'content' => 'Private',
        'starred_at' => now(),
    ]);
    $otherUser->vaultFiles()->create([
        'original_name' => 'other-starred-file.pdf',
        'stored_name' => 'other-starred-file.vault',
        'category' => 'documents',
        'cipher' => 'aes',
        'mime_type' => 'application/pdf',
        'original_size' => 512,
        'original_hash' => str_repeat('c', 64),
        'starred_at' => now(),
    ]);

    $this->actingAs($user)->get(route('starred.index'))
        ->assertOk()
        ->assertSee('Algorithms Cheat Sheet')
        ->assertSee('starred-file.pdf')
        ->assertDontSee('Database Summary')
        ->assertDontSee('unstarred-file.pdf')
        ->assertDontSee('Other Starred Note')
        ->assertDontSee('other-starred-file.pdf');
});

it('shows an empty state when nothing is starred', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('starred.index'))
        ->assertOk()
        ->assertSee('No starred resources yet.');
});
