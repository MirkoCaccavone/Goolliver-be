<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Contest;

class ContestController extends Controller
{
    public function index()
    {
        return response()->json(Contest::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'max_participants' => 'required|integer|min:1',
            'prize' => 'nullable|string',
            'status' => 'nullable|string|in:open,voting,closed',
        ]);

        $contest = Contest::create([
            'title' => $request->title,
            'description' => $request->description,
            'max_participants' => $request->max_participants,
            'prize' => $request->prize,
            'status' => $request->status ?? 'open', // default value
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
}
