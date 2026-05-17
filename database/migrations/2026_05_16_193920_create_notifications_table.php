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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('batch_id')->nullable()->index();
            $table->string('channel')->index();
            $table->string('recipient')->index();
            $table->text('content');
            $table->string('status')->default('pending')->index();
            $table->string('priority')->default('normal')->index();
            $table->string('idempotency_key')->nullable()->unique();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->text('error_log')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
