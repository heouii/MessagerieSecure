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
        Schema::table('users', function (Blueprint $table) {

            // Colonnes admin/blocage
            $table->boolean('admin')->default(0)->after('updated_at');
            $table->boolean('is_blocked')->default(0)->after('admin');
            $table->timestamp('blocked_until')->nullable()->after('is_blocked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'admin',
                'is_blocked',
                'blocked_until',
            ]);
        });
    }
};
