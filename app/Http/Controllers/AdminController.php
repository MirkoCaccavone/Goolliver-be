<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Contest;
use App\Models\Entry;
use App\Models\Vote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    /**
     * Dashboard principale con statistiche generali
     */
    public function dashboard()
    {
        try {
            // Statistiche base che sicuramente funzionano
            $stats = [
                'users' => [
                    'total' => User::where('role', 'user')->count(), // Solo utenti normali
                    'active' => User::where('is_active', true)->where('role', 'user')->count(),
                    'new_today' => User::whereDate('created_at', now()->toDateString())->where('role', 'user')->count(),
                ],
                'contests' => [
                    'total' => Contest::count(),
                    'recent' => Contest::orderBy('created_at', 'desc')->take(5)->get()
                ],
                'entries' => [
                    'total' => Entry::count(),
                ],
                'votes' => [
                    'total' => Vote::count(),
                    'today' => Vote::whereDate('created_at', now()->toDateString())->count(),
                ]
            ];

            // Aggiungiamo statistiche per ruolo se il campo esiste
            try {
                $stats['users']['by_role'] = User::select('role', DB::raw('count(*) as count'))
                    ->groupBy('role')
                    ->pluck('count', 'role');
            } catch (\Exception $e) {
                $stats['users']['by_role'] = [];
            }

            // Aggiungiamo statistiche di moderazione se i campi esistono
            try {
                $stats['entries']['approved'] = Entry::where('moderation_status', 'approved')->count();
                $stats['entries']['pending'] = Entry::where('moderation_status', 'pending')->count();
                $stats['entries']['rejected'] = Entry::where('moderation_status', 'rejected')->count();
            } catch (\Exception $e) {
                // Se i campi di moderazione non esistono, usiamo valori di default
                $stats['entries']['approved'] = 0;
                $stats['entries']['pending'] = $stats['entries']['total'];
                $stats['entries']['rejected'] = 0;
            }

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Errore nel caricamento delle statistiche',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gestione utenti
     */
    public function users(Request $request)
    {
        try {
            $query = User::query();

            // Filtri
            if ($request->has('role') && $request->role !== 'all') {
                $query->where('role', $request->role);
            }

            if ($request->has('active') && $request->active !== 'all') {
                $query->where('is_active', $request->active === 'true');
            }

            // Ricerca
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                });
            }

            // Ordinamento
            $orderBy = $request->get('sort_by', 'created_at');
            $orderDir = $request->get('sort_dir', 'desc');
            $query->orderBy($orderBy, $orderDir);

            // Paginazione
            $users = $query->paginate($request->get('per_page', 20));

            return response()->json([
                'success' => true,
                'users' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Errore nel caricamento degli utenti',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aggiorna ruolo utente
     */
    public function updateUserRole(Request $request, $userId)
    {
        $request->validate([
            'role' => 'required|in:user,moderator,admin'
        ]);

        try {
            $user = User::findOrFail($userId);

            // 🛡️ PROTEZIONE: L'ultimo admin non può declassare se stesso
            if ($user->role === 'admin' && $request->role !== 'admin') {
                $activeAdminsCount = User::where('role', 'admin')
                    ->where('is_active', true)
                    ->count();

                if ($activeAdminsCount <= 1 && $user->is_active) {
                    return response()->json([
                        'success' => false,
                        'error' => '👑 Non puoi rimuovere l\'ultimo amministratore! Crea prima un altro admin.',
                    ], 403);
                }
            }

            $user->update([
                'role' => $request->role
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ruolo aggiornato con successo',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Errore nell\'aggiornamento del ruolo',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Attiva/disattiva utente
     */
    public function toggleUserStatus(Request $request, $userId)
    {
        try {
            $user = User::findOrFail($userId);
            $currentUser = Auth::user();

            // 🛡️ PROTEZIONE: Un admin non può disattivare se stesso!
            if ($user->id === $currentUser->id && $user->is_active) {
                return response()->json([
                    'success' => false,
                    'error' => '🚫 Non puoi disattivare il tuo stesso account! Rischi di bloccarti fuori dal sistema.',
                ], 403);
            }

            // 🛡️ PROTEZIONE: Impedisce di disattivare l'ultimo admin attivo
            if ($user->role === 'admin' && $user->is_active) {
                $activeAdminsCount = User::where('role', 'admin')
                    ->where('is_active', true)
                    ->count();

                if ($activeAdminsCount <= 1) {
                    return response()->json([
                        'success' => false,
                        'error' => '⚠️ Non puoi disattivare l\'ultimo amministratore attivo! Crea prima un altro admin.',
                    ], 403);
                }
            }

            $user->update([
                'is_active' => !$user->is_active
            ]);

            return response()->json([
                'success' => true,
                'message' => $user->is_active ? 'Utente attivato' : 'Utente disattivato',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Errore nell\'aggiornamento dello status',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gestione contest
     */
    public function contests(Request $request)
    {
        try {
            $query = Contest::withCount('entries');

            // Aggiungiamo il conteggio voti se la relazione esiste
            try {
                $query->withCount('votes');
            } catch (\Exception $e) {
                // Ignora se la relazione votes non funziona
            }

            $contests = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 10));

            return response()->json([
                'success' => true,
                'contests' => $contests
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Errore nel caricamento dei contest',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dettagli completi di un contest specifico
     */
    public function contestDetails($contestId)
    {
        try {
            $contest = Contest::findOrFail($contestId);

            // Carica le entries con gestione errori
            $entries = collect();
            try {
                $entries = Entry::where('contest_id', $contestId)
                    ->with('user')
                    ->withCount('votes')
                    ->orderBy('votes_count', 'desc')
                    ->get();
            } catch (\Exception $e) {
                // Se c'è un errore con withCount('votes'), proviamo senza
                $entries = Entry::where('contest_id', $contestId)
                    ->with('user')
                    ->orderBy('created_at', 'desc')
                    ->get();
            }

            // Statistiche del contest con fallback
            $totalVotes = 0;
            try {
                $totalVotes = Vote::whereHas('entry', function ($query) use ($contestId) {
                    $query->where('contest_id', $contestId);
                })->count();
            } catch (\Exception $e) {
                $totalVotes = 0;
            }

            $stats = [
                'total_participants' => $entries->count(),
                'total_votes' => $totalVotes,
                'avg_votes_per_entry' => $entries->count() > 0 && $totalVotes > 0 ?
                    round($totalVotes / $entries->count(), 2) : 0,
                'most_voted_entry' => $entries->first(),
            ];

            // Dettagli partecipanti con le loro foto e voti
            $participants = $entries->map(function ($entry) {
                // Gestione sicura dei voti
                $votesData = [];
                $votesCount = $entry->votes_count ?? 0;

                try {
                    $votes = Vote::where('entry_id', $entry->id)->with('user')->get();
                    $votesData = $votes->map(function ($vote) {
                        return [
                            'user_name' => $vote->user ? $vote->user->name : 'Utente sconosciuto',
                            'voted_at' => $vote->created_at
                        ];
                    })->toArray();
                } catch (\Exception $e) {
                    $votesData = [];
                }

                return [
                    'entry_id' => $entry->id,
                    'user' => [
                        'id' => $entry->user ? $entry->user->id : 0,
                        'name' => $entry->user ? $entry->user->name : 'Utente sconosciuto',
                        'email' => $entry->user ? $entry->user->email : 'N/A',
                        'registration_date' => $entry->user ? $entry->user->created_at : null
                    ],
                    'photo' => [
                        'title' => $entry->title ?? 'Senza titolo',
                        'filename' => $entry->filename ?? 'FILE MANCANTE ⚠️',
                        'uploaded_at' => $entry->created_at,
                        'moderation_status' => $entry->moderation_status ?? 'pending',
                        'file_size' => $entry->file_size ?? null
                    ],
                    'votes' => [
                        'count' => $votesCount,
                        'voters' => $votesData
                    ],
                    'position' => null // Calcolato dopo
                ];
            });

            // Calcola le posizioni (ranking) - gestione sicura
            $participants = $participants->sortByDesc(function ($p) {
                return $p['votes']['count'] ?? 0;
            })->values();

            $participants = $participants->map(function ($participant, $index) {
                $participant['position'] = $index + 1;
                return $participant;
            });

            return response()->json([
                'success' => true,
                'contest' => [
                    'id' => $contest->id,
                    'title' => $contest->title ?? 'Contest senza titolo',
                    'description' => $contest->description ?? 'Nessuna descrizione disponibile',
                    'created_at' => $contest->created_at,
                    'updated_at' => $contest->updated_at
                ],
                'stats' => $stats,
                'participants' => $participants->toArray()
            ]);
        } catch (\Exception $e) {
            Log::error('Errore dettagli contest', [
                'contest_id' => $contestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Errore nel caricamento dei dettagli del contest',
                'details' => config('app.debug') ? $e->getMessage() : 'Errore interno del server'
            ], 500);
        }
    }

    /**
     * Gestione moderazione contenuti
     */
    public function moderation(Request $request)
    {
        try {
            $query = Entry::with(['user', 'contest']);

            if ($request->has('status') && $request->status !== 'all') {
                $query->where('moderation_status', $request->status);
            }

            $entries = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));

            return response()->json([
                'success' => true,
                'entries' => $entries
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Errore nel caricamento dei contenuti',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approva/rifiuta contenuto
     */
    public function moderateEntry(Request $request, $entryId)
    {
        $request->validate([
            'action' => 'required|in:approve,reject,pending',
            'reason' => 'nullable|string'
        ]);

        try {
            $entry = Entry::findOrFail($entryId);

            $statusMap = [
                'approve' => 'approved',
                'reject' => 'rejected',
                'pending' => 'pending'
            ];

            $status = $statusMap[$request->action];

            $updateData = [
                'moderation_status' => $status,
            ];

            // Solo per approve/reject aggiungiamo i dati del moderatore
            if ($request->action !== 'pending') {
                $updateData['moderation_reason'] = $request->reason;
                $updateData['moderated_at'] = now();
                $updateData['moderated_by'] = Auth::id();
            } else {
                // Reset per pending
                $updateData['moderation_reason'] = null;
                $updateData['moderated_at'] = null;
                $updateData['moderated_by'] = null;
            }

            $entry->update($updateData);

            $messages = [
                'approve' => 'Contenuto approvato',
                'reject' => 'Contenuto rifiutato',
                'pending' => 'Contenuto rimesso in attesa'
            ];

            return response()->json([
                'success' => true,
                'message' => $messages[$request->action],
                'entry' => $entry
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Errore nella moderazione del contenuto',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
