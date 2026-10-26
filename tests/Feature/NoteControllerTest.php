<?php

use App\Models\User;

beforeEach(function () {
    config()->set('studentvault.encryption.aes.key', base64_encode(str_repeat('a', 32)));
    config()->set('studentvault.encryption.des.key', base64_encode(str_repeat('b', 8)));
    config()->set('studentvault.encryption.rc4.key', base64_encode(str_repeat('c', 16)));
});

it('sorts notes by title using their decrypted values', function () {
    $user = User::factory()->create();
    $user->notes()->create(['title' => 'Zebra Notes', 'content' => null]);
    $user->notes()->create(['title' => 'Alpha Notes', 'content' => null]);

    $this->actingAs($user)->get(route('notes.index', ['sort' => 'title']))
        ->assertOk()
        ->assertSeeInOrder(['Alpha Notes', 'Zebra Notes']);
});

it('sorts notes oldest first', function () {
    $user = User::factory()->create();
    $olderNote = $user->notes()->create(['title' => 'Zebra Notes', 'content' => null]);
    $olderNote->forceFill(['created_at' => now()->subDay()])->save();
    $user->notes()->create(['title' => 'Alpha Notes', 'content' => null]);

    $this->actingAs($user)->get(route('notes.index', ['sort' => 'oldest']))
        ->assertOk()
        ->assertSeeInOrder(['Zebra Notes', 'Alpha Notes']);
});

it('falls back to newest first for an unknown note sort', function () {
    $user = User::factory()->create();
    $olderNote = $user->notes()->create(['title' => 'Zebra Notes', 'content' => null]);
    $olderNote->forceFill(['created_at' => now()->subDay()])->save();
    $user->notes()->create(['title' => 'Alpha Notes', 'content' => null]);

    $this->actingAs($user)->get(route('notes.index', ['sort' => 'title;drop table notes']))
        ->assertOk()
        ->assertSeeInOrder(['Alpha Notes', 'Zebra Notes']);
});

it('toggles a note star for its owner', function () {
    $user = User::factory()->create();
    $note = $user->notes()->create(['title' => 'Starred Candidate', 'content' => null]);

    $this->actingAs($user)
        ->from(route('notes.index'))
        ->patch(route('notes.star', $note))
        ->assertRedirect(route('notes.index'))
        ->assertSessionHas('success', 'Note starred.');

    expect($note->fresh()->starred_at)->not->toBeNull();

    $this->actingAs($user)
        ->from(route('notes.index'))
        ->patch(route('notes.star', $note))
        ->assertRedirect(route('notes.index'))
        ->assertSessionHas('success', 'Note unstarred.');

    expect($note->fresh()->starred_at)->toBeNull();
});

it('forbids starring another users note', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $note = $owner->notes()->create(['title' => 'Private Note', 'content' => null]);

    $this->actingAs($otherUser)
        ->patch(route('notes.star', $note))
        ->assertForbidden();

    expect($note->fresh()->starred_at)->toBeNull();
});

it('does not treat a note star as a user controlled attribute on update', function () {
    $user = User::factory()->create();
    $note = $user->notes()->create(['title' => 'Original', 'content' => null]);

    $this->actingAs($user)->put(route('notes.update', $note), [
        'title' => 'Updated',
        'content' => null,
        'course_id' => null,
        'starred_at' => now()->toDateTimeString(),
    ])->assertRedirect(route('notes.index'));

    expect($note->fresh()->starred_at)->toBeNull();
});
