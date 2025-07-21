<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('cursos', function (Blueprint $table) {
            $table->renameColumn('Plantillas_id', 'plantilla_id');
        });
    }

    public function down(): void {
        Schema::table('cursos', function (Blueprint $table) {
            $table->renameColumn('plantilla_id', 'Plantillas_id');
        });
    }
};
