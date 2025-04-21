<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inicios', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('shortname')->nullable()->unique();
            $table->unsignedBigInteger('plantilla_id')->nullable();
            $table->foreign('plantilla_id')->references('id')->on('plantillas')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inicios');
    }
};
