<?php

namespace Database\Seeders;

use App\Models\SharedFolder;
use App\Models\User;
use Illuminate\Database\Seeder;

class SharedFolderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $owner = User::query()->first();

        if ($owner !== null) {
            SharedFolder::factory()->for($owner, 'owner')->create();
        }
    }
}
