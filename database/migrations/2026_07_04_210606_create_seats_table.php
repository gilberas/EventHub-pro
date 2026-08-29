<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('row_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('seat_number');
            $table->string('type')->default('standard');
            $table->unsignedInteger('col_position');
            $table->unsignedInteger('row_position');
            $table->float('x_coord')->nullable();
            $table->float('y_coord')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['row_id', 'seat_number']);
            $table->index(['row_id', 'type']);
            $table->index(['type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
