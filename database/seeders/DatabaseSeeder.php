<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\WikidataTracking;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\UrlScan;
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        WikidataTracking::firstOrCreate([
            'item' => 'Q8235724', // {{relevans}}
            'type' => 'categorycount',
        ]);
        WikidataTracking::firstOrCreate([
            'item' => 'Q9884765', // {{referanseløs}}
            'type' => 'categorycount',
        ]);
        WikidataTracking::firstOrCreate([
            'item' => 'Q5897849', // {{tr}}
            'type' => 'categorycount',
        ]);
        WikidataTracking::firstOrCreate([
            'item' => 'Q9883690', // {{klargjør}}, {{utdyp}}, {{når}}, {{hvem}}, {{av hvem}}, {{hvor}}
            'type' => 'categorycount',
        ]);
        WikidataTracking::firstOrCreate([
            'item' => 'Q9879477', // {{død lenke}}
            'type' => 'categorycount',
        ]);
        WikidataTracking::firstOrCreate([
            'item' => 'Q9884723', // {{kildeløs}}
            'type' => 'categorycount',
        ]);
        WikidataTracking::firstOrCreate([
            'item' => 'Q9882887', // {{refforbedre}}
            'type' => 'categorycount',
        ]);
#        UrlScan::firstOrCreate([
#            'urltype' => 'normal',
#            'url' => 'https://archive.org/details/wikimediadownloads',
#        ]);
    }
}
