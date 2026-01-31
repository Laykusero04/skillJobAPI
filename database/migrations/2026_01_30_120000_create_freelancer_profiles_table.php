<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('freelancer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->text('bio')->nullable();
            $table->string('resume_url', 2048)->nullable();
            $table->timestamp('resume_uploaded_at')->nullable();
            $table->string('availability', 255)->nullable();
            $table->boolean('available_today')->default(false);
            $table->decimal('avg_rating', 3, 2)->nullable();
            $table->unsignedInteger('completed_gigs')->default(0);
            $table->unsignedInteger('no_shows')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('freelancer_profiles');
    }
};
