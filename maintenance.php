<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple2Success — Wartung</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #0a0a12;
            color: #fff;
            font-family: Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            text-align: center;
            max-width: 560px;
            padding: 40px 30px;
        }
        .icon {
            font-size: 72px;
            margin-bottom: 24px;
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50%       { transform: scale(1.08); opacity: 0.85; }
        }
        h1 {
            font-size: 2.2rem;
            margin-bottom: 12px;
            background: linear-gradient(135deg, #cb2ebc, #8b00ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        p {
            color: #aaa;
            font-size: 1.05rem;
            line-height: 1.7;
            margin-bottom: 16px;
        }
        .badge {
            display: inline-block;
            background: linear-gradient(135deg, #cb2ebc, #8b00ff);
            color: #fff;
            padding: 8px 24px;
            border-radius: 50px;
            font-size: 0.85rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-top: 16px;
        }
        .dots {
            display: inline-flex;
            gap: 8px;
            margin-top: 32px;
        }
        .dot {
            width: 10px;
            height: 10px;
            background: #cb2ebc;
            border-radius: 50%;
            animation: bounce 1.4s ease-in-out infinite;
        }
        .dot:nth-child(2) { animation-delay: 0.2s; }
        .dot:nth-child(3) { animation-delay: 0.4s; }
        @keyframes bounce {
            0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
            40%            { transform: scale(1);   opacity: 1;   }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🦅</div>
        <h1>Wir sind gleich zurück!</h1>
        <p>
            Simple2Success befindet sich derzeit in der Wartung.<br>
            Wir arbeiten daran, dir noch mehr Möglichkeiten zu bieten.
        </p>
        <p>
            Bitte versuche es in Kürze wieder.
        </p>
        <div class="badge">Maintenance</div>
        <div class="dots">
            <div class="dot"></div>
            <div class="dot"></div>
            <div class="dot"></div>
        </div>
    </div>
</body>
</html>
