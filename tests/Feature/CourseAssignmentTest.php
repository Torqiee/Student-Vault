<?php

use App\Models\Course;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config()->set('studentvault.encryption.aes.key', base64_encode(str_repeat('a', 32)));
    config()->set('studentvault.encryption.des.key', base64_encode(str_repeat('b', 8)));
    config()->set('studentvault.encryption.rc4.key', base64_encode(str_repeat('c', 16)));
    Storage::fake('local');
});

it('assigns and removes a note from an owned course', function () {
    $user = User::factory()->create();
    $course = Course::factory()->for($user)->create();

    $this->actingAs($user)->post(route('notes.store'), [
        'title' => 'Algorithms', 'content' => 'KNN', 'course_id' => $course->id,
    ])->assertRedirect(route('notes.index'));

    $note = $user->notes()->sole();
    expect($note->course_id)->toBe($course->id);
    $this->actingAs($user)->get(route('courses.show', $course))->assertOk()->assertSee('Algorithms');

    $this->actingAs($user)->put(route('notes.update', $note), [
        'title' => 'Algorithms', 'content' => 'KNN', 'course_id' => '',
    ])->assertRedirect(route('notes.index'));
    expect($note->fresh()->course_id)->toBeNull();
});

it('rejects assigning notes to another users course', function () {
    $user = User::factory()->create();
    $otherCourse = Course::factory()->create();

    $this->actingAs($user)->post(route('notes.store'), [
        'title' => 'Secret', 'content' => 'Private', 'course_id' => $otherCourse->id,
    ])->assertSessionHasErrors('course_id');
    expect($user->notes()->count())->toBe(0);
});

it('assigns a new file and moves it to General', function () {
    $user = User::factory()->create();
    $course = Course::factory()->for($user)->create();

    $this->actingAs($user)->post(route('vault.store'), [
        'category' => 'documents', 'course_id' => $course->id,
        'file' => UploadedFile::fake()->createWithContent('lecture.pdf', "%PDF-1.4\ncontent"),
    ])->assertRedirect(route('vault.index'));

    $file = $user->vaultFiles()->sole();
    expect($file->course_id)->toBe($course->id);
    $this->actingAs($user)->get(route('courses.show', $course))->assertOk()->assertSee('lecture.pdf');

    $this->actingAs($user)->patch(route('vault.course.update', $file), ['course_id' => ''])
        ->assertRedirect();
    expect($file->fresh()->course_id)->toBeNull();
    Storage::disk('local')->assertExists('vault/'.$file->stored_name);
});

it('rejects another users course on file upload and reassignment', function () {
    $user = User::factory()->create();
    $otherCourse = Course::factory()->create();

    $this->actingAs($user)->post(route('vault.store'), [
        'category' => 'documents', 'course_id' => $otherCourse->id,
        'file' => UploadedFile::fake()->createWithContent('lecture.pdf', "%PDF-1.4\ncontent"),
    ])->assertSessionHasErrors('course_id');
    expect($user->vaultFiles()->count())->toBe(0);

    $this->actingAs($user)->post(route('vault.store'), [
        'category' => 'documents',
        'file' => UploadedFile::fake()->createWithContent('lecture.pdf', "%PDF-1.4\ncontent"),
    ])->assertRedirect(route('vault.index'));
    $file = $user->vaultFiles()->sole();
    $this->actingAs($user)->patch(route('vault.course.update', $file), ['course_id' => $otherCourse->id])
        ->assertSessionHasErrors('course_id');
    expect($file->fresh()->course_id)->toBeNull();
});

it('forbids reassigning another users file', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $file = $owner->vaultFiles()->create([
        'original_name' => 'private.pdf', 'stored_name' => 'private.vault',
        'category' => 'documents', 'cipher' => 'aes', 'mime_type' => 'application/pdf',
        'original_size' => 4, 'original_hash' => str_repeat('a', 64),
    ]);

    $this->actingAs($otherUser)->patch(route('vault.course.update', $file), ['course_id' => ''])->assertForbidden();
    expect($file->fresh()->course_id)->toBeNull();
});

it('shows only unassigned resources in General', function () {
    $user = User::factory()->create();
    $course = Course::factory()->for($user)->create();
    $user->notes()->create(['title' => 'General note', 'course_id' => null]);
    $user->notes()->create(['title' => 'Course note', 'course_id' => $course->id]);
    $user->vaultFiles()->create([
        'original_name' => 'general.pdf', 'stored_name' => 'general.vault', 'category' => 'documents',
        'cipher' => 'aes', 'mime_type' => 'application/pdf', 'original_size' => 4,
        'original_hash' => str_repeat('a', 64),
    ]);
    $user->vaultFiles()->create([
        'original_name' => 'course.pdf', 'stored_name' => 'course.vault', 'category' => 'documents',
        'cipher' => 'aes', 'mime_type' => 'application/pdf', 'original_size' => 4,
        'original_hash' => str_repeat('a', 64), 'course_id' => $course->id,
    ]);

    $this->actingAs($user)->get(route('notes.index', ['course' => 'general']))
        ->assertOk()->assertSee('General note')->assertDontSee('Course note');
    $this->actingAs($user)->get(route('vault.index', ['course' => 'general']))
        ->assertOk()->assertSee('general.pdf')->assertDontSee('course.pdf');
});

it('filters course resources and hides another users course filter', function () {
    $user = User::factory()->create();
    $course = Course::factory()->for($user)->create();
    $otherCourse = Course::factory()->create();
    $user->notes()->create(['title' => 'Selected note', 'course_id' => $course->id]);
    $user->notes()->create(['title' => 'General note']);
    $user->vaultFiles()->create([
        'original_name' => 'selected.pdf', 'stored_name' => 'selected.vault', 'category' => 'documents',
        'cipher' => 'aes', 'mime_type' => 'application/pdf', 'original_size' => 4,
        'original_hash' => str_repeat('a', 64), 'course_id' => $course->id,
    ]);

    $this->actingAs($user)->get(route('notes.index', ['course' => $course->id]))
        ->assertOk()->assertSee('Selected note')->assertDontSee('General note');
    $this->actingAs($user)->get(route('vault.index', ['course' => $course->id]))
        ->assertOk()->assertSee('selected.pdf');
    $this->actingAs($user)->get(route('notes.index', ['course' => $otherCourse->id]))->assertNotFound();
    $this->actingAs($user)->get(route('vault.index', ['course' => $otherCourse->id]))->assertNotFound();
});
