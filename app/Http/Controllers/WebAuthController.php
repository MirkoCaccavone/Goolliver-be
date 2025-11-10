<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\LoginRequest;

class WebAuthController extends Controller
{
    /**
     * Mostra il form di login
     */
    public function showLoginForm(Request $request)
    {
        $users = User::all(); // Per facilitare i test

        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <title>🔐 Login Goolliver</title>
            <meta charset='utf-8'>
            <style>
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
                    margin: 0; 
                    padding: 0; 
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .container { 
                    max-width: 400px; 
                    background: white; 
                    padding: 40px; 
                    border-radius: 15px; 
                    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                }
                .header h1 {
                    color: #333;
                    margin: 0;
                    font-size: 2em;
                }
                .form-group {
                    margin-bottom: 20px;
                }
                label {
                    display: block;
                    margin-bottom: 5px;
                    font-weight: bold;
                    color: #333;
                }
                input, select {
                    width: 100%;
                    padding: 12px;
                    border: 2px solid #ddd;
                    border-radius: 8px;
                    font-size: 16px;
                    transition: border-color 0.3s;
                }
                input:focus, select:focus {
                    outline: none;
                    border-color: #667eea;
                }
                .btn {
                    width: 100%;
                    padding: 12px;
                    background: linear-gradient(45deg, #667eea, #764ba2);
                    color: white;
                    border: none;
                    border-radius: 8px;
                    font-size: 16px;
                    font-weight: bold;
                    cursor: pointer;
                    transition: transform 0.2s;
                }
                .btn:hover {
                    transform: translateY(-2px);
                }
                .quick-login {
                    margin-top: 20px;
                    padding: 20px;
                    background: #f8f9fa;
                    border-radius: 10px;
                }
                .quick-login h3 {
                    margin: 0 0 15px 0;
                    color: #333;
                }
                .quick-btn {
                    display: inline-block;
                    padding: 8px 15px;
                    margin: 5px;
                    background: #17a2b8;
                    color: white;
                    text-decoration: none;
                    border-radius: 5px;
                    font-size: 14px;
                }
                .quick-btn:hover {
                    background: #138496;
                }
                .alert {
                    padding: 12px;
                    margin-bottom: 20px;
                    border-radius: 8px;
                    background: #d1ecf1;
                    border: 1px solid #bee5eb;
                    color: #0c5460;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔐 Login Goolliver</h1>
                    <p>Accedi per vedere le tue notifiche</p>
                </div>

                <div class='alert'>
                    ℹ️ Dopo il login verrai automaticamente reindirizzato alle tue notifiche!
                </div>

                <form method='POST' action='/api/web-login'>
                    " . csrf_field() . "
                    <div class='form-group'>
                        <label for='email'>📧 Email:</label>
                        <input type='email' name='email' id='email' required value='mario@example.com' placeholder='Inserisci la tua email'>
                    </div>

                    <div class='form-group'>
                        <label for='password'>🔑 Password:</label>
                        <input type='password' name='password' id='password' required value='123456' placeholder='Inserisci la password'>
                    </div>

                    <button type='button' class='btn' onclick='loginWithCredentials()'>🚀 Accedi alle Notifiche</button>
                </form>

                <script>
                async function loginWithCredentials() {
                    const email = document.getElementById('email').value;
                    const password = document.getElementById('password').value;
                    
                    try {
                        // Login tramite API
                        const response = await fetch('/api/login', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({email, password})
                        });

                        const data = await response.json();
                        
                        if (data.token) {
                            // Salva il token in un cookie
                            document.cookie = `auth_token=\${data.token}; path=/; max-age=86400`;
                            
                            // Reindirizza alle notifiche
                            window.location.href = '/api/notifications/view';
                        } else {
                            alert('❌ Errore login: ' + (data.message || 'Credenziali non valide'));
                        }
                    } catch (error) {
                        alert('❌ Errore di connessione: ' + error.message);
                    }
                }
                </script>

                <div class='quick-login'>
                    <h3>⚡ Login Rapido (per test):</h3>
                    <p>Utenti disponibili:</p>";

        foreach ($users as $user) {
            $html .= "<a href='/api/quick-login/{$user->id}' class='quick-btn'>👤 {$user->name}</a>";
        }

        $html .= "
                </div>

                <div style='text-align: center; margin-top: 20px;'>
                    <small style='color: #666;'>
                        Non hai un account? <a href='/api/register' style='color: #667eea;'>Registrati qui</a>
                    </small>
                </div>
            </div>
        </body>
        </html>";

        return response($html)->header('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Login rapido per test (genera token e reindirizza)
     */
    public function quickLogin(Request $request, $userId)
    {
        try {
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'error' => 'Utente non trovato',
                    'user_id' => $userId
                ], 404);
            }

            // Genera token
            $token = $user->createToken('web-access')->plainTextToken;

            // Debug: verifichiamo che il token sia valido
            Log::info("Token generato per utente {$user->id}: " . substr($token, 0, 20) . "...");

            // Redirect con cookie alle notifiche
            return redirect('/api/notifications/view')
                ->withCookie('auth_token', $token, 60 * 24, '/', null, false, false);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Errore durante il login',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
    /**
     * Login tradizionale con email e password
     */
    public function webLogin(LoginRequest $request)
    {
        $sanitizedData = $request->getSanitizedData();

        $user = User::where('email', $sanitizedData['email'])->first();

        if (!$user || !Hash::check($sanitizedData['password'], $user->password)) {
            // Log tentativo di login fallito per monitoring
            Log::warning('Login fallito', [
                'email' => $sanitizedData['email'],
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return redirect('/api/login-form')->withErrors(['error' => 'Credenziali non valide']);
        }

        // Genera token
        $token = $user->createToken('web-access')->plainTextToken;

        // Reindirizza alle notifiche con il token
        return redirect('/api/notifications/view')
            ->withCookie('auth_token', $token, 60 * 24);
    }
}
