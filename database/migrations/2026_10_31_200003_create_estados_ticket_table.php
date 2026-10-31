<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estados_ticket', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 50)->unique();
            $table->unsignedTinyInteger('orden')->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estados_ticket');
    }
};
