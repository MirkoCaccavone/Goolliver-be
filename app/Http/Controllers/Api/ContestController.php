<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Log;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Contest;
use App\Models\Entry;
use App\Models\Vote;

class ContestController extends Controller
{
    /**
     * Aggiorna lo stato dei contest: upcoming -> active se la data di inizio è oggi/passata
     */
    public static function updateContestStatuses()
    {
        $now = now();
        // upcoming -> active se la data di inizio è oggi/passata
        Contest::where('status', 'upcoming')
            ->whereDate('start_date', '<=', $now)
            ->update(['status' => 'active']);

        // active -> pending_voting se raggiunto il numero massimo di partecipanti
        $pendingVotingContests = Contest::where('status', 'active')
            ->whereColumn('current_participants', '>=', 'max_participants')
            ->get();
        foreach ($pendingVotingContests as $contest) {
            $contest->status = 'pending_voting';
            $contest->save();
            // Notifica tutti gli admin
            foreach (\App\Models\User::admins() as $admin) {
                Log::info('Creazione notifica admin', [
                    'admin_id' => $admin->id,
                    'contest_id' => $contest->id,
                    'contest_title' => $contest->title
                ]);
                $admin->notifications()->create([
                    'type' => 'contest_pending_voting',
                    'title' => $contest->title,
                    'message' => "Il contest '{$contest->title}' ha raggiunto il numero massimo di partecipanti. Scegli la durata della votazione.",
                    'data' => [
                        'contest_id' => $contest->id,
                        'title' => $contest->title,
                        'message' => "Il contest '{$contest->title}' ha raggiunto il numero massimo di partecipanti. Scegli la durata della votazione.",
                        'url' => "/admin/contests/{$contest->id}/details"
                    ]
                ]);
            }
        }

        // voting -> ended se la voting_end_date è passata
        Contest::where('status', 'voting')
            ->whereNotNull('voting_end_date')
            ->where('voting_end_date', '<', $now)
            ->update(['status' => 'ended']);
    }

    public function index()
    {
        self::updateContestStatuses();
        return response()->json(Contest::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'max_participants' => 'required|integer|min:1',
            'prize' => 'nullable|string',
            'entry_fee' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:active,upcoming,ended,voting',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date', // ora facoltativo
        ]);

        // Determina lo stato contest in base alla data di inizio
        $now = now();
        $startDate = $request->start_date;
        $status = $request->status;
        if (!$status) {
            if ($startDate > $now) {
                $status = 'upcoming';
            } else {
                $status = 'active';
            }
        }

        $contest = Contest::create([
            'title' => $request->title,
            'description' => $request->description,
            'max_participants' => $request->max_participants,
            'prize' => $request->prize,
            'entry_fee' => $request->entry_fee ?? 0,
            'status' => $status,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date, // può essere null
        ]);

        return response()->json($contest, 201);
    }

    public function show($id)
    {
        return response()->json(Contest::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $contest = Contest::findOrFail($id);
        $contest->update($request->all());
        return response()->json($contest);
    }

    public function destroy($id)
    {
        $contest = Contest::findOrFail($id);
        $contest->delete();
        return response()->json(['message' => 'Concorso eliminato']);
    }

    /**
     * Ottieni tutte le entries pubbliche di un contest
     */
    public function entries($id)
    {
        $contest = Contest::findOrFail($id);
        $entries = $contest->entries()->public()->get();

        $userId = request()->user() ? request()->user()->id : null;
        if ($userId) {
            // Preleva tutti gli entry_id votati dall'utente per questo contest
            $votedEntryIds = Vote::where('user_id', $userId)
                ->whereIn('entry_id', $entries->pluck('id'))
                ->where('vote_type', 'like')
                ->pluck('entry_id')
                ->toArray();
        } else {
            $votedEntryIds = [];
        }

        // Aggiungi la proprietà voted_by_user a ciascuna entry
        $entries->transform(function ($entry) use ($votedEntryIds) {
            $entry->voted_by_user = in_array($entry->id, $votedEntryIds);
            return $entry;
        });

        return response()->json($entries);
    }
}
