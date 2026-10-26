<?php

use App\Models\Note;
use App\Models\User;
use App\Models\VaultFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config()->set('studentvault.encryption.aes.key', base64_encode(str_repeat('a', 32)));
    config()->set('studentvault.encryption.des.key', base64_encode(str_repeat('b', 8)));
    config()->set('studentvault.encryption.rc4.key', base64_encode(str_repeat('c', 16)));
});

it('requires authentication to view and change the trash', function () {
    $this->get(route('trash.index'))->assertRedirect(route('login'));
    $this->patch(route('trash.notes.restore', 1))->assertRedirect(route('login'));
    $this->delete(route('trash.files.destroy', 1))->assertRedirect(route('login'));
});

it('moves a note to trash and restores its encrypted content', function () {
    $user = User::factory()->create();
    $note = $user->notes()->create(['title' => 'Private lecture', 'content' => 'Secret notes']);

    $this->actingAs($user)->delete(route('notes.destroy', $note))
        ->assertRedirect(route('notes.index'))
        ->assertSessionHas('success', 'Note moved to Trash.');

    expect($user->notes()->find($note->id))->toBeNull();
    expect(Note::withTrashed()->findOrFail($note->id)->trashed())->toBeTrue();
    $this->actingAs($user)->get(route('notes.index'))->assertOk()->assertDontSee('Private lecture');
    $this->actingAs($user)->get(route('notes.show', $note))->assertNotFound();
    $this->actingAs($user)->get(route('dashboard'))->assertViewHas('notesCount', 0);
    $this->actingAs($user)->get(route('trash.index'))->assertOk()->assertSee('Private lecture');

    $this->actingAs($user)->patch(route('trash.notes.restore', $note->id))
        ->assertRedirect(route('trash.index'))
        ->assertSessionHas('success', 'Note restored.');

    expect($note->fresh()->content)->toBe('Secret notes');
    $this->actingAs($user)->get(route('notes.show', $note))->assertOk()->assertSee('Secret notes');
    $this->actingAs($user)->get(route('trash.index'))->assertDontSee('Private lecture');
});

it('moves a file to trash without deleting ciphertext and restores its download', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $content = "%PDF-1.4\nsecret file";
    $this->actingAs($user)->post(route('vault.store'), [
        'category' => 'documents',
        'file' => UploadedFile::fake()->createWithContent('lecture.pdf', $content),
    ])->assertRedirect(route('vault.index'));
    $file = $user->vaultFiles()->sole();
    $path = 'vault/'.$file->stored_name;

    $this->actingAs($user)->delete(route('vault.destroy', $file))
        ->assertRedirect(route('vault.index'))
        ->assertSessionHas('success', 'File moved to Trash.');

    expect($user->vaultFiles()->find($file->id))->toBeNull();
    expect(VaultFile::withTrashed()->findOrFail($file->id)->trashed())->toBeTrue();
    Storage::disk('local')->assertExists($path);
    $this->actingAs($user)->get(route('vault.index'))->assertOk()->assertDontSee('lecture.pdf');
    $this->actingAs($user)->get(route('vault.download', $file))->assertNotFound();
    $this->actingAs($user)->get(route('dashboard'))->assertViewHas('filesCount', 0);
    $this->actingAs($user)->get(route('trash.index'))->assertOk()->assertSee('lecture.pdf');

    $this->actingAs($user)->patch(route('trash.files.restore', $file->id))
        ->assertRedirect(route('trash.index'))
        ->assertSessionHas('success', 'File restored.');

    $response = $this->actingAs($user)->get(route('vault.download', $file));
    $response->assertOk();
    expect($response->streamedContent())->toBe($content);
    Storage::disk('local')->assertExists($path);
});

it('permanently deletes only trashed notes and files', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $note = $user->notes()->create(['title' => 'Old note', 'content' => null]);
    $file = $user->vaultFiles()->create([
        'original_name' => 'old.pdf', 'stored_name' => 'old.vault', 'category' => 'documents',
        'cipher' => 'aes', 'mime_type' => 'application/pdf', 'original_size' => 4,
        'original_hash' => str_repeat('a', 64),
    ]);
    Storage::disk('local')->put('vault/old.vault', 'encrypted');

    $this->actingAs($user)->delete(route('trash.notes.destroy', $note->id))->assertNotFound();
    $this->actingAs($user)->delete(route('trash.files.destroy', $file->id))->assertNotFound();
    Storage::disk('local')->assertExists('vault/old.vault');

    $this->actingAs($user)->delete(route('notes.destroy', $note))->assertRedirect(route('notes.index'));
    $this->actingAs($user)->delete(route('vault.destroy', $file))->assertRedirect(route('vault.index'));
    $this->actingAs($user)->delete(route('trash.notes.destroy', $note->id))
        ->assertRedirect(route('trash.index'));
    $this->actingAs($user)->delete(route('trash.files.destroy', $file->id))
        ->assertRedirect(route('trash.index'));

    expect(Note::withTrashed()->find($note->id))->toBeNull();
    expect(VaultFile::withTrashed()->find($file->id))->toBeNull();
    Storage::disk('local')->assertMissing('vault/old.vault');
});

