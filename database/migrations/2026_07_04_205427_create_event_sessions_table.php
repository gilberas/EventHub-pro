<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->string('location')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->string('recurrence_rule')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['event_id', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_sessions');
    }
};
