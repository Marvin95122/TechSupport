<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table): void {
            $table->id();
            $table->string('folio', 40)->unique();

            $table->foreignId('solicitante_id')
                ->constrained('users')->cascadeOnUpdate()->restrictOnDelete();

            $table->foreignId('equipo_id')->nullable()
                ->constrained('equipos')->cascadeOnUpdate()->restrictOnDelete();

            $table->foreignId('categoria_id')
                ->constrained('categorias')->cascadeOnUpdate()->restrictOnDelete();

            $table->foreignId('prioridad_id')
                ->constrained('prioridades')->cascadeOnUpdate()->restrictOnDelete();

            $table->foreignId('estado_id')
                ->constrained('estados_ticket')->cascadeOnUpdate()->restrictOnDelete();

            // Reservados para HU-13 a HU-15 (Sprint 5); no se usan aún en formularios.
            $table->foreignId('tecnico_id')->nullable()
                ->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('asignado_por_id')->nullable()
                ->constrained('users')->cascadeOnUpdate()->restrictOnDelete();

            $table->string('asunto', 180);
            $table->text('descripcion');
            $table->dateTime('creado_en')->useCurrent();
            $table->dateTime('asignado_en')->nullable();
            $table->dateTime('resuelto_en')->nullable();
            $table->dateTime('cerrado_en')->nullable();
            $table->index(['solicitante_id', 'creado_en']);
            $table->index(['estado_id', 'prioridad_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
