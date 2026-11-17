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
        Schema::table('notes', function (Blueprint $table) {
            $table->timestamp('starred_at')->nullable();
        });

        Schema::table('vault_files', function (Blueprint $table) {
            $table->timestamp('starred_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropColumn('starred_at');
        });

        Schema::table('vault_files', function (Blueprint $table) {
            $table->dropColumn('starred_at');
        });
    }
};
