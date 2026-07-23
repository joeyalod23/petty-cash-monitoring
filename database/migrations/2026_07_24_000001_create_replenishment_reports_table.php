<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('replenishment_reports', function (Blueprint $table) {
            $table->id();
            $table->string('project_name', 255);
            $table->string('location', 255);
            $table->string('subject', 255);
            $table->date('period_start');
            $table->date('period_end');
            $table->date('report_date');
            $table->decimal('cash_received', 10, 2);
            $table->string('prepared_by', 255);
            $table->string('reviewed_by', 255);
            $table->string('verified_by', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('replenishment_reports');
    }
};
