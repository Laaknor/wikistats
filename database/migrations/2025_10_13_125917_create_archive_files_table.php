<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\ArchiveItem;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('archive_files', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignIdFor(ArchiveItem::class)->index();
            $table->string('filename')->index();
            $table->dateTime('last_sync')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archive_files');
    }
};
