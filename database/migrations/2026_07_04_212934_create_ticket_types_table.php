<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_session_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('mode')->default('general_admission');
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('quantity_available')->nullable();
            $table->unsignedInteger('max_per_order')->default(10);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['event_session_id', 'mode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_types');
    }
};
