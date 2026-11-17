<?php

use App\Mail\SharedFolderInvitationMail;
use App\Models\Note;
use App\Models\SharedFolder;
use App\Models\SharedFolderInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('studentvault.encryption.aes.key', base64_encode(str_repeat('a', 32)));
    config()->set('studentvault.encryption.des.key', base64_encode(str_repeat('b', 8)));
    config()->set('studentvault.encryption.rc4.key', base64_encode(str_repeat('c', 16)));
});

it('requires login and keeps a pending invitee outside the folder', function (): void {
    Mail::fake();
    $owner = User::factory()->create();
    $friend = User::factory()->create();

    $this->get(route('shared-folders.index'))->assertRedirect(route('login'));
    $this->actingAs($owner)->post(route('shared-folders.store'), ['name' => 'Study group'])
        ->assertRedirect();
    $folder = SharedFolder::query()->sole();
    $this->actingAs($owner)->post(route('shared-folders.invitations.store', $folder), ['email' => $friend->email])
        ->assertRedirect(route('shared-folders.show', $folder));

    Mail::assertSent(SharedFolderInvitationMail::class, fn (SharedFolderInvitationMail $mail): bool => $mail->hasTo($friend->email));
    $this->actingAs($friend)->get(route('shared-folders.show', $folder))->assertForbidden();
    $this->actingAs($friend)->get(route('shared-folders.invitations.index'))->assertOk()->assertSee('Study group');
    expect(SharedFolderInvitation::query()->sole()->status)->toBe('pending');
});

it('lets invited users accept or decline and prevents replay or cross account acceptance', function (): void {
    Mail::fake();
    $owner = User::factory()->create();
    $friend = User::factory()->create();
    $stranger = User::factory()->create();
    $folder = SharedFolder::factory()->for($owner, 'owner')->create();

    $this->actingAs($owner)->post(route('shared-folders.invitations.store', $folder), ['email' => $friend->email])->assertRedirect();
    $invitation = SharedFolderInvitation::query()->sole();
    $this->actingAs($stranger)->post(route('shared-folders.invitations.accept', $invitation))->assertForbidden();
    $this->actingAs($friend)->post(route('shared-folders.invitations.accept', $invitation))
        ->assertRedirect(route('shared-folders.show', $folder));
    $this->actingAs($friend)->get(route('shared-folders.show', $folder))->assertOk();
    $this->actingAs($friend)->post(route('shared-folders.invitations.accept', $invitation))->assertForbidden();
    expect($folder->members()->whereKey($friend->id)->exists())->toBeTrue();

    $this->actingAs($owner)->post(route('shared-folders.invitations.store', $folder), ['email' => $stranger->email])->assertRedirect();
    $secondInvitation = SharedFolderInvitation::query()->latest('id')->firstOrFail();
    $this->actingAs($stranger)->post(route('shared-folders.invitations.decline', $secondInvitation))->assertRedirect();
    $this->actingAs($stranger)->get(route('shared-folders.show', $folder))->assertForbidden();
    expect($secondInvitation->fresh()->status)->toBe('declined');
});

