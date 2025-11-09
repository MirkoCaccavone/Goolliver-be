<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reminder - {{ $contest->title }}</title>
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
            background: linear-gradient(135deg, #ffa726 0%, #fb8c00 100%);
            padding: 40px 20px;
            text-align: center;
            color: white;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .reminder-badge {
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
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
            color: white;
            border-radius: 8px;
        }
        .time-left {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 25px;
            border-radius: 8px;
            text-align: center;
            margin: 30px 0;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .time-left h3 {
            margin: 0 0 10px 0;
            font-size: 20px;
        }
        .time-left .countdown {
            font-size: 32px;
            font-weight: bold;
            margin: 15px 0;
        }
        .contest-stats {
            background-color: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin: 30px 0;
        }
        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 15px 0;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .stat-item:last-child {
            border-bottom: none;
        }
        .stat-label {
            font-weight: bold;
            color: #495057;
        }
        .stat-value {
            color: #333;
            font-weight: 500;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
            max-width: 250px;
            margin: 30px auto;
        }
        .cta-button:hover {
            transform: translateY(-2px);
        }
        .urgency-alert {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.8; }
            100% { opacity: 1; }
        }
        .progress-bar {
            background-color: #e9ecef;
            border-radius: 10px;
            height: 20px;
            overflow: hidden;
            margin: 20px 0;
        }
        .progress-fill {
            background: linear-gradient(90deg, #28a745, #20c997);
            height: 100%;
            border-radius: 10px;
            transition: width 0.5s ease;
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
            <h1>⏰ Reminder Concorso!</h1>
            <div class="reminder-badge">Tempo limitato</div>
        </div>
        
        <div class="content">
            <p style="font-size: 18px; color: #333; margin-bottom: 20px;">
                Ciao <strong>{{ $user->name }}</strong>!
            </p>
            
            <p style="color: #666; line-height: 1.6;">
                Non dimenticare di partecipare al concorso che ti interessa!
            </p>
            
            <div class="contest-title">
                📸 {{ $contest->title }}
            </div>
            
            @php
                $currentParticipants = $contest->participants ?? 0;
                $maxParticipants = $contest->max_participants;
                $fillPercentage = $maxParticipants > 0 ? min(100, ($currentParticipants / $maxParticipants) * 100) : 0;
                $spotsLeft = max(0, $maxParticipants - $currentParticipants);
            @endphp
            
            <div class="time-left">
                <h3>⚡ Attenzione!</h3>
                <div>Posti disponibili in esaurimento</div>
                <div class="countdown">{{ $spotsLeft }} rimasti!</div>
                <div style="font-size: 14px;">su {{ $maxParticipants }} totali</div>
            </div>
            
            <div style="margin: 30px 0;">
                <p style="text-align: center; margin-bottom: 10px; font-weight: bold;">
                    Stato partecipazioni: {{ number_format($fillPercentage, 1) }}%
                </p>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {{ $fillPercentage }}%"></div>
                </div>
            </div>
            
            <div class="contest-stats">
                <div class="stat-item">
                    <span class="stat-label">👥 Partecipanti attuali:</span>
                    <span class="stat-value">{{ $currentParticipants }}/{{ $maxParticipants }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">🏆 Premio:</span>
                    <span class="stat-value">{{ $contest->prize ?? 'Da definire' }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">📊 Stato:</span>
                    <span class="stat-value" style="color: #28a745; font-weight: bold;">
                        {{ ucfirst($contest->status) }}
                    </span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">📅 Creato:</span>
                    <span class="stat-value">{{ $contest->created_at->format('d/m/Y') }}</span>
                </div>
            </div>
            
            @if($fillPercentage >= 80)
            <div class="urgency-alert">
                <strong>🚨 ULTIMI POSTI DISPONIBILI!</strong><br>
                Il concorso è quasi al completo. Affrettati a partecipare prima che sia troppo tardi!
            </div>
            @elseif($fillPercentage >= 60)
            <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px;">
                <strong>⚠️ Posti limitati!</strong> Il concorso si sta riempiendo velocemente.
            </div>
            @else
            <div style="background-color: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; border-radius: 4px;">
                <strong>✅ Ancora molti posti disponibili!</strong> È il momento perfetto per partecipare.
            </div>
            @endif
            
            <a href="{{ config('app.url') }}/contests/{{ $contest->id }}" class="cta-button">
                🎯 PARTECIPA SUBITO
            </a>
            
            <p style="text-align: center; color: #666; margin-top: 30px;">
                Non perdere questa opportunità! 🌟<br>
                <em>Team Goolliver</em>
            </p>
        </div>
        
        <div class="footer">
            <p>&copy; 2025 Goolliver. Tutti i diritti riservati.</p>
            <p>Hai ricevuto questo reminder perché hai mostrato interesse per questo concorso.</p>
        </div>
    </div>
</body>
</html>