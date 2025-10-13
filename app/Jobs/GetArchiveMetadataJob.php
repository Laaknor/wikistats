<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\ArchiveFile;
use App\Models\ArchiveItem;

class GetArchiveMetadataJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
        $archiveItem = ArchiveItem::where('is_active', true)->where('last_sync',null)->take(2)->inRandomOrder()->get();
        foreach($archiveItem AS $item) {
            $metadata = exec ('ia metadata '.$item->identifier);
            $data = json_decode($metadata, true);
            
            foreach($data['files'] as $file) {
                $size = $file['size'] ?? 0;
                $dbname = strstr($file['name'], "-", true) ?? null;
                ArchiveFile::updateOrCreate([
                    'filename' => $file['name'],
                    'archive_item_id' => $item->id,
                ],
                [
                    'size' => $size,
                    'dbname' => $dbname,
                ]
                );
            }
            $item->last_sync = now();
            $item->publish_date = $data['metadata']['date'];
            
            $item->save();
        }
    }
}
