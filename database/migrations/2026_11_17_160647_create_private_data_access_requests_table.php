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
        Schema::create('private_data_access_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->string('resource_type', 32);
            $table->unsignedBigInteger('resource_id');
            $table->string('status', 16)->default('pending');
            $table->text('encrypted_symmetric_key')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->index(['owner_id', 'status']);
            $table->index(['requester_id', 'status']);
            $table->index(['resource_type', 'resource_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('private_data_access_requests');
    }
};
