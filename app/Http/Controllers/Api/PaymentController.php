<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Entry;
use App\Models\Contest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class PaymentController extends Controller
{
    /**
     * Processa un pagamento per un'entry del contest
     */
    public function processPayment(Request $request): JsonResponse
    {
        $request->validate([
            'entry_id' => 'required|integer|exists:entries,id',
            'payment_method_id' => 'required|string', // Stripe Payment Method ID
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            // Configura Stripe
            Stripe::setApiKey(config('stripe.secret_key'));

            DB::beginTransaction();

            // Trova l'entry e verifica che appartenga all'utente
            $entry = Entry::with('contest')->findOrFail($request->entry_id);

            if ($entry->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorizzato ad effettuare il pagamento per questa entry'
                ], 403);
            }

            // Verifica che l'entry non sia già stata pagata
            if ($entry->payment_status === 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Il pagamento è già stato completato per questa entry'
                ], 400);
            }

            // Verifica che l'importo corrisponda al costo del contest
            $expectedAmount = $entry->contest->entry_fee ?? 0;
            if (abs($request->amount - $expectedAmount) > 0.01) {
                return response()->json([
                    'success' => false,
                    'message' => "L'importo non corrisponde al costo del contest (€{$expectedAmount})"
                ], 400);
            }

            // Crea Payment Intent con Stripe
            $paymentIntent = PaymentIntent::create([
                'amount' => $request->amount * 100, // Stripe usa centesimi
                'currency' => config('stripe.currency', 'eur'),
                'payment_method' => $request->payment_method_id,
                'confirmation_method' => 'manual',
                'confirm' => true,
                'return_url' => config('app.frontend_url') . '/payment-success',
                'metadata' => [
                    'entry_id' => $entry->id,
                    'user_id' => Auth::id(),
                    'contest_id' => $entry->contest_id,
                    'contest_title' => $entry->contest->title ?? 'Contest'
                ]
            ]);

            // Verifica che il pagamento sia stato confermato
            if ($paymentIntent->status !== 'succeeded') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pagamento non riuscito. Controlla i dati della carta.',
                    'payment_intent' => [
                        'id' => $paymentIntent->id,
                        'status' => $paymentIntent->status
                    ]
                ], 400);
            }

            // Aggiorna l'entry con i dati di pagamento
            $entry->update([
                'payment_status' => 'completed',
                'paid_at' => now(),
                'payment_amount' => $request->amount,
                'payment_method' => 'stripe_card',
                'transaction_id' => $paymentIntent->id
            ]);

            DB::commit();

            Log::info('Pagamento Stripe completato', [
                'entry_id' => $entry->id,
                'user_id' => Auth::id(),
                'amount' => $request->amount,
                'stripe_payment_intent_id' => $paymentIntent->id,
                'payment_method_id' => $request->payment_method_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pagamento completato con successo!',
                'data' => [
                    'transaction_id' => $paymentIntent->id,
                    'amount' => $request->amount,
                    'currency' => strtoupper(config('stripe.currency', 'eur')),
                    'payment_method_id' => $request->payment_method_id,
                    'entry' => $entry->load('contest')
                ]
            ]);
        } catch (\Stripe\Exception\CardException $e) {
            DB::rollBack();

            Log::warning('Stripe Card Error', [
                'error' => $e->getMessage(),
                'entry_id' => $request->entry_id ?? null,
                'user_id' => Auth::id(),
                'payment_method_id' => $request->payment_method_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Carta rifiutata: ' . $e->getError()->message
            ], 400);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            DB::rollBack();

            Log::error('Stripe Invalid Request', [
                'error' => $e->getMessage(),
                'entry_id' => $request->entry_id ?? null,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Richiesta non valida. Controlla i dati inseriti.'
            ], 400);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Stripe payment error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'entry_id' => $request->entry_id ?? null,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nel processamento del pagamento. Riprova più tardi.'
            ], 500);
        }
    }



    /**
     * Ottiene lo stato di un pagamento
     */
    public function getPaymentStatus(Request $request, $entryId): JsonResponse
    {
        $entry = Entry::with('contest')->findOrFail($entryId);

        // Verifica che l'entry appartenga all'utente corrente
        if ($entry->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'Non autorizzato'
            ], 403);
        }

        return response()->json([
            'entry_id' => $entry->id,
            'payment_status' => $entry->payment_status,
            'paid_at' => $entry->paid_at,
            'payment_amount' => $entry->payment_amount,
            'transaction_id' => $entry->transaction_id,
            'contest_fee' => $entry->contest->entry_fee
        ]);
    }

    /**
     * Lista delle carte di test disponibili
     */
    public function getTestCards(): JsonResponse
    {
        return response()->json([
            'success_cards' => [
                '4242424242424242' => 'Visa - Sempre successo',
                '4000056655665556' => 'Visa (debit) - Sempre successo',
                '5555555555554444' => 'Mastercard - Sempre successo',
                '2223003122003222' => 'Mastercard (2-series) - Sempre successo',
                '5200828282828210' => 'Mastercard (debit) - Sempre successo',
                '378282246310005' => 'American Express - Sempre successo'
            ],
            'error_cards' => [
                '4000000000000002' => 'Carta declined (generic_decline)',
                '4000000000000069' => 'Carta scaduta (expired_card)',
                '4000000000000127' => 'CVC errato (incorrect_cvc)',
                '4000000000000119' => 'Errore di elaborazione (processing_error)',
                '4242424242424241' => 'Numero carta non valido (incorrect_number)'
            ],
            'european_cards' => [
                '4000002500003155' => 'Visa - Richiede autenticazione 3D Secure',
                '4000002760003184' => 'Visa - 3D Secure 2 obbligatorio'
            ],
            'test_data' => [
                'card_holder' => 'Mario Rossi',
                'expiry_month' => 12,
                'expiry_year' => 2025,
                'cvv' => '123'
            ],
            'stripe_info' => [
                'public_key' => config('stripe.public_key'),
                'currency' => config('stripe.currency'),
                'api_version' => config('stripe.api_version')
            ]
        ]);
    }
}
