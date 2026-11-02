<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cambios_estado', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('ticket_id')
                ->constrained('tickets')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('usuario_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('estado_anterior_id')
                ->nullable()
                ->constrained('estados_ticket')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('estado_nuevo_id')
                ->constrained('estados_ticket')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->dateTime('fecha')->useCurrent();

            $table->index(['ticket_id', 'fecha']);
            $table->index(['usuario_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cambios_estado');
    }
};