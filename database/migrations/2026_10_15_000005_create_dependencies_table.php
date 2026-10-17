<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('predecessor_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('successor_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('type')->default('FS'); // FS | SS | FF | SF
            $table->integer('delay')->default(0);  // hari kerja
            $table->timestamps();

            $table->unique(['predecessor_id', 'successor_id'], 'dependencies_pair_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dependencies');
    }
};
