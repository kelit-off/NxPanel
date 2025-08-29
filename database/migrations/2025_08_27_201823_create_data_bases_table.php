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
        Schema::create('data_bases', function (Blueprint $table) {
            $table->id();
            $table->integer("site_id");
            $table->string('database_name');
            $table->string('username');
            $table->string('password');
            $table->string('host')->default('127.0.0.1');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_bases');
    }
};
