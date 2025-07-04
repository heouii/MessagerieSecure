<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('approved_senders', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->string('email')->nullable()->after('user_id');
            $table->string('domain')->nullable()->after('email');
        });
    }

    public function down()
    {
        Schema::table('approved_senders', function (Blueprint $table) {
            $table->dropColumn(['user_id', 'email', 'domain']);
        });
    }
};