<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ArchiveItem;

class wikistats_import_archive extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wikistats:import_archive';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import list of archive.org Items';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $filename = base_path().'/wikimediadownloads.txt';
        if(!file_exists($filename)) {
            $this->error('File not found: '.$filename);
            return;
        }
        $file = fopen($filename, 'r');
        while($line = fgets($file)) {
            $data = json_decode($line, true);
            if(Str::startsWith($data['identifier'], 'incr')) {
                continue;
            }
            $archiveItem = ArchiveItem::firstOrCreate([
                'identifier' => $data['identifier'],
            ]);
        }
    }
}
