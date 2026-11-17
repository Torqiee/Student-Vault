<?php

namespace Database\Seeders;

use App\Models\SharedFolder;
use App\Models\SharedFolderInvitation;
use App\Models\User;
use Illuminate\Database\Seeder;

class SharedFolderInvitationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $folder = SharedFolder::query()->first();
        $invitee = User::query()->whereKeyNot($folder?->owner_id)->first();

        if ($folder !== null && $invitee !== null) {
            SharedFolderInvitation::factory()->for($folder, 'folder')->for($invitee)->create([
                'email' => $invitee->email,
            ]);
        }
    }
}
