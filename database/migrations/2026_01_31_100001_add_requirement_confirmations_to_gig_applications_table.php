<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gig_applications', function (Blueprint $table) {
            $table->json('requirement_confirmations')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('gig_applications', function (Blueprint $table) {
            $table->dropColumn('requirement_confirmations');
        });
    }
};
