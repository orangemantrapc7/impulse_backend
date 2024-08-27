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
    Schema::create('users', function (Blueprint $table) {
        $table->increments('id');
        $table->string('mobile_uid')->nullable();
        $table->string('name')->nullable();
        $table->string('email', 191)->unique()->nullable();
        $table->string('mobile')->nullable();
        $table->string('password')->nullable();
        $table->string('device_id')->nullable();
        $table->string('device_token')->nullable();
        $table->rememberToken()->nullable();
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
