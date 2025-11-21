<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Contest;
use App\Models\Entry;

class ContestController extends Controller
{
    /**
     * Aggiorna lo stato dei contest: upcoming -> active se la data di inizio è oggi/passata
     */
    public static function updateContestStatuses()
    {
        $now = now();
        Contest::where('status', 'upcoming')
            ->whereDate('start_date', '<=', $now)
            ->update(['status' => 'active']);
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
            'end_date' => 'required|date|after_or_equal:start_date',
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
            'end_date' => $request->end_date,
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
        return response()->json($entries);
    }
}
