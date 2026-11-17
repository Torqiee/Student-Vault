<?php

namespace Database\Factories;

use App\Models\SharedFolder;
use App\Models\SharedFolderInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SharedFolderInvitation>
 */
class SharedFolderInvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shared_folder_id' => SharedFolder::factory(),
            'invitee_id' => User::factory(),
            'email' => fn (array $attributes): string => User::query()->findOrFail($attributes['invitee_id'])->email,
            'status' => 'pending',
            'responded_at' => null,
        ];
    }
}
