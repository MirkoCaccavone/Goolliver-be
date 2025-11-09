<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuovo Concorso - {{ $contest->title }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            padding: 40px 20px;
            text-align: center;
            color: white;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .contest-badge {
            background-color: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 10px;
            font-size: 14px;
        }
        .content {
            padding: 40px 30px;
        }
        .contest-title {
            font-size: 24px;
            color: #333;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
        }
        .contest-details {
            background-color: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin: 30px 0;
        }
        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 15px 0;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .detail-item:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: bold;
            color: #495057;
        }
        .detail-value {
            color: #333;
            font-weight: 500;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            padding: 18px 40px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            font-size: 18px;
            margin: 20px 0;
            transition: transform 0.2s;
            text-align: center;
            display: block;
            max-width: 200px;
            margin: 30px auto;
        }
        .cta-button:hover {
            transform: translateY(-2px);
        }
        .urgency-note {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .footer {
            background-color: #333;
            color: #ccc;
            padding: 20px;
            text-align: center;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏆 Nuovo Concorso!</h1>
            <div class="contest-badge">Partecipa ora e vinci</div>
        </div>
        
        <div class="content">
            <p style="font-size: 18px; color: #333; margin-bottom: 20px;">
                Ciao <strong>{{ $user->name }}</strong>!
            </p>
            
            <p style="color: #666; line-height: 1.6;">
                È appena iniziato un nuovo entusiasmante concorso che potrebbe interessarti!
            </p>
            
            <div class="contest-title">
                📸 {{ $contest->title }}
            </div>
            
            <div class="contest-details">
                <div class="detail-item">
                    <span class="detail-label">🏆 Premio:</span>
                    <span class="detail-value">{{ $contest->prize ?? 'Da definire' }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">👥 Max partecipanti:</span>
                    <span class="detail-value">{{ $contest->max_participants }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">📅 Creato il:</span>
                    <span class="detail-value">{{ $contest->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">📊 Stato:</span>
                    <span class="detail-value" style="color: #28a745; font-weight: bold;">
                        {{ ucfirst($contest->status) }}
                    </span>
                </div>
            </div>
            
            @if($contest->description)
            <div style="background-color: #e9f7ef; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h3 style="margin: 0 0 10px 0; color: #27ae60;">📝 Descrizione:</h3>
                <p style="margin: 0; color: #2c3e50; line-height: 1.6;">{{ $contest->description }}</p>
            </div>
            @endif
            
            <div class="urgency-note">
                <strong>⚡ Non perdere tempo!</strong> I posti sono limitati e potrebbero esaurirsi velocemente.
            </div>
            
            <a href="{{ config('app.url') }}/contests/{{ $contest->id }}" class="cta-button">
                🚀 PARTECIPA ORA
            </a>
            
            <p style="text-align: center; color: #666; margin-top: 30px;">
                Buona fortuna e che vinca il migliore! 🎯<br>
                <em>Team Goolliver</em>
            </p>
        </div>
        
        <div class="footer">
            <p>&copy; 2025 Goolliver. Tutti i diritti riservati.</p>
            <p>Ricevi questa email perché sei iscritto alle notifiche dei concorsi.</p>
        </div>
    </div>
</body>
</html>