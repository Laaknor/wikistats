<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Allows charts to define series by WikidataTracking (template charts on all wikis).
     */
    public function up(): void
    {
        Schema::table('charts', function (Blueprint $table) {
            $table->foreignId('site_id')->nullable()->change();
        });

        Schema::create('chart_wikidata_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wikidata_tracking_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('label')->nullable();
            $table->string('color')->nullable();
            $table->timestamps();

            $table->unique(['chart_id', 'wikidata_tracking_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chart_wikidata_tracking');

        Schema::table('charts', function (Blueprint $table) {
            $table->foreignId('site_id')->nullable(false)->change();
        });
    }
};
