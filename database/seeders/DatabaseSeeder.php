<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\WikidataTracking;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
            'item' => 'Q6867401', // {{referanseløs}}
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
      
    }
}
