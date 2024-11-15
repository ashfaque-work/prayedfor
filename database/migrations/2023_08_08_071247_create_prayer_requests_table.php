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
        Schema::create('prayer_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('contact_id');
            $table->string('prayedfor_msg')->nullable();
            $table->string('flagged_req')->default('0')->nullable();
            $table->string('last_time')->nullable();
            $table->date('last_date')->nullable();
            $table->integer('prayer_sent_today')->nullable();
            $table->integer('prayed_count_today')->nullable();
            $table->integer('prayed_count_all')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prayer_requests');
    }
};
