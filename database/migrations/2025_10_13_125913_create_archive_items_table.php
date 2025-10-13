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
        Schema::create('archive_items', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('identifier')->unique();
            $table->date('publish_date')->nullable();
            $table->dateTime('last_sync')->nullable();
            $table->json('collection')->nullable();
            $table->boolean('is_active')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archive_items');
    }
};
