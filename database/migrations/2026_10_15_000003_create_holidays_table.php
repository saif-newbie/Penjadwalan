<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('one_off'); // one_off | recurring
            $table->date('date')->nullable();           // untuk one_off
            $table->unsignedTinyInteger('month')->nullable(); // 1-12 untuk recurring
            $table->unsignedTinyInteger('day')->nullable();   // 1-31 untuk recurring
            $table->string('label');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
