<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foto Rifiutata - Credito Assegnato</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .email-container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #e74c3c;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        .alert-section {
            background: #fee;
            border: 1px solid #fcc;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .credit-section {
            background: #e8f5e8;
            border: 1px solid #a8d8a8;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .photo-info {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
        }
        .reason-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
        }
        .cta-button {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 14px;
            color: #666;
            text-align: center;
        }
        .emoji {
            font-size: 20px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">📸 Goolliver Contest</div>
            <p>Sistema di Gestione Photo Contest</p>
        </div>

        <h2><span class="emoji">❌</span> La Tua Foto È Stata Rifiutata</h2>
        
        <p>Ciao <strong>{{ $user->name }}</strong>,</p>
        
        <p>Ti stiamo scrivendo per informarti che una delle tue foto è stata esaminata dal nostro team di moderazione e purtroppo non è stata approvata.</p>

        <div class="photo-info">
            <h3><span class="emoji">📸</span> Dettagli Foto</h3>
            <ul>
                <li><strong>ID Foto:</strong> #{{ $entry->id }}</li>
                @if($entry->title)
                    <li><strong>Titolo:</strong> {{ $entry->title }}</li>
                @endif
                @if($entry->caption)
                    <li><strong>Descrizione:</strong> {{ $entry->caption }}</li>
                @endif
                <li><strong>Contest:</strong> {{ $entry->contest->title ?? 'Contest' }}</li>
                <li><strong>Data Caricamento:</strong> {{ $entry->created_at->format('d/m/Y H:i') }}</li>
            </ul>
        </div>

        @if($rejectionReason)
        <div class="reason-box">
            <h3><span class="emoji">💬</span> Motivo del Rifiuto</h3>
            <p><em>"{{ $rejectionReason }}"</em></p>
        </div>
        @endif

        <div class="credit-section">
            <h3><span class="emoji">💰</span> Buone Notizie: Credito Assegnato!</h3>
            <p>Poiché avevi già effettuato il pagamento per questa foto, ti abbiamo automaticamente assegnato <strong>{{ $creditsAssigned }} credito{{ $creditsAssigned > 1 ? 's' : '' }}</strong> che potrai utilizzare per caricare una nuova foto senza costi aggiuntivi.</p>
            
            <div style="background: white; padding: 15px; border-radius: 4px; margin: 10px 0;">
                <strong>💳 I tuoi crediti totali: {{ $user->photo_credits }} credito{{ $user->photo_credits != 1 ? 's' : '' }}</strong>
            </div>
        </div>

        <div class="alert-section">
            <h3><span class="emoji">📋</span> Cosa Puoi Fare Ora</h3>
            <ul>
                <li>Carica una nuova foto utilizzando i tuoi crediti</li>
                <li>Assicurati che rispetti le linee guida del contest</li>
                <li>Contatta il supporto se hai domande</li>
            </ul>
        </div>

        <div style="text-align: center;">
            <a href="{{ config('app.url') }}" class="cta-button">
                <span class="emoji">🚀</span> Carica Nuova Foto
            </a>
        </div>

        <div class="footer">
            <p><strong>Grazie per la tua partecipazione!</strong></p>
            <p>Il Team di Goolliver Contest</p>
            <hr style="margin: 15px 0; border: none; border-top: 1px solid #eee;">
            <p style="font-size: 12px; color: #999;">
                Questa è un'email automatica. Per domande o supporto, contattaci tramite il nostro sito web.<br>
                © {{ date('Y') }} Goolliver Contest. Tutti i diritti riservati.
            </p>
        </div>
    </div>
</body>
</html>