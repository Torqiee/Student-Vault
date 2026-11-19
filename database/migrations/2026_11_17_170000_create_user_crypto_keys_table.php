<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_crypto_keys', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->text('public_key');

            $table->longText('encrypted_private_key');

            $table->string('algorithm', 50)
                ->default('RSA-OAEP-SHA256');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_crypto_keys');
    }
};
