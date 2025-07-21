<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFeedbacksTable extends Migration
{
    public function up(): void
    {
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('curso_id');
            $table->unsignedBigInteger('user_id'); // estudiante
            $table->text('contenido');             // texto generado
            $table->string('generado_por')->default('IA'); // IA o docente
            $table->timestamp('fecha_generado')->useCurrent();

            $table->timestamps();

            $table->foreign('curso_id')->references('id')->on('cursos')->onDelete('cascade');
            // Asume que 'users' es tu tabla de estudiantes/docentes
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
}
