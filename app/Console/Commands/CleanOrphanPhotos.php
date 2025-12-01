<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\Entry;

class CleanOrphanPhotos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'photos:clean-orphans';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rimuove i file foto (original e thumbnail) che non hanno più una entry associata nel database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Inizio pulizia file orfani...');

        $disk = Storage::disk('public');
        $orphans = 0;

        // Pulizia original
        $originalFiles = $disk->files('photos/original');
        foreach ($originalFiles as $file) {
            $filename = basename($file);
            $exists = Entry::where('photo_url', 'like', "%$filename%")
                ->exists();
            if (!$exists) {
                $disk->delete($file);
                $this->line("Cancellato orfano (original): $file");
                $orphans++;
            }
        }

        // Pulizia thumbnail
        $thumbFiles = $disk->files('photos/thumbnails');
        foreach ($thumbFiles as $file) {
            $filename = basename($file);
            // Cerca solo per il nome file esatto nel campo thumbnail_url
            $exists = Entry::where('thumbnail_url', $filename)->exists();
            if (!$exists) {
                $disk->delete($file);
                $this->line("Cancellato orfano (thumbnail): $file");
                $orphans++;
            }
        }

        $this->info("Pulizia completata. File orfani rimossi: $orphans");
    }
}
