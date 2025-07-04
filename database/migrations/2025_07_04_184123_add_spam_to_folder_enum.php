<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE emails MODIFY COLUMN folder ENUM('inbox','sent','drafts','trash','unverified','spam') DEFAULT 'inbox'");
    }

    public function down()
    {
        // Attention : ceci supprimera les enregistrements avec folder='spam'
        DB::statement("UPDATE emails SET folder='unverified' WHERE folder='spam'");
        DB::statement("ALTER TABLE emails MODIFY COLUMN folder ENUM('inbox','sent','drafts','trash','unverified') DEFAULT 'inbox'");
    }
};