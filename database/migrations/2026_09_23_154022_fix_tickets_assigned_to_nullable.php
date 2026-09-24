<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Drop foreign key if exists (nome padrão tickets_assigned_to_foreign)
            try {
                $table->dropForeign(['assigned_to']);
            } catch (\Throwable $e) {
                // ignora se já não existe
            }
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_to')->nullable()->change();
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            try {
                $table->dropForeign(['assigned_to']);
            } catch (\Throwable $e) {}
        });
        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_to')->nullable(false)->change();
            $table->foreign('assigned_to')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
