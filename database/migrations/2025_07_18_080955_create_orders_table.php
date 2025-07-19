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
        Schema::disableForeignKeyConstraints();

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('customer');
            $table->timestamp('created_at');
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('warehouse_id');
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->enum('status', ["active","completed","cancelled"]);
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
