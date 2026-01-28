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
        Schema::create('gigs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->foreignId('primary_skill_id')->constrained('skills')->cascadeOnDelete();
            $table->string('location');
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->decimal('pay', 10, 2);
            $table->unsignedInteger('workers_needed');
            $table->text('description');
            $table->boolean('auto_close_enabled')->default(false);
            $table->dateTime('auto_close_at')->nullable();
            $table->string('status')->default('open');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gigs');
    }
};
