<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Contracts\Provider;

class SocialAuthController extends Controller
{
    // Redirect verso Google o Facebook
    public function redirectToProvider($provider)
    {
        try {
            // Verifica che il provider sia supportato
            if (!in_array($provider, ['facebook', 'google'])) {
                return response()->json(['error' => 'Provider non supportato'], 400);
            }

            // Debug: verifica configurazione
            $config = config("services.{$provider}");
            if (empty($config['client_id']) || $config['client_id'] === 'your_facebook_client_id') {
                return response()->json([
                    'error' => "Configurazione {$provider} mancante o non valida",
                    'message' => 'Devi configurare le credenziali nel file .env'
                ], 500);
            }

            // Per API, creiamo manualmente l'URL dei provider
            if ($provider === 'facebook') {
                $config = config('services.facebook');
                $redirectUrl = 'https://www.facebook.com/v18.0/dialog/oauth?' . http_build_query([
                    'client_id' => $config['client_id'],
                    'redirect_uri' => $config['redirect'],
                    'scope' => 'public_profile,email',
                    'response_type' => 'code',
                    'state' => csrf_token()
                ]);
            } elseif ($provider === 'google') {
                $config = config('services.google');
                $redirectUrl = 'https://accounts.google.com/o/oauth2/auth?' . http_build_query([
                    'client_id' => $config['client_id'],
                    'redirect_uri' => $config['redirect'],
                    'scope' => 'openid email profile',
                    'response_type' => 'code',
                    'state' => csrf_token()
                ]);
            } else {
                $redirectUrl = Socialite::driver($provider)->redirect()->getTargetUrl();
            }

            // Invece di restituire JSON, fai redirect diretto
            return redirect($redirectUrl);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Errore durante il redirect',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Callback del provider (Google/Facebook)
    public function handleProviderCallback($provider)
    {
        try {
            // Per Facebook, usiamo il metodo senza sessioni
            if ($provider === 'facebook') {
                $code = request('code');
                if (!$code) {
                    throw new \Exception('Codice di autorizzazione mancante');
                }

                // Ottieni il token manualmente
                $config = config('services.facebook');
                $response = Http::withoutVerifying()->get('https://graph.facebook.com/v18.0/oauth/access_token', [
                    'client_id' => $config['client_id'],
                    'client_secret' => $config['client_secret'],
                    'redirect_uri' => $config['redirect'],
                    'code' => $code
                ]);

                $responseData = $response->json();
                if (!isset($responseData['access_token'])) {
                    throw new \Exception('Impossibile ottenere access token da Facebook: ' . $response->body());
                }

                $token = $responseData['access_token'];

                // Ottieni i dati utente 
                $userResponse = Http::withoutVerifying()->get('https://graph.facebook.com/me', [
                    'access_token' => $token,
                    'fields' => 'id,name,email'
                ]);

                $fbUser = $userResponse->json();
                if (!isset($fbUser['id'])) {
                    throw new \Exception('Impossibile ottenere dati utente da Facebook: ' . $userResponse->body());
                }

                // OBBLIGATORIA: Verifica che Facebook fornisca l'email
                if (!isset($fbUser['email']) || empty($fbUser['email'])) {
                    throw new \Exception('Email non fornita da Facebook. Per utilizzare Goolliver è necessario autorizzare l\'accesso all\'email. Riprova il login e autorizza l\'email.');
                }

                $socialUser = (object) [
                    'id' => $fbUser['id'],
                    'name' => $fbUser['name'] ?? 'User Facebook',
                    'email' => $fbUser['email']  // NESSUN FALLBACK - email obbligatoria
                ];
            } elseif ($provider === 'google') {
                // Gestione manuale per Google
                $code = request('code');
                if (!$code) {
                    throw new \Exception('Codice di autorizzazione Google mancante');
                }

                // Ottieni il token da Google
                $config = config('services.google');
                $response = Http::withoutVerifying()->post('https://oauth2.googleapis.com/token', [
                    'client_id' => $config['client_id'],
                    'client_secret' => $config['client_secret'],
                    'redirect_uri' => $config['redirect'],
                    'grant_type' => 'authorization_code',
                    'code' => $code
                ]);

                $responseData = $response->json();
                if (!isset($responseData['access_token'])) {
                    throw new \Exception('Impossibile ottenere access token da Google: ' . $response->body());
                }

                $token = $responseData['access_token'];

                // Ottieni i dati utente da Google
                $userResponse = Http::withoutVerifying()->get('https://www.googleapis.com/oauth2/v2/userinfo', [
                    'access_token' => $token
                ]);

                $googleUser = $userResponse->json();
                if (!isset($googleUser['id'])) {
                    throw new \Exception('Impossibile ottenere dati utente da Google: ' . $userResponse->body());
                }

                // OBBLIGATORIA: Verifica che Google fornisca l'email
                if (!isset($googleUser['email']) || empty($googleUser['email'])) {
                    throw new \Exception('Email non fornita da Google. Per utilizzare Goolliver è necessario autorizzare l\'accesso all\'email. Riprova il login e autorizza l\'email.');
                }

                $socialUser = (object) [
                    'id' => $googleUser['id'],
                    'name' => $googleUser['name'] ?? 'User Google',
                    'email' => $googleUser['email']  // NESSUN FALLBACK - email obbligatoria
                ];
            } else {
                $socialUser = Socialite::driver($provider)->user();
            }
        } catch (\Exception $e) {
            // Redirect al frontend con errore
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
            $redirectUrl = $frontendUrl . '/auth/callback?' . http_build_query([
                'error' => 'true',
                'message' => $e->getMessage()
            ]);

            return redirect($redirectUrl);
        }

        // Gestione unificata per tutti i provider
        $providerId = in_array($provider, ['facebook', 'google']) ? $socialUser->id : $socialUser->getId();
        $userName = in_array($provider, ['facebook', 'google']) ? $socialUser->name : $socialUser->getName();
        $userEmail = in_array($provider, ['facebook', 'google']) ? $socialUser->email : $socialUser->getEmail();

        $user = User::updateOrCreate(
            [
                'provider' => $provider,
                'provider_id' => $providerId,
            ],
            [
                'name' => $userName,
                'email' => $userEmail,
                'password' => null, // Password nulla per utenti social
                'provider' => $provider,
                'provider_id' => $providerId,
            ]
        );

        // Genera token di accesso per API
        $token = $user->createToken('api_token')->plainTextToken;

        // Redirect al frontend con i dati dell'utente
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $redirectUrl = $frontendUrl . '/auth/callback?' . http_build_query([
            'token' => $token,
            'user' => base64_encode(json_encode($user)),
            'provider' => $provider,
            'success' => 'true'
        ]);

        return redirect($redirectUrl);
    }
}
