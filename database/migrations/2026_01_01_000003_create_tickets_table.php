<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->text('description');
            $table->string('priority', 20);
            $table->string('status', 20);
            $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete();
            $table->dateTime('opened_at');
            $table->timestamps();

            $table->index(['status', 'priority', 'assigned_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
