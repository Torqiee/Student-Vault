<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shared_folder_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shared_folder_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['shared_folder_id', 'user_id']);
        });

        Schema::create('shared_folder_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shared_folder_id')->constrained()->cascadeOnDelete();
            $table->foreignId('note_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['shared_folder_id', 'note_id']);
        });

        Schema::create('shared_folder_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shared_folder_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vault_file_id')->constrained('vault_files')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['shared_folder_id', 'vault_file_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shared_folder_files');
        Schema::dropIfExists('shared_folder_notes');
        Schema::dropIfExists('shared_folder_memberships');
    }
};