it('shows only the owners deleted items and forbids cross-user trash actions', function () {
    Storage::fake('local');
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $note = $owner->notes()->create(['title' => 'Owner secret', 'content' => null]);
    $file = $owner->vaultFiles()->create([
        'original_name' => 'owner-secret.pdf', 'stored_name' => 'owner.vault', 'category' => 'documents',
        'cipher' => 'aes', 'mime_type' => 'application/pdf', 'original_size' => 4,
        'original_hash' => str_repeat('a', 64),
    ]);
    Storage::disk('local')->put('vault/owner.vault', 'encrypted');
    $note->delete();
    $file->delete();

    $this->actingAs($otherUser)->get(route('trash.index'))
        ->assertOk()->assertDontSee('Owner secret')->assertDontSee('owner-secret.pdf');
    $this->actingAs($otherUser)->patch(route('trash.notes.restore', $note->id))->assertForbidden();
    $this->actingAs($otherUser)->delete(route('trash.notes.destroy', $note->id))->assertForbidden();
    $this->actingAs($otherUser)->patch(route('trash.files.restore', $file->id))->assertForbidden();
    $this->actingAs($otherUser)->delete(route('trash.files.destroy', $file->id))->assertForbidden();

    expect(Note::withTrashed()->findOrFail($note->id)->trashed())->toBeTrue();
    expect(VaultFile::withTrashed()->findOrFail($file->id)->trashed())->toBeTrue();
    Storage::disk('local')->assertExists('vault/owner.vault');
});

it('keeps a trashed file when its ciphertext is missing during restore', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $file = $user->vaultFiles()->create([
        'original_name' => 'missing.pdf', 'stored_name' => 'missing.vault', 'category' => 'documents',
        'cipher' => 'aes', 'mime_type' => 'application/pdf', 'original_size' => 4,
        'original_hash' => str_repeat('a', 64),
    ]);
    $file->delete();

    $this->actingAs($user)->patch(route('trash.files.restore', $file->id))
        ->assertRedirect(route('trash.index'))
        ->assertSessionHasErrors('file');

    expect(VaultFile::withTrashed()->findOrFail($file->id)->trashed())->toBeTrue();
});

it('keeps a trashed file record when ciphertext deletion fails', function () {
    $user = User::factory()->create();
    $file = $user->vaultFiles()->create([
        'original_name' => 'locked.pdf', 'stored_name' => 'locked.vault', 'category' => 'documents',
        'cipher' => 'aes', 'mime_type' => 'application/pdf', 'original_size' => 4,
        'original_hash' => str_repeat('a', 64),
    ]);
    $file->delete();
    $disk = Mockery::mock();
    $disk->shouldReceive('exists')->once()->with('vault/locked.vault')->andReturn(true);
    $disk->shouldReceive('delete')->once()->with('vault/locked.vault')->andReturn(false);
    Storage::shouldReceive('disk')->once()->with('local')->andReturn($disk);

    $this->actingAs($user)->delete(route('trash.files.destroy', $file->id))
        ->assertRedirect(route('trash.index'))
        ->assertSessionHasErrors('file');

    expect(VaultFile::withTrashed()->findOrFail($file->id)->trashed())->toBeTrue();
});

it('permanently removes a trashed file record when its ciphertext is already missing', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $file = $user->vaultFiles()->create([
        'original_name' => 'gone.pdf', 'stored_name' => 'gone.vault', 'category' => 'documents',
        'cipher' => 'aes', 'mime_type' => 'application/pdf', 'original_size' => 4,
        'original_hash' => str_repeat('a', 64),
    ]);
    $file->delete();

    $this->actingAs($user)->delete(route('trash.files.destroy', $file->id))
        ->assertRedirect(route('trash.index'))
        ->assertSessionHas('success', 'File permanently deleted.');

    expect(VaultFile::withTrashed()->find($file->id))->toBeNull();
});

it('escapes deleted note titles and file names in trash', function () {
    $user = User::factory()->create();
    $note = $user->notes()->create(['title' => '<script>alert(1)</script>', 'content' => null]);
    $file = $user->vaultFiles()->create([
        'original_name' => '<img src=x onerror=alert(1)>.pdf',
        'stored_name' => 'unsafe.vault', 'category' => 'documents', 'cipher' => 'aes',
        'mime_type' => 'application/pdf', 'original_size' => 4,
        'original_hash' => str_repeat('a', 64),
    ]);
    $note->delete();
    $file->delete();

    $this->actingAs($user)->get(route('trash.index'))
        ->assertOk()
        ->assertSee('&lt;script&gt;', false)
        ->assertSee('&lt;img', false)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('<img src=x', false);
});

it('keeps trashed notes out of starred results and course pages', function () {
    $user = User::factory()->create();
    $course = $user->courses()->create(['name' => 'Machine Learning']);
    $note = $user->notes()->create([
        'title' => 'Hidden course note', 'content' => null, 'course_id' => $course->id,
        'starred_at' => now(),
    ]);
    $note->delete();

    $this->actingAs($user)->get(route('starred.index'))->assertOk()->assertDontSee('Hidden course note');
    $this->actingAs($user)->get(route('courses.show', $course))->assertOk()->assertDontSee('Hidden course note');
    $this->actingAs($user)->get(route('search.index', ['q' => 'Hidden']))->assertOk()->assertDontSee('Hidden course note');
});
