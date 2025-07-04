<?php
// Créez cette migration : php artisan make:migration add_spam_columns_to_emails_table

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->boolean('is_spam')->default(false);
            $table->decimal('spam_probability', 4, 3)->default(0.000);
            $table->string('spam_confidence', 20)->nullable();
            $table->timestamp('spam_checked_at')->nullable();
            $table->json('spam_details')->nullable();
        });
    }

    public function down()
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->dropColumn([
                'is_spam', 
                'spam_probability', 
                'spam_confidence', 
                'spam_checked_at',
                'spam_details'
            ]);
        });
    }
};