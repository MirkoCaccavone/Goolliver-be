<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benvenuto in Goolliver</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            text-align: center;
            color: white;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .content {
            padding: 40px 30px;
        }
        .welcome-message {
            font-size: 18px;
            color: #333;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            font-size: 16px;
            margin: 20px 0;
            transition: transform 0.2s;
        }
        .cta-button:hover {
            transform: translateY(-2px);
        }
        .features {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 30px 0;
        }
        .feature-item {
            display: flex;
            align-items: center;
            margin: 15px 0;
        }
        .feature-icon {
            font-size: 24px;
            margin-right: 15px;
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
            <h1>🎉 Benvenuto in Goolliver!</h1>
        </div>
        
        <div class="content">
            <div class="welcome-message">
                <p>Ciao <strong>{{ $user->name }}</strong>!</p>
                
                <p>È fantastico averti nella community Goolliver! Sei pronto a partecipare ai concorsi più creativi e divertenti?</p>
                
                <p>Con Goolliver puoi:</p>
            </div>
            
            <div class="features">
                <div class="feature-item">
                    <span class="feature-icon">🏆</span>
                    <span>Partecipare a concorsi emozionanti</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">📸</span>
                    <span>Condividere le tue creazioni</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">🎁</span>
                    <span>Vincere premi fantastici</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">👥</span>
                    <span>Connetterti con altri creativi</span>
                </div>
            </div>
            
            <div style="text-align: center;">
                <a href="{{ config('app.url') }}" class="cta-button">
                    🚀 Esplora i Concorsi
                </a>
            </div>
            
            <p style="margin-top: 30px; color: #666;">
                Ti manterremo aggiornato sui nuovi concorsi e sulle tue attività. 
                Buona fortuna e divertiti! 🎯
            </p>
        </div>
        
        <div class="footer">
            <p>&copy; 2025 Goolliver. Tutti i diritti riservati.</p>
            <p>Hai ricevuto questa email perché ti sei registrato su Goolliver.</p>
        </div>
    </div>
</body>
</html>