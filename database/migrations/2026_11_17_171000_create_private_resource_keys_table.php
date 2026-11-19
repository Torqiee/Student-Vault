<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('private_resource_keys', function (Blueprint $table) {
            $table->id();

            $table->foreignId('owner_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('resource_type', 50);
            $table->unsignedBigInteger('resource_id');

            $table->longText('encrypted_symmetric_key');

            $table->timestamps();

            $table->unique(
                ['owner_id', 'resource_type', 'resource_id'],
                'private_resource_keys_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('private_resource_keys');
    }
};
