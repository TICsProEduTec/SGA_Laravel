<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('grados', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            // Usamos solo la relación correcta
            $table->foreignId('periodo_id')->nullable()->constrained('periodos')->nullOnDelete();

            $table->unsignedBigInteger('id_category_moodle')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('grados');
    }
};
