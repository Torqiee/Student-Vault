<?php

namespace App\Services\Vault;

use App\Models\PersonalVaultEntry;
use App\Models\User;
use App\Services\Encryption\EncryptionManager;
use App\Services\Encryption\PrivateDataKeyService;
use Illuminate\Support\Facades\DB;
use JsonException;
use RuntimeException;
use Throwable;

class PersonalVaultService
{
    public const RESOURCE_TYPE = 'personal_data';

    private const CATEGORIES = [
        'student_profile' => 'Student Profile',
        'contact' => 'Contact Details',
        'emergency' => 'Emergency Contact',
        'identity' => 'Identity Information',
        'other' => 'Other Information',
    ];

    private const FIELD_LABELS = [
        'student_id' => 'Student ID',
        'university' => 'University',
        'faculty' => 'Faculty',
        'study_program' => 'Study Program',
        'academic_year' => 'Academic Year',
        'personal_phone' => 'Personal Phone',
        'personal_email' => 'Personal Email',
        'address' => 'Address',
        'emergency_contact_name' => 'Emergency Contact Name',
        'emergency_contact_phone' => 'Emergency Contact Phone',
        'emergency_contact_relation' => 'Relationship',
        'identity_number' => 'Identity Number',
        'identity_type' => 'Identity Type',
        'identity_expiry' => 'Expiry Date',
        'other_notes' => 'Notes',
    ];

    private const FIELD_GROUPS = [
        'student_profile' => [
            'student_id', 'university', 'faculty', 'study_program', 'academic_year',
        ],
        'contact' => [
            'personal_phone', 'personal_email', 'address',
        ],
        'emergency' => [
            'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relation',
        ],
        'identity' => [
            'identity_number', 'identity_type', 'identity_expiry',
        ],
        'other' => [
            'other_notes',
        ],
    ];

    public function __construct(
        private EncryptionManager $encryptionManager,
        private PrivateDataKeyService $privateDataKeyService
    ) {}

    public static function categories(): array
    {
        return self::CATEGORIES;
    }

    public static function fieldLabels(): array
    {
        return self::FIELD_LABELS;
    }

    public static function fieldGroups(): array
    {
        return self::FIELD_GROUPS;
    }