it('shares only contributed resources and preserves their original ownership', function (): void {
    Storage::fake('local');
    $owner = User::factory()->create();
    $friend = User::factory()->create();
    $stranger = User::factory()->create();
    $folder = SharedFolder::factory()->for($owner, 'owner')->create();
    $folder->members()->attach($friend);
    $note = $owner->notes()->create(['title' => 'Team note', 'content' => 'Shared secret']);
    $privateNote = $owner->notes()->create(['title' => 'Private note', 'content' => 'Only mine']);
    $friendNote = $friend->notes()->create(['title' => 'Friend note', 'content' => 'Friend content']);

    $this->actingAs($owner)->post(route('shared-folders.notes.store', $folder), ['note_id' => $note->id])->assertRedirect();
    $this->actingAs($friend)->post(route('shared-folders.notes.store', $folder), ['note_id' => $friendNote->id])->assertRedirect();
    $this->actingAs($friend)->post(route('shared-folders.notes.store', $folder), ['note_id' => $privateNote->id])->assertNotFound();
    $this->actingAs($friend)->get(route('shared-folders.notes.show', [$folder, $note]))->assertOk()->assertSee('Shared secret');
    $this->actingAs($friend)->get(route('shared-folders.notes.show', [$folder, $privateNote]))->assertNotFound();
    $this->actingAs($friend)->get(route('notes.show', $note))->assertForbidden();
    $this->actingAs($friend)->delete(route('notes.destroy', $note))->assertForbidden();
    $this->actingAs($stranger)->get(route('shared-folders.notes.show', [$folder, $note]))->assertForbidden();

    $this->actingAs($owner)->post(route('vault.store'), [
        'category' => 'documents',
        'file' => UploadedFile::fake()->createWithContent('study.pdf', "%PDF-1.4\nshared file"),
    ])->assertRedirect();
    $file = $owner->vaultFiles()->sole();
    $this->actingAs($owner)->post(route('shared-folders.files.store', $folder), ['vault_file_id' => $file->id])->assertRedirect();
    $this->actingAs($friend)->post(route('shared-folders.files.store', $folder), ['vault_file_id' => $file->id])->assertNotFound();
    $this->actingAs($friend)->get(route('shared-folders.files.download', [$folder, $file]))
        ->assertOk()->assertStreamedContent("%PDF-1.4\nshared file");
    $this->actingAs($stranger)->get(route('shared-folders.files.download', [$folder, $file]))->assertForbidden();
    $this->actingAs($friend)->get(route('vault.download', $file))->assertForbidden();
});

it('revokes access and detaches contributions without deleting originals', function (): void {
    $owner = User::factory()->create();
    $friend = User::factory()->create();
    $folder = SharedFolder::factory()->for($owner, 'owner')->create();
    $folder->members()->attach($friend);
    $note = $friend->notes()->create(['title' => 'Friend contribution', 'content' => 'Keep this']);
    $ownerNote = $owner->notes()->create(['title' => 'Owner contribution', 'content' => 'Owner content']);
    $this->actingAs($friend)->post(route('shared-folders.notes.store', $folder), ['note_id' => $note->id])->assertRedirect();
    $this->actingAs($owner)->post(route('shared-folders.notes.store', $folder), ['note_id' => $ownerNote->id])->assertRedirect();

    $this->actingAs($owner)->delete(route('shared-folders.members.destroy', [$folder, $friend]))->assertRedirect();
    $this->actingAs($friend)->get(route('shared-folders.show', $folder))->assertForbidden();
    $this->actingAs($friend)->get(route('shared-folders.notes.show', [$folder, $ownerNote]))->assertForbidden();
    expect($folder->notes()->whereKey($note->id)->exists())->toBeFalse()
        ->and($note->fresh()->content)->toBe('Keep this');

    $ownNote = $owner->notes()->create(['title' => 'Shared later', 'content' => 'Return']);
    $this->actingAs($owner)->post(route('shared-folders.notes.store', $folder), ['note_id' => $ownNote->id])->assertRedirect();
    $this->actingAs($owner)->delete(route('notes.destroy', $ownNote))->assertRedirect();
    expect($folder->notes()->whereKey($ownNote->id)->exists())->toBeFalse();
    $this->actingAs($owner)->patch(route('trash.notes.restore', $ownNote->id))->assertRedirect();
    expect($folder->notes()->whereKey($ownNote->id)->exists())->toBeFalse();

    $this->actingAs($owner)->delete(route('shared-folders.destroy', $folder))->assertRedirect();
    expect(Note::query()->find($note->id))->not->toBeNull();
});

