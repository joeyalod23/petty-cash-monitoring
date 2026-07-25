<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('replenishment_items');

        Schema::create('replenishment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('replenishment_report_id')->constrained('replenishment_reports')->cascadeOnDelete();
            $table->date('expense_date');
            $table->string('voucher_no', 100);
            $table->string('reference_no', 100)->nullable();
            $table->string('payee', 255);
            $table->string('cost_code', 100);
            $table->string('particulars', 255);
            $table->decimal('amount', 10, 2);
            $table->string('group_key', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('replenishment_items');
    }
};
