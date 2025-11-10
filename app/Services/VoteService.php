<?php

namespace App\Services;

use App\Models\Entry;
use App\Models\Vote;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class VoteService
{
    /**
     * Aggiungi o rimuovi un like
     */
    public function toggleLike(int $entryId, int $userId, ?string $ipAddress = null, ?string $userAgent = null): array
    {
        try {
            DB::beginTransaction();

            $entry = Entry::findOrFail($entryId);

            // REGOLA 1: Non puoi votare la tua foto
            if ($entry->user_id == $userId) {
                throw new \InvalidArgumentException('Non puoi votare la tua stessa foto');
            }

            // REGOLA 2: Verifica se l'utente partecipa al contest (ha almeno una foto)
            $userParticipates = Entry::where('contest_id', $entry->contest_id)
                ->where('user_id', $userId)
                ->exists();

            if (!$userParticipates) {
                throw new \InvalidArgumentException('Solo i partecipanti al contest possono votare');
            }

            // REGOLA 3: Un utente può avere UN SOLO voto per contest
            // Prima, trova tutte le entries del contest
            $contestEntryIds = Entry::where('contest_id', $entry->contest_id)->pluck('id');

            // Poi trova il voto dell'utente in questo contest
            $existingVoteInContest = Vote::where('user_id', $userId)
                ->where('vote_type', Vote::TYPE_LIKE)
                ->whereIn('entry_id', $contestEntryIds)
                ->first();

            if ($existingVoteInContest) {
                if ($existingVoteInContest->entry_id == $entryId) {
                    // Rimuovi il voto dalla stessa foto
                    $existingVoteInContest->delete();
                    $this->updateEntryLikeStats($entry, -1);

                    DB::commit();
                    return [
                        'action' => 'removed',
                        'liked' => false,
                        'likes_count' => $entry->fresh()->likes_count,
                        'message' => 'Voto rimosso'
                    ];
                } else {
                    // Sposta il voto da un'altra foto a questa
                    $oldEntry = Entry::findOrFail($existingVoteInContest->entry_id);
                    $existingVoteInContest->delete();
                    $this->updateEntryLikeStats($oldEntry, -1);                    // Aggiungi nuovo voto
                    Vote::create([
                        'user_id' => $userId,
                        'entry_id' => $entryId,
                        'vote_type' => Vote::TYPE_LIKE,
                        'ip_address' => $ipAddress,
                        'user_agent' => $userAgent
                    ]);
                    $this->updateEntryLikeStats($entry, 1);

                    DB::commit();
                    return [
                        'action' => 'moved',
                        'liked' => true,
                        'likes_count' => $entry->fresh()->likes_count,
                        'message' => 'Voto spostato dalla foto precedente'
                    ];
                }
            } else {
                // Primo voto dell'utente in questo contest
                Vote::create([
                    'user_id' => $userId,
                    'entry_id' => $entryId,
                    'vote_type' => Vote::TYPE_LIKE,
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent
                ]);

                $this->updateEntryLikeStats($entry, 1);

                DB::commit();
                return [
                    'action' => 'added',
                    'liked' => true,
                    'likes_count' => $entry->fresh()->likes_count,
                    'message' => 'Voto aggiunto'
                ];
            }
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Errore toggle like', ['error' => $e->getMessage(), 'entry_id' => $entryId]);
            throw $e;
        }
    }



    /**
     * Aggiorna statistiche like dell'entry
     */
    private function updateEntryLikeStats(Entry $entry, int $increment): void
    {
        if ($increment < 0) {
            // Decremenento: assicuriamoci di non andare sotto 0
            $newCount = max(0, $entry->likes_count + $increment);
            $entry->likes_count = $newCount;
            $entry->save();
        } else {
            // Incremento: possiamo usare increment() normalmente
            $entry->increment('likes_count', $increment);
        }

        $this->recalculateVoteScore($entry);
    }



    /**
     * Ricalcola il punteggio complessivo per il ranking
     */
    private function recalculateVoteScore(Entry $entry): void
    {
        // Algoritmo di scoring semplificato: solo likes
        // Il punteggio è uguale al numero di like
        $entry->vote_score = $entry->likes_count;
        $entry->save();
    }

    /**
     * Ottieni statistiche di voto per un'entry
     */
    public function getVoteStats(int $entryId, ?int $userId = null): array
    {
        $entry = Entry::findOrFail($entryId);

        $stats = [
            'likes_count' => $entry->likes_count,
            'vote_score' => $entry->vote_score,
        ];

        if ($userId) {
            $stats['user_liked'] = $entry->hasUserVoted($userId, Vote::TYPE_LIKE);
        }

        return $stats;
    }

    /**
     * Ottieni info sul voto dell'utente in un contest
     */
    public function getUserVoteInContest(int $contestId, int $userId): ?array
    {
        $userVote = Vote::join('entries', 'votes.entry_id', '=', 'entries.id')
            ->where('entries.contest_id', $contestId)
            ->where('votes.user_id', $userId)
            ->where('votes.vote_type', Vote::TYPE_LIKE)
            ->select('votes.*', 'entries.title as entry_title')
            ->first();

        if ($userVote) {
            return [
                'has_voted' => true,
                'voted_entry_id' => $userVote->entry_id,
                'voted_entry_title' => $userVote->entry_title
            ];
        }

        return ['has_voted' => false];
    }

    /**
     * Ottieni top entries per contest
     */
    public function getTopEntries(int $contestId, int $limit = 10, string $orderBy = 'vote_score'): array
    {
        $query = Entry::where('contest_id', $contestId)
            ->where('moderation_status', 'approved');

        switch ($orderBy) {
            case 'likes':
                $query->orderByLikes();
                break;
            case 'vote_score':
            default:
                $query->orderByVoteScore();
                break;
        }

        return $query->limit($limit)->get()->toArray();
    }
}