it('lets only the owner manage invitations and prevents revoked invitations from being accepted', function (): void {
    Mail::fake();
    $owner = User::factory()->create();
    $friend = User::factory()->create();
    $stranger = User::factory()->create();
    $folder = SharedFolder::factory()->for($owner, 'owner')->create();

    $this->actingAs($friend)->post(route('shared-folders.invitations.store', $folder), ['email' => $stranger->email])->assertForbidden();
    $this->actingAs($owner)->post(route('shared-folders.invitations.store', $folder), ['email' => $owner->email])->assertSessionHasErrors('email');
    $this->actingAs($owner)->post(route('shared-folders.invitations.store', $folder), ['email' => $friend->email])->assertRedirect();
    $invitation = SharedFolderInvitation::query()->sole();
    $this->actingAs($owner)->post(route('shared-folders.invitations.store', $folder), ['email' => $friend->email])->assertSessionHasErrors('email');
    $this->actingAs($friend)->delete(route('shared-folders.invitations.revoke', [$folder, $invitation]))->assertForbidden();
    $this->actingAs($owner)->delete(route('shared-folders.invitations.revoke', [$folder, $invitation]))->assertRedirect();
    $this->actingAs($friend)->post(route('shared-folders.invitations.accept', $invitation))->assertForbidden();
    expect($folder->members()->whereKey($friend->id)->exists())->toBeFalse();
});

it('removes a trashed shared file and does not re-share it on restore', function (): void {
    Storage::fake('local');
    $owner = User::factory()->create();
    $friend = User::factory()->create();
    $folder = SharedFolder::factory()->for($owner, 'owner')->create();
    $folder->members()->attach($friend);

    $this->actingAs($owner)->post(route('vault.store'), [
        'category' => 'documents',
        'file' => UploadedFile::fake()->createWithContent('study.pdf', "%PDF-1.4\nshared file"),
    ])->assertRedirect();
    $file = $owner->vaultFiles()->sole();
    $this->actingAs($owner)->post(route('shared-folders.files.store', $folder), ['vault_file_id' => $file->id])->assertRedirect();
    $this->actingAs($owner)->delete(route('vault.destroy', $file))->assertRedirect();
    $this->actingAs($owner)->patch(route('trash.files.restore', $file->id))->assertRedirect();

    expect($folder->files()->whereKey($file->id)->exists())->toBeFalse();
    $this->actingAs($friend)->get(route('shared-folders.files.download', [$folder, $file]))->assertNotFound();
});

it('reserves folder management for the owner and lets a member leave', function (): void {
    $owner = User::factory()->create();
    $friend = User::factory()->create();
    $folder = SharedFolder::factory()->for($owner, 'owner')->create(['name' => 'Original name']);
    $folder->members()->attach($friend);
    $note = $friend->notes()->create(['title' => 'My note', 'content' => 'Mine']);

    $this->actingAs($friend)->get(route('shared-folders.edit', $folder))->assertForbidden();
    $this->actingAs($friend)->put(route('shared-folders.update', $folder), ['name' => 'Hijacked'])->assertForbidden();
    $this->actingAs($friend)->delete(route('shared-folders.destroy', $folder))->assertForbidden();
    $this->actingAs($friend)->post(route('shared-folders.notes.store', $folder), ['note_id' => $note->id])->assertRedirect();
    $this->actingAs($friend)->delete(route('shared-folders.members.destroy', [$folder, $friend]))
        ->assertRedirect(route('shared-folders.index'));

    expect($folder->fresh()->name)->toBe('Original name')
        ->and($folder->members()->whereKey($friend->id)->exists())->toBeFalse()
        ->and($folder->notes()->whereKey($note->id)->exists())->toBeFalse()
        ->and($note->fresh()->content)->toBe('Mine');
});

it('escapes user supplied folder names in the page and invitation email', function (): void {
    $owner = User::factory()->create();
    $folder = SharedFolder::factory()->for($owner, 'owner')->create(['name' => '<script>alert(1)</script>']);

    $this->actingAs($owner)->get(route('shared-folders.index'))
        ->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false);

    $email = (new SharedFolderInvitationMail($folder->name, $owner->name))->render();
    expect($email)->not->toContain('<script>alert(1)</script>');
});
