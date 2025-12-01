<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class CleanOrphanAvatars extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'avatars:clean-orphans';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rimuove i file avatar che non hanno più un utente associato nel database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Inizio pulizia avatar orfani...');

        $disk = Storage::disk('public');
        $orphans = 0;


        // Recupera tutti i nomi file avatar usati dagli utenti (solo basename, non nulli e non vuoti)
        $usedAvatars = User::whereNotNull('avatar')
            ->where('avatar', '!=', '')
            ->pluck('avatar')
            ->map(function ($avatar) {
                return basename($avatar);
            })
            ->unique()
            ->toArray();

        // Ottieni tutti i file nella cartella public/avatars
        $avatarFiles = $disk->files('avatars');

        foreach ($avatarFiles as $file) {
            $filename = basename($file);
            // Ignora file di sistema
            if ($filename === '.gitignore') {
                continue;
            }
            if (!in_array($filename, $usedAvatars)) {
                $disk->delete($file);
                $this->line("Cancellato avatar orfano: $file");
                $orphans++;
            }
        }

        $this->info("Pulizia completata. Avatar orfani rimossi: $orphans");
    }
}
