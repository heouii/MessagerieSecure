<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('mailgun_id')->nullable();
            $table->enum('folder', ['inbox', 'sent', 'drafts', 'trash'])->default('inbox');
            $table->string('from_email');
            $table->string('from_name')->nullable();
            $table->string('to_email');
            $table->string('to_name')->nullable();
            $table->string('cc_email')->nullable();
            $table->string('subject');
            $table->longText('content');
            $table->text('preview')->nullable();
            $table->boolean('is_html')->default(false);
            $table->boolean('is_read')->default(false);
            $table->boolean('is_starred')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->json('attachments')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'folder']);
            $table->index(['from_email']);
            $table->index(['to_email']);
            $table->index(['is_read']);
            $table->index(['created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('emails');
    }
};
