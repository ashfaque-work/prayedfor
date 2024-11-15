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
        Schema::create('church_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('location_id');
            $table->integer('prayer_w_qnty')->default('10')->nullable();
            $table->integer('prayer_req_qnty')->default('10')->nullable();
            $table->integer('prayer_req_alltime')->default('50')->nullable();
            $table->string('time_gap_from')->default('08:00')->nullable();
            $table->string('time_gap_to')->default('17:00')->nullable();
            $table->integer('time_interval')->default(1)->nullable();
            $table->string('time_zone')->default('UTC')->nullable();
            $table->timestamps();    
            
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('church_settings');
    }
};
