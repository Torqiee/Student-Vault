<?php

use App\Models\Course;
use App\Models\User;

it('renders the custom 404 page for an unknown route', function () {
    $this->get('/a-page-that-does-not-exist')
        ->assertNotFound()
        ->assertSee('We couldn’t find that page')
        ->assertSee(route('login'))
        ->assertSee('StudentVault');
});

it('renders the custom 403 page for a course owned by another user', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $course = Course::factory()->for($owner)->create();

    $this->actingAs($otherUser)->get(route('courses.show', $course))
        ->assertForbidden()
        ->assertSee('This page is private')
        ->assertSee(route('dashboard'))
        ->assertDontSee($course->name);
});
