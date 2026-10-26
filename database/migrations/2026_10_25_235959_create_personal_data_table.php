<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_data', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // The label and every personal field are AES-encrypted before they reach this table.
            $table->text('encrypted_label');
            $table->string('category', 32);
            $table->longText('encrypted_fields');

            // A link to a separately encrypted File Vault identity document; the file itself is not copied here.
            $table->foreignId('identity_file_id')
                ->nullable()
                ->constrained('vault_files')
                ->nullOnDelete();

            $table->timestamps();
            $table->index(['user_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_data');
    }
};
