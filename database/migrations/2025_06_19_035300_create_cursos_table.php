<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('cursos', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            $table->unsignedBigInteger('Plantillas_id')->nullable();
            $table->unsignedBigInteger('Grados_id')->nullable();
            $table->unsignedBigInteger('id_curso_moodle')->nullable();

            $table->timestamps();

            $table->foreign('Plantillas_id')->references('id')->on('plantillas')->onDelete('set null');
            $table->foreign('Grados_id')->references('id')->on('grados')->onDelete('set null');
        });
    }

    public function down(): void {
        Schema::dropIfExists('cursos');
    }
};
