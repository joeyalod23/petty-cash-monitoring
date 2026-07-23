<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('replenishment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fund_id')->constrained('petty_cash_funds')->cascadeOnDelete();
            $table->decimal('requested_amount', 10, 2);
            $table->string('status', 30)->default('pending');
            $table->string('triggered_by', 100)->default('System Automated Trigger');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('replenishment_requests');
    }
};
