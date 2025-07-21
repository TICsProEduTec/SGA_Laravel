<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::dropIfExists('grado_user');
    }

    public function down(): void {
        Schema::create('grado_user', function ($table) {
            $table->id();
            $table->unsignedBigInteger('grado_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });
    }
};
