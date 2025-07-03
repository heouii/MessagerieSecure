<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('secure_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->string('recipient_email');
            $table->string('subject');
            $table->longText('encrypted_content');
            $table->string('message_token', 64)->unique();
            $table->string('encryption_key', 32);
            $table->string('mailgun_id')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('self_destructed_at')->nullable();
            $table->boolean('require_2fa')->default(false);
            $table->boolean('self_destruct')->default(false);
            $table->boolean('read_receipt')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['message_token']);
            $table->index(['sender_id', 'created_at']);
            $table->index(['recipient_email']);
            $table->index(['expires_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('secure_messages');
    }
};