    public function hasContent(string $category, array $fields, ?int $identityFileId): bool
    {
        if ($category === 'identity' && $identityFileId !== null) {
            return true;
        }

        foreach (self::FIELD_GROUPS[$category] ?? [] as $field) {
            $value = $fields[$field] ?? null;
            if (is_scalar($value) && trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Store a new entry encrypted with its own AES-256 resource key.
     */
    public function create(User $user, array $validated): PersonalVaultEntry
    {
        return DB::transaction(function () use ($user, $validated): PersonalVaultEntry {
            $entry = $user->personalVaultEntries()->create([
                'encrypted_label' => '',
                'category' => $validated['category'],
                'encrypted_fields' => '{}',
            ]);

            $key = $this->privateDataKeyService->getOrCreateResourceKey($user->id, self::RESOURCE_TYPE, $entry->id);

            $entry->fill($this->encryptedAttributes($validated, $key));
            $entry->save();

            return $entry;
        });
    }

    public function update(PersonalVaultEntry $entry, array $validated): void
    {
        $key = $this->privateDataKeyService->getOrCreateResourceKey($entry->user_id, self::RESOURCE_TYPE, $entry->id);

        $entry->fill($this->encryptedAttributes($validated, $key));
        $entry->save();
    }

    /**
     * Delete an entry together with its resource key.
     */
    public function delete(PersonalVaultEntry $entry): void
    {
        DB::transaction(function () use ($entry): void {
            $entry->delete();
            $this->privateDataKeyService->forgetResourceKey($entry->user_id, self::RESOURCE_TYPE, $entry->id);
        });
    }

    /**
     * Re-encrypt an entry created before resource keys existed so its key can be shared.
     *
     * @return string The entry's raw resource key.
     */
    public function ensureResourceKeyEncryption(PersonalVaultEntry $entry): string
    {
        $existingKey = $this->privateDataKeyService->findResourceKey($entry->user_id, self::RESOURCE_TYPE, $entry->id);

        if ($existingKey !== null && $this->encryptionManager->usesResourceKey($entry->encrypted_label)) {
            return $existingKey;
        }

        $plaintext = [
            'label' => $this->decryptValue($entry->encrypted_label, $existingKey),
            'category' => $entry->category,
            'fields' => $this->decryptFields($entry, $existingKey),
            'identity_file_id' => $entry->identity_file_id,
        ];

        $key = $this->privateDataKeyService->getOrCreateResourceKey($entry->user_id, self::RESOURCE_TYPE, $entry->id);

        $entry->fill($this->encryptedAttributes($plaintext, $key));
        $entry->save();

        return $key;
    }

    /** Convert a stored record to view data; plaintext is never written back to the model. */
    public function present(PersonalVaultEntry $entry): array
    {
        $identityFile = $entry->identityFile;
        if (
            ! $identityFile
            || (int) $identityFile->user_id !== (int) $entry->user_id
            || $identityFile->category !== 'identity'
        ) {
            $identityFile = null;
        }

        try {
            $key = $this->privateDataKeyService->findResourceKey($entry->user_id, self::RESOURCE_TYPE, $entry->id);

            return [
                'id' => $entry->id,
                'label' => $this->decryptValue($entry->encrypted_label, $key),
                'category' => $entry->category,
                'fields' => $this->decryptFields($entry, $key),
                'identity_file_id' => $entry->identity_file_id,
                'identity_file' => $identityFile,
                'updated_at' => $entry->updated_at,
                'error' => false,
            ];
        } catch (Throwable $exception) {
            // Do not expose ciphertext, keys, or cryptographic exception details to the UI.
            report($exception);

            return [
                'id' => $entry->id,
                'label' => 'Unable to open this entry securely',
                'category' => $entry->category,
                'fields' => [],
                'identity_file_id' => null,
                'identity_file' => null,
                'updated_at' => $entry->updated_at,
                'error' => true,
            ];
        }
    }

    /**
     * @param  array{label: string, category: string, fields?: array<string, mixed>, identity_file_id?: int|string|null}  $validated
     * @return array{encrypted_label: string, category: string, encrypted_fields: string, identity_file_id: int|string|null}
     */
    private function encryptedAttributes(array $validated, string $key): array
    {
        $category = $validated['category'];
        $fields = $this->fieldsForCategory($category, $validated['fields'] ?? []);
        $encryptedFields = [];

        foreach ($fields as $field => $value) {
            $encryptedFields[$field] = $this->encryptionManager->encryptWithKey($value, $key);
        }

        return [
            'encrypted_label' => $this->encryptionManager->encryptWithKey(trim($validated['label']), $key),
            'category' => $category,
            'encrypted_fields' => json_encode($encryptedFields, JSON_THROW_ON_ERROR),
            'identity_file_id' => $category === 'identity'
                ? ($validated['identity_file_id'] ?? null)
                : null,
        ];
    }

    private function fieldsForCategory(string $category, array $fields): array
    {
        $allowedKeys = array_flip(self::FIELD_GROUPS[$category] ?? []);
        $fields = array_intersect_key($fields, $allowedKeys);
        $clean = [];

        foreach ($fields as $key => $value) {
            if (! is_scalar($value)) {
                continue;
            }

            $value = trim((string) $value);
            if ($value !== '') {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }

    /**
     * @return array<string, string>
     *
     * @throws JsonException|RuntimeException
     */
    private function decryptFields(PersonalVaultEntry $entry, ?string $resourceKey): array
    {
        $encryptedFields = json_decode($entry->encrypted_fields, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($encryptedFields)) {
            throw new RuntimeException('Invalid personal vault field payload.');
        }

        $fields = [];
        foreach ($encryptedFields as $field => $ciphertext) {
            if (! isset(self::FIELD_LABELS[$field]) || ! is_string($ciphertext)) {
                continue;
            }

            $fields[$field] = $this->decryptValue($ciphertext, $resourceKey);
        }

        return $fields;
    }

    /**
     * Decrypt a resource-key payload, or a legacy payload encrypted with the environment AES key.
     */
    private function decryptValue(string $ciphertext, ?string $resourceKey): string
    {
        if ($this->encryptionManager->usesResourceKey($ciphertext)) {
            if ($resourceKey === null) {
                throw new RuntimeException('Resource encryption key not found.');
            }

            return $this->encryptionManager->decryptWithKey($ciphertext, $resourceKey);
        }

        return $this->encryptionManager->decrypt($ciphertext);
    }
}
