<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('email_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('secure_messages')->onDelete('cascade');
            $table->enum('event_type', ['delivered', 'opened', 'clicked', 'bounced', 'complained', 'unsubscribed']);
            $table->json('event_data');
            $table->timestamp('occurred_at');
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['message_id', 'event_type']);
            $table->index(['occurred_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('email_tracking');
    }
};