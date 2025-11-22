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
use Illuminate\Support\Facades\Mail;
use App\Mail\PhotoRejectedMail;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AdminController extends Controller
{
    /**
     * Elimina un utente (solo admin)
     */
    public function deleteUser($userId)
    {
        try {
            $user = User::findOrFail($userId);
            $currentUser = Auth::user();
            // Protezione: un admin non può eliminare se stesso
            if ($user->id === $currentUser->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Non puoi eliminare il tuo stesso account da admin!',
                ], 403);
            }
            // Protezione: non eliminare l'ultimo admin attivo
            if ($user->role === 'admin' && $user->is_active) {
                $activeAdminsCount = User::where('role', 'admin')->where('is_active', true)->count();
                if ($activeAdminsCount <= 1) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Non puoi eliminare l\'ultimo amministratore attivo! Crea prima un altro admin.',
                    ], 403);
                }
            }
            $user->delete();
            return response()->json([
                'success' => true,
                'message' => 'Utente eliminato con successo.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Errore durante l\'eliminazione utente',
                'details' => $e->getMessage()
            ], 500);
        }
    }
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
                $stats['entries']['pending'] = Entry::where('moderation_status', 'pending_review')->count();
                $stats['entries']['rejected'] = Entry::where('moderation_status', 'rejected')->count();
            } catch (\Exception $e) {
                // Se i campi di moderazione non esistono, usiamo valori di default
                $stats['entries']['approved'] = 0;
                $stats['entries']['pending'] = $stats['entries']['total'];
                $stats['entries']['rejected'] = 0;
            }

            // Aggiungiamo statistiche sui crediti
            try {
                $stats['credits'] = [
                    'total_credits_distributed' => User::sum('photo_credits'),
                    'users_with_credits' => User::where('photo_credits', '>', 0)->count(),
                    'entries_that_gave_credits' => Entry::where('credit_given', true)->count(),
                    'average_credits_per_user' => User::where('photo_credits', '>', 0)->avg('photo_credits'),
                    'max_credits_single_user' => User::max('photo_credits')
                ];
            } catch (\Exception $e) {
                $stats['credits'] = [
                    'total_credits_distributed' => 0,
                    'users_with_credits' => 0,
                    'entries_that_gave_credits' => 0,
                    'average_credits_per_user' => 0,
                    'max_credits_single_user' => 0
                ];
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
        // Validazione ID entry
        if (!is_numeric($entryId) || $entryId <= 0) {
            return response()->json([
                'success' => false,
                'error' => 'ID entry non valido',
                'code' => 'INVALID_ENTRY_ID'
            ], 400);
        }

        // Validazione input
        try {
            $request->validate([
                'action' => 'required|in:approve,reject,pending',
                'reason' => 'nullable|string|max:500'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Dati di input non validi',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            // Verifica esistenza entry
            $entry = Entry::findOrFail($entryId);

            // Verifica autorizzazioni (già controllato dal middleware, ma doppio controllo)
            $user = Auth::user();
            if (!in_array($user->role, ['admin', 'moderator'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Non hai i permessi per moderare contenuti',
                    'code' => 'INSUFFICIENT_PERMISSIONS'
                ], 403);
            }

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

            // 🎯 GESTIONE CREDITI: Controlliamo PRIMA dell'update!
            $shouldGiveCredit = false;
            $shouldRemoveCredit = false;

            if ($request->action === 'reject' && !$entry->credit_given) {
                // L'entry NON ha mai dato credito E stiamo rifiutando → diamo credito
                $shouldGiveCredit = $this->shouldGiveCreditForRejection($entry);
            } elseif ($request->action === 'approve' && $entry->credit_given) {
                // L'entry HA GIÀ DATO credito E stiamo approvando → rimuoviamo il credito
                $shouldRemoveCredit = true;
            }

            // Ora aggiorniamo l'entry
            $entry->update($updateData);

            // Gestiamo i crediti DOPO l'update
            if ($shouldGiveCredit) {
                $user = $entry->user;
                $user->increment('photo_credits');
                // Aggiorna le note sui crediti (aggiunta credito)
                $creditNote = "Credito aggiunto manualmente da admin";
                $existingNotes = $user->credit_notes ? $user->credit_notes . "\n" : '';
                $user->update([
                    'credit_notes' => $existingNotes . date('Y-m-d H:i:s') . ': ' . $creditNote
                ]);

                // 🎯 IMPORTANTE: Segniamo che questa entry ha dato un credito
                $entry->update(['credit_given' => true]);

                // Aggiorna le note sui crediti
                $creditNote = "Credito assegnato per foto rifiutata (Entry #{$entry->id}) - Motivo: " . ($request->reason ?: 'Non specificato');
                $existingNotes = $user->credit_notes ? $user->credit_notes . "\n" : '';
                $user->update([
                    'credit_notes' => $existingNotes . date('Y-m-d H:i:s') . ': ' . $creditNote
                ]);

                Log::info('Credito assegnato', [
                    'user_id' => $user->id,
                    'entry_id' => $entry->id,
                    'new_credits' => $user->fresh()->photo_credits,
                    'credit_given_flag' => true
                ]);

                // 📧 Invia email di notifica all'utente
                try {
                    Mail::to($user->email)->send(new PhotoRejectedMail(
                        $entry->fresh(),
                        $request->reason ?: 'Non specificato',
                        1 // crediti assegnati
                    ));

                    Log::info('Email di rifiuto foto inviata', [
                        'user_id' => $user->id,
                        'entry_id' => $entry->id,
                        'email' => $user->email
                    ]);
                } catch (\Exception $e) {
                    Log::error('Errore invio email rifiuto foto', [
                        'user_id' => $user->id,
                        'entry_id' => $entry->id,
                        'error' => $e->getMessage()
                    ]);
                }
            } elseif ($shouldRemoveCredit) {
                $user = $entry->user;

                // Controlliamo che l'utente abbia crediti da scalare
                if ($user->photo_credits > 0) {
                    $user->decrement('photo_credits');

                    // 🎯 IMPORTANTE: Segniamo che questa entry NON ha più dato un credito
                    $entry->update(['credit_given' => false]);

                    // Aggiorna le note sui crediti
                    $creditNote = "Credito rimosso per foto approvata dopo precedente rifiuto (Entry #{$entry->id}) - Motivo: " . ($request->reason ?: 'Foto approvata');
                    $existingNotes = $user->credit_notes ? $user->credit_notes . "\n" : '';
                    $user->update([
                        'credit_notes' => $existingNotes . date('Y-m-d H:i:s') . ': ' . $creditNote
                    ]);

                    Log::info('Credito rimosso', [
                        'user_id' => $user->id,
                        'entry_id' => $entry->id,
                        'new_credits' => $user->fresh()->photo_credits,
                        'reason' => 'Foto approvata dopo precedente rifiuto',
                        'credit_given_flag' => false
                    ]);
                } else {
                    Log::warning('Tentativo di rimuovere credito ma utente ha 0 crediti', [
                        'user_id' => $user->id,
                        'entry_id' => $entry->id,
                        'current_credits' => $user->photo_credits
                    ]);
                }
            }

            $messages = [
                'approve' => 'Contenuto approvato' . ($shouldRemoveCredit ? ' - Credito precedente rimosso' : ''),
                'reject' => 'Contenuto rifiutato' . ($shouldGiveCredit ? ' - Credito assegnato all\'utente' : ''),
                'pending' => 'Contenuto rimesso in attesa'
            ];

            return response()->json([
                'success' => true,
                'message' => $messages[$request->action],
                'entry' => $entry->fresh()
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Entry non trovata',
                'code' => 'ENTRY_NOT_FOUND'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Errore moderazione entry', [
                'entry_id' => $entryId,
                'action' => $request->action ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Errore interno durante la moderazione',
                'code' => 'MODERATION_ERROR'
            ], 500);
        }
    }

    /**
     * Determina se dare un credito per una foto rifiutata
     * Credito solo se l'utente aveva già pagato (foto era approved o pending dopo AI)
     */
    private function shouldGiveCreditForRejection($entry)
    {
        // Logica per determinare se l'utente aveva già pagato:
        // 1. Se la foto era 'approved' prima del rifiuto → aveva pagato
        // 2. Se la foto era 'pending' dopo AI → aveva pagato  
        // 3. Se la foto era sempre 'rejected' → non aveva pagato

        // Per ora, assumiamo che se la foto esiste nel DB e non è già rejected, 
        // allora l'utente aveva pagato (approved o pending post-AI)

        // Nei sistemi reali, dovresti controllare:
        // - Lo stato precedente della foto
        // - Se esiste una transazione associata
        // - Il campo processing_status per capire se ha passato AI

        return true; // Per ora diamo sempre credito per test
    }

    /**
     * Analytics dettagliati sui crediti
     */
    public function creditAnalytics()
    {
        try {
            // Statistiche generali
            $generalStats = [
                'total_credits_distributed' => User::sum('photo_credits'),
                'users_with_credits' => User::where('photo_credits', '>', 0)->count(),
                'total_users' => User::count(),
                'entries_that_gave_credits' => Entry::where('credit_given', true)->count(),
                'rejected_entries_total' => Entry::where('moderation_status', 'rejected')->count(),
                'average_credits_per_user' => round(User::where('photo_credits', '>', 0)->avg('photo_credits'), 2),
                'max_credits_single_user' => User::max('photo_credits')
            ];

            // Top 10 utenti con più crediti
            $topUsers = User::where('photo_credits', '>', 0)
                ->orderBy('photo_credits', 'desc')
                ->take(10)
                ->get(['id', 'name', 'email', 'photo_credits'])
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'credits' => $user->photo_credits
                    ];
                });

            // Distribuzione crediti per range
            $creditDistribution = [
                '1-2_credits' => User::whereBetween('photo_credits', [1, 2])->count(),
                '3-5_credits' => User::whereBetween('photo_credits', [3, 5])->count(),
                '6-10_credits' => User::whereBetween('photo_credits', [6, 10])->count(),
                '11+_credits' => User::where('photo_credits', '>', 10)->count(),
            ];

            // Entries recenti che hanno dato crediti
            $recentCreditEntries = Entry::where('credit_given', true)
                ->with(['user:id,name,email', 'contest:id,title'])
                ->orderBy('updated_at', 'desc')
                ->take(20)
                ->get()
                ->map(function ($entry) {
                    return [
                        'entry_id' => $entry->id,
                        'title' => $entry->title ?: 'Senza titolo',
                        'user_name' => $entry->user->name,
                        'user_email' => $entry->user->email,
                        'contest_title' => $entry->contest->title ?? 'Contest eliminato',
                        'moderated_at' => $entry->moderated_at,
                        'moderation_reason' => $entry->moderation_reason
                    ];
                });

            // Movimenti di crediti per periodo (ultimi 30 giorni)
            $creditMovements = User::where('credit_notes', '!=', '')
                ->where('updated_at', '>=', now()->subDays(30))
                ->get(['id', 'name', 'email', 'photo_credits', 'credit_notes', 'updated_at'])
                ->map(function ($user) {
                    $lines = array_filter(explode("\n", $user->credit_notes));
                    $recentNotes = array_slice(array_reverse($lines), 0, 5); // Ultime 5 note

                    return [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'email' => $user->email,
                        'current_credits' => $user->photo_credits,
                        'recent_movements' => $recentNotes,
                        'last_update' => $user->updated_at
                    ];
                });

            return response()->json([
                'success' => true,
                'analytics' => [
                    'general_stats' => $generalStats,
                    'top_users' => $topUsers,
                    'credit_distribution' => $creditDistribution,
                    'recent_credit_entries' => $recentCreditEntries,
                    'recent_movements' => $creditMovements->take(15) // Limita ai primi 15
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Errore nel caricamento degli analytics crediti',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dettaglio utente per admin: dati, contest, foto
     */
    public function userDetail($userId)
    {
        Log::info('Admin userDetail start', ['userId' => $userId]);
        try {
            $user = User::findOrFail($userId);
            Log::info('Admin userDetail user trovato', ['user' => $user]);
            // Contest a cui partecipa (entries con user_id)
            $contests = \App\Models\Contest::whereHas('entries', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })->get(['id', 'title', 'start_date', 'end_date', 'status']);
            Log::info('Admin userDetail contests', ['contests_count' => $contests->count()]);
            // Foto caricate (entries)
            $photos = \App\Models\Entry::where('user_id', $userId)
                ->get([
                    'id',
                    'title',
                    'photo_url',
                    'thumbnail_url',
                    'created_at',
                    'moderation_status',
                    'contest_id',
                    'payment_method',
                    'votes_count',
                    'likes_count',
                    'views_count',
                    'description',
                    'location'
                ]);
            Log::info('Admin userDetail photos', ['photos_count' => $photos->count()]);
            return response()->json([
                'success' => true,
                'user' => $user,
                'photo_credits' => $user->photo_credits,
                'contests' => $contests,
                'photos' => $photos
            ]);
        } catch (\Exception $e) {
            Log::error('Admin userDetail error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'error' => 'Errore nel caricamento dettagli utente',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
