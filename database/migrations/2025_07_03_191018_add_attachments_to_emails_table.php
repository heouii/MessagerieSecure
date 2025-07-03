<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('emails', function (Blueprint $table) {
            if (!Schema::hasColumn('emails', 'attachments')) {
                $table->json('attachments')->nullable()->after('is_html');
            }
        });
    }

    public function down()
    {
        Schema::table('emails', function (Blueprint $table) {
            if (Schema::hasColumn('emails', 'attachments')) {
                $table->dropColumn('attachments');
            }
        });
    }
};