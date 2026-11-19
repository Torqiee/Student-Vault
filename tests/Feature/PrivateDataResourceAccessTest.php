
<?php

use App\Models\PersonalVaultEntry;
use App\Models\PrivateDataAccessRequest;
use App\Models\User;
use App\Services\Vault\FileVaultService;
use App\Services\Vault\PersonalVaultService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
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

    Storage::fake('local');
    Mail::fake();
});

function createAccessTestEntry(
    User $owner,
    string $label = 'Shared Student Profile',
    string $studentId = 'STUDENT-SHARED'
): PersonalVaultEntry {
    return app(PersonalVaultService::class)->create($owner, [
        'label' => $label,
        'category' => 'student_profile',
        'fields' => [
            'student_id' => $studentId,
        ],
    ]);
}

function createAccessTestRequest(
    User $requester,
    User $owner,
    string $resourceType,
    int $resourceId
): PrivateDataAccessRequest {
    test()->actingAs($requester)
        ->post(route('private-access.store'), [
            'owner_email' => $owner->email,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
        ])
        ->assertRedirect(route('private-access.index'));

    return PrivateDataAccessRequest::query()
        ->latest('id')
        ->firstOrFail();
}

it('allows an approved requester to view shared personal data', function (): void {
    $owner = User::factory()->create();
    $requester = User::factory()->create();

    $entry = createAccessTestEntry($owner);

    $accessRequest = createAccessTestRequest(
        $requester,
        $owner,
        'personal_data',
        $entry->id
    );

    $this->actingAs($owner)
        ->post(route('private-access.approve', $accessRequest))
        ->assertRedirect();

    $this->actingAs($requester)
        ->get(route('private-access.resources.personal-data', $accessRequest->id))
        ->assertOk()
        ->assertSee('Shared Student Profile')
        ->assertSee('STUDENT-SHARED')
        ->assertSee($owner->name);
});

it('denies personal data access while the request is pending or declined', function (): void {
    $owner = User::factory()->create();
    $requester = User::factory()->create();

    $entry = createAccessTestEntry($owner);

    $accessRequest = createAccessTestRequest(
        $requester,
        $owner,
        'personal_data',
        $entry->id
    );

    // Pending request must not grant access.
    $this->actingAs($requester)
        ->get(route('private-access.resources.personal-data', $accessRequest->id))
        ->assertNotFound();

    // Owner declines the request.
    $this->actingAs($owner)
        ->post(route('private-access.decline', $accessRequest))
        ->assertRedirect();

    // Declined request must still be denied.
    $this->actingAs($requester)
        ->get(route('private-access.resources.personal-data', $accessRequest->id))
        ->assertNotFound();
});

it('immediately denies personal data access after revocation', function (): void {
    $owner = User::factory()->create();
    $requester = User::factory()->create();

    $entry = createAccessTestEntry($owner);

    $accessRequest = createAccessTestRequest(
        $requester,
        $owner,
        'personal_data',
        $entry->id
    );

    $this->actingAs($owner)
        ->post(route('private-access.approve', $accessRequest))
        ->assertRedirect();

    // Verify access before revocation.
    $this->actingAs($requester)
        ->get(route('private-access.resources.personal-data', $accessRequest->id))
        ->assertOk();

    // Owner revokes access.
    $this->actingAs($owner)
        ->post(route('private-access.revoke', $accessRequest))
        ->assertRedirect();

    // The same URL must no longer work.
    $this->actingAs($requester)
        ->get(route('private-access.resources.personal-data', $accessRequest->id))
        ->assertNotFound();
});

it('does not allow another user to use someone elses approved request', function (): void {
    $owner = User::factory()->create();
    $requester = User::factory()->create();
    $intruder = User::factory()->create();

    $entry = createAccessTestEntry($owner);

    $accessRequest = createAccessTestRequest(
        $requester,
        $owner,
        'personal_data',
        $entry->id
    );

    $this->actingAs($owner)
        ->post(route('private-access.approve', $accessRequest))
        ->assertRedirect();

    $this->actingAs($intruder)
        ->get(route('private-access.resources.personal-data', $accessRequest->id))
        ->assertNotFound();
});

it('allows an approved requester to download a shared file', function (): void {
    $owner = User::factory()->create();
    $requester = User::factory()->create();

    $content = 'This is a confidential shared file.';

    $file = app(FileVaultService::class)->store(
        $owner,
        UploadedFile::fake()->createWithContent('shared.pdf', $content),
        'identity'
    );

    $accessRequest = createAccessTestRequest(
        $requester,
        $owner,
        'vault_file',
        $file->id
    );

    $this->actingAs($owner)
        ->post(route('private-access.approve', $accessRequest))
        ->assertRedirect();

    $response = $this->actingAs($requester)
        ->get(route('private-access.resources.file', $accessRequest->id));

    $response->assertDownload('shared.pdf')
        ->assertStreamedContent($content);
});
