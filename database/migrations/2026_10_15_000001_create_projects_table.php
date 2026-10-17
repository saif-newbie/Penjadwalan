<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('organization')->nullable();
            $table->string('website')->nullable();
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->string('weekend_mode')->default('off');       // off | continuous
            $table->string('holiday_mode')->default('none');      // none | custom
            $table->string('date_format')->default('mm/dd/yyyy'); // mm/dd/yyyy | dd/mm/yyyy
            $table->unsignedTinyInteger('font_size')->default(14);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
