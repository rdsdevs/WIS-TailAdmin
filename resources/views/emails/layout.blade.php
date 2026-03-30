<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>WIS ASCUN</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f4f5;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 16px;
            line-height: 1.6;
            color: #374151;
        }
        .wrapper {
            width: 100%;
            background-color: #f4f4f5;
            padding: 32px 16px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #2563eb;
            padding: 28px 40px;
            text-align: center;
        }
        .header-logo {
            color: #ffffff;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 0.05em;
            margin: 0;
            text-decoration: none;
        }
        .header-tagline {
            color: #bfdbfe;
            font-size: 13px;
            margin: 4px 0 0;
        }
        .body {
            padding: 36px 40px;
        }
        .footer {
            background-color: #f9fafb;
            border-top: 1px solid #e5e7eb;
            padding: 20px 40px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
        }
        .footer a {
            color: #6b7280;
            text-decoration: none;
        }
        @media only screen and (max-width: 620px) {
            .body {
                padding: 24px 20px;
            }
            .header {
                padding: 20px 20px;
            }
            .footer {
                padding: 16px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">

            <div class="header">
                <p class="header-logo">WIS ASCUN</p>
                <p class="header-tagline">Sistema de Gestión Integral</p>
            </div>

            <div class="body">
                {{ $slot }}
            </div>

            <div class="footer">
                <p>&copy; {{ date('Y') }} ASCUN &middot; WIS System</p>
                <p>Este mensaje fue generado automáticamente. Por favor no responda este correo.</p>
            </div>

        </div>
    </div>
</body>
</html>
