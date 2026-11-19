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
        Schema::table('private_resource_keys', function (Blueprint $table) {
            $table->string('shared_stored_name')->nullable()->after('encrypted_symmetric_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('private_resource_keys', function (Blueprint $table) {
            $table->dropColumn('shared_stored_name');
        });
    }
};
