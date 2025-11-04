<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;

class TransactionController extends Controller
{
    // Tutte le transazioni
    public function index()
    {
        $transactions = Transaction::with(['user', 'contest'])->get();
        return response()->json($transactions);
    }

    // Registra una nuova transazione
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'contest_id' => 'required|integer|exists:contests,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string', // es. PayPal, ApplePay, GooglePay
            'status' => 'required|string', // es. "completed", "failed", "pending"
            'transaction_code' => 'nullable|string|max:255',
        ]);

        $transaction = Transaction::create($request->all());
        return response()->json($transaction, 201);
    }

    // Singola transazione
    public function show($id)
    {
        $transaction = Transaction::with(['user', 'contest'])->findOrFail($id);
        return response()->json($transaction);
    }

    // Elimina (facoltativo)
    public function destroy($id)
    {
        $transaction = Transaction::findOrFail($id);
        $transaction->delete();
        return response()->json(['message' => 'Transazione eliminata']);
    }
}
