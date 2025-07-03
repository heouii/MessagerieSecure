<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('subject');
            $table->longText('html_content');
            $table->longText('text_content')->nullable();
            $table->json('variables')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['name', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('email_templates');
    }
};