<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Site;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('siteinfos', function (Blueprint $table) {
            $table->foreignIdFor(Site::class);
            $table->string('info');
            $table->date('date');
            $table->integer('count')->default(0);
            $table->index(['site_id', 'info', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siteinfos');
    }
};
