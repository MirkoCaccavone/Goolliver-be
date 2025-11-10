<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Contest;
use App\Models\Entry;
use App\Models\Vote;
use Illuminate\Support\Facades\DB;

class VoteTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding vote test data...');

        DB::transaction(function () {
            // Crea utenti di test se non esistono
            $users = [];
            for ($i = 1; $i <= 5; $i++) {
                $users[] = User::firstOrCreate([
                    'email' => "user{$i}@test.com"
                ], [
                    'name' => "Test User {$i}",
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                ]);
            }

            // Crea contest di test se non esiste
            $contest = Contest::firstOrCreate([
                'title' => 'Contest Demo Voting'
            ], [
                'description' => 'Contest di test per il sistema di voto',
                'start_at' => now()->subDays(7),
                'end_at' => now()->addDays(7),
                'max_participants' => 100,
                'prize' => 'Test Prize',
                'status' => 'open'
            ]);

            // Crea entries di test se non esistono
            $entries = [];
            $entryTitles = [
                'Tramonto sul Mare',
                'Montagne al Mattino',
                'Riflessi nel Lago',
                'Strada di Campagna',
                'Fiori di Primavera'
            ];

            foreach ($entryTitles as $index => $title) {
                $entries[] = Entry::firstOrCreate([
                    'contest_id' => $contest->id,
                    'user_id' => $users[$index % count($users)]->id,
                    'title' => $title
                ], [
                    'image_path' => 'test/photo_' . ($index + 1) . '.jpg',
                    'caption' => 'Foto di test per il sistema di voto: ' . $title,
                    'moderation_status' => 'approved',
                    'processing_status' => 'completed',
                ]);
            }

            $this->command->info("✅ Creati {$contest->title} con " . count($entries) . " foto");

            // Genera voti casuali per rendere il test realistico
            $this->generateRandomVotes($entries, $users);

            $this->command->info('🎉 Seeding completato! Usa http://localhost:8000/test-simple-voting.html');
        });
    }

    /**
     * Genera voti casuali per test realistici
     */
    private function generateRandomVotes(array $entries, array $users): void
    {
        $this->command->info('🗳️  Generando like casuali...');

        foreach ($entries as $entry) {
            // Genera likes casuali - solo 1 voto per utente (sistema semplificato)
            // Circa 2-3 utenti votano ogni entry
            $voters = array_rand($users, rand(2, 3));
            if (!is_array($voters)) {
                $voters = [$voters];
            }

            foreach ($voters as $voterIndex) {
                // Evita self-voting
                if ($users[$voterIndex]->id !== $entry->user_id) {
                    Vote::firstOrCreate([
                        'user_id' => $users[$voterIndex]->id,
                        'entry_id' => $entry->id,
                        'vote_type' => Vote::TYPE_LIKE
                    ], [
                        'ip_address' => '127.0.0.1',
                        'user_agent' => 'Test Seeder'
                    ]);
                }
            }

            // Aggiorna statistiche entry
            $this->updateEntryStatistics($entry);
        }

        $this->command->info('✅ Like generati e statistiche aggiornate');
    }

    /**
     * Aggiorna manualmente le statistiche dell'entry
     */
    private function updateEntryStatistics(Entry $entry): void
    {
        // Conta likes
        $likesCount = Vote::where('entry_id', $entry->id)
            ->where('vote_type', Vote::TYPE_LIKE)
            ->count();

        // Sistema semplificato: vote_score = likes_count
        $voteScore = $likesCount;

        // Aggiorna entry
        $entry->update([
            'likes_count' => $likesCount,
            'vote_score' => $voteScore,
        ]);
    }
}
