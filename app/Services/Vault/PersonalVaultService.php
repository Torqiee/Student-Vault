<?php

namespace App\Services\Vault;

use App\Models\PersonalVaultEntry;
use App\Models\User;
use App\Services\Encryption\EncryptionManager;
use App\Services\Encryption\EncryptionPolicy;
use JsonException;
use RuntimeException;
use Throwable;

class PersonalVaultService
{
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

    public function __construct(private EncryptionManager $encryptionManager)
    {
    }

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

    /** Encrypt one value using the project's AES-256-GCM cipher. */
    public function encryptText(string $value): string
    {
        return $this->encryptionManager->encrypt($value, EncryptionPolicy::IDENTITY);
    }

    public function decryptText(string $ciphertext): string
    {
        return $this->encryptionManager->decrypt($ciphertext);
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

    public function create(User $user, array $validated): PersonalVaultEntry
    {
        return $user->personalVaultEntries()->create($this->encryptedAttributes($validated));
    }

    public function update(PersonalVaultEntry $entry, array $validated): void
    {
        $entry->fill($this->encryptedAttributes($validated));
        $entry->save();
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
            return [
                'id' => $entry->id,
                'label' => $this->decryptText($entry->encrypted_label),
                'category' => $entry->category,
                'fields' => $this->decryptFields($entry),
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

    private function encryptedAttributes(array $validated): array
    {
        $category = $validated['category'];
        $label = trim($validated['label']);
        $fields = $this->fieldsForCategory($category, $validated['fields'] ?? []);
        $encryptedFields = [];

        foreach ($fields as $key => $value) {
            $encryptedFields[$key] = $this->encryptText($value);
        }

        return [
            'encrypted_label' => $this->encryptText($label),
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

    /** @throws JsonException|RuntimeException */
    private function decryptFields(PersonalVaultEntry $entry): array
    {
        $encryptedFields = json_decode($entry->encrypted_fields, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($encryptedFields)) {
            throw new RuntimeException('Invalid personal vault field payload.');
        }

        $fields = [];
        foreach ($encryptedFields as $key => $ciphertext) {
            if (! isset(self::FIELD_LABELS[$key]) || ! is_string($ciphertext)) {
                continue;
            }

            $fields[$key] = $this->decryptText($ciphertext);
        }

        return $fields;
    }
}
