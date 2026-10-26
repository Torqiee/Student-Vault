<?php

use App\Models\Course;
use App\Models\User;

beforeEach(function () {
    $this->markTestSkipped('Phase 7 is pending redesign.');
    config()->set('studentvault.encryption.aes.key', base64_encode(str_repeat('a', 32)));
    config()->set('studentvault.encryption.des.key', base64_encode(str_repeat('b', 8)));
    config()->set('studentvault.encryption.rc4.key', base64_encode(str_repeat('c', 16)));
});

it('requires login to search', function () {
    $this->get(route('search.index'))->assertRedirect(route('login'));
});

it('finds notes by decrypted title', function () {
    $user = User::factory()->create();
    $user->notes()->create([
        'title' => 'Machine Learning Notes',
        'content' => 'KNN and Decision Trees',
    ]);
    $user->notes()->create([
        'title' => 'Database Systems',
        'content' => 'SQL joins',
    ]);

    $this->actingAs($user)->get(route('search.index', ['q' => 'machine learning']))
        ->assertOk()
        ->assertSee('Machine Learning Notes')
        ->assertDontSee('Database Systems');
});

it('finds notes by decrypted content', function () {
    $user = User::factory()->create();
    $user->notes()->create([
        'title' => 'Machine Learning Notes',
        'content' => 'KNN and Decision Trees',
    ]);

    $this->actingAs($user)->get(route('search.index', ['q' => 'decision trees']))
        ->assertOk()
        ->assertSee('Machine Learning Notes');
});

it('finds files by name and category metadata', function () {
    $user = User::factory()->create();
    $user->vaultFiles()->create([
        'original_name' => 'security-assignment.pdf',
        'stored_name' => 'security-assignment.vault',
        'category' => 'assignments',
        'cipher' => 'des',
        'mime_type' => 'application/pdf',
        'original_size' => 1024,
        'original_hash' => str_repeat('a', 64),
    ]);

    $this->actingAs($user)->get(route('search.index', ['q' => 'security']))
        ->assertOk()
        ->assertSee('security-assignment.pdf');

    $this->actingAs($user)->get(route('search.index', ['q' => 'assignments']))
        ->assertOk()
        ->assertSee('security-assignment.pdf');
});

it('finds courses by name', function () {
    $user = User::factory()->create();
    Course::factory()->for($user)->create([
        'name' => 'Information Security',
        'description' => null,
    ]);

    $this->actingAs($user)->get(route('search.index', ['q' => 'information']))
        ->assertOk()
        ->assertSee('Information Security');
});

it('finds courses by description', function () {
    $user = User::factory()->create();
    Course::factory()->for($user)->create([
        'name' => 'Information Security',
        'description' => 'Network defense and cryptography',
    ]);

    $this->actingAs($user)->get(route('search.index', ['q' => 'cryptography']))
        ->assertOk()
        ->assertSee('Information Security');
});

it('does not expose another users resources', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $otherUser->notes()->create([
        'title' => 'Other Secret Note',
        'content' => 'Hidden content',
    ]);
    $otherUser->vaultFiles()->create([
        'original_name' => 'other-secret.pdf',
        'stored_name' => 'other-secret.vault',
        'category' => 'documents',
        'cipher' => 'aes',
        'mime_type' => 'application/pdf',
        'original_size' => 10,
        'original_hash' => str_repeat('b', 64),
    ]);
    Course::factory()->for($otherUser)->create([
        'name' => 'Other Secret Course',
    ]);

    $this->actingAs($user)->get(route('search.index', ['q' => 'other secret']))
        ->assertOk()
        ->assertDontSee('Other Secret Note')
        ->assertDontSee('other-secret.pdf')
        ->assertDontSee('Other Secret Course');
});

it('escapes the search query in the page', function () {
    $user = User::factory()->create();
    $query = '<script>alert(1)</script>';

    $this->actingAs($user)->get(route('search.index', ['q' => $query]))
        ->assertOk()
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee($query, false);
});
