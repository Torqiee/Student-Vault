<?php

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config()->set('studentvault.encryption.aes.key', base64_encode(str_repeat('a', 32)));
    config()->set('studentvault.encryption.des.key', base64_encode(str_repeat('b', 8)));
    config()->set('studentvault.encryption.rc4.key', base64_encode(str_repeat('c', 16)));
    Storage::fake('local');
});

it('requires login for course pages', function () {
    $this->get(route('courses.index'))->assertRedirect(route('login'));
    $this->get(route('courses.create'))->assertRedirect(route('login'));
});

it('creates, shows, and updates an owned course', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('courses.store'), [
        'name' => 'Information Security', 'description' => 'Semester project',
    ])->assertRedirect();

    $course = $user->courses()->sole();
    $this->assertDatabaseHas('courses', ['id' => $course->id, 'user_id' => $user->id, 'name' => 'Information Security']);
    $this->actingAs($user)->get(route('courses.show', $course))->assertOk()->assertSee('Information Security');
    $this->actingAs($user)->put(route('courses.update', $course), [
        'name' => 'Security', 'description' => '',
    ])->assertRedirect(route('courses.show', $course));
    expect($course->fresh()->name)->toBe('Security');
    expect($course->fresh()->description)->toBeNull();
});

it('rejects invalid course data', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('courses.store'), [
        'name' => '', 'description' => str_repeat('x', 2001),
    ])->assertSessionHasErrors(['name', 'description']);

    expect($user->courses()->count())->toBe(0);
});

it('forbids reading, changing, and deleting another users course', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $course = Course::factory()->for($owner)->create(['name' => 'Private subject']);

    $this->actingAs($otherUser)->get(route('courses.show', $course))->assertForbidden();
    $this->actingAs($otherUser)->get(route('courses.edit', $course))->assertForbidden();
    $this->actingAs($otherUser)->put(route('courses.update', $course), ['name' => 'Changed'])->assertForbidden();
    $this->actingAs($otherUser)->delete(route('courses.destroy', $course))->assertForbidden();
    $this->actingAs($otherUser)->get(route('courses.index'))->assertDontSee('Private subject');
    $this->assertModelExists($course);
});

it('moves notes and files to General when a course is deleted', function () {
    $user = User::factory()->create();
    $course = Course::factory()->for($user)->create();
    $note = $user->notes()->create(['title' => 'Retained note', 'content' => 'Safe', 'course_id' => $course->id]);
    $file = $user->vaultFiles()->create([
        'original_name' => 'retained.pdf', 'stored_name' => 'retained.vault',
        'category' => 'documents', 'cipher' => 'aes', 'mime_type' => 'application/pdf',
        'original_size' => 4, 'original_hash' => str_repeat('a', 64), 'course_id' => $course->id,
    ]);
    Storage::disk('local')->put('vault/retained.vault', 'ciphertext');

    $this->actingAs($user)->delete(route('courses.destroy', $course))->assertRedirect(route('courses.index'));

    expect($note->fresh()->course_id)->toBeNull();
    expect($file->fresh()->course_id)->toBeNull();
    Storage::disk('local')->assertExists('vault/retained.vault');
    $this->assertModelMissing($course);
});

it('escapes course names and descriptions in the page', function () {
    $user = User::factory()->create();
    $course = Course::factory()->for($user)->create([
        'name' => '<script>alert(1)</script>',
        'description' => '<img src=x onerror=alert(1)>',
    ]);

    $this->actingAs($user)->get(route('courses.show', $course))
        ->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;img', false)->assertDontSee('<img src=x', false);
});
