<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipos', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 50)->unique();

            $table->foreignId('area_id')
                ->nullable()
                ->constrained('areas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('responsable_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('tipo', 100);
            $table->string('marca', 100);
            $table->string('modelo', 100);
            $table->string('serie', 100)->nullable();
            $table->string('estado', 50);
            $table->boolean('activo')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipos');
    }
};
