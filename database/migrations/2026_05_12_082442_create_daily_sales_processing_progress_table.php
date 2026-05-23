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
        Schema::create('daily_sales_processing_progresses', function (Blueprint $table) {
            $table->id();

            $table->date('report_date')->unique();

            $table->string('status')->default('pending');


            $table->unsignedBigInteger('last_processed_id')->default(0);

            $table->unsignedBigInteger('total_orders')->default(0);
            $table->decimal('total_sales', 18, 2)->default(0);

            $table->unsignedInteger('processed_chunks')->default(0);
            $table->unsignedInteger('chunk_size')->default(1000);

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_sales_processing_progresses');
    }

    /**
     * Reverse the migrations.
     */

};
