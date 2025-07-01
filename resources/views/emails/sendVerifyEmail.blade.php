{{-- resources/views/emails/verify.blade.php --}}
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Correo Electrónico</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .email-container {
            max-width: 600px;
            margin: auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .email-header {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            color: #4CAF50;
        }

        .email-body {
            font-size: 16px;
            line-height: 1.5;
            margin: 20px 0;
        }

        .button {
            display: inline-block;
            padding: 10px 20px;
            color: #ffffff;
            background-color: #4CAF50;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }

        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #777;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <div class="email-header">
            ¡Verifica tu correo electrónico!
        </div>
        <div class="email-body">
            <p>Hola,</p>
            <p>Gracias por registrarte en MiAplicación. Para completar el proceso de registro, haz clic en el botón de abajo para verificar tu dirección de correo electrónico:</p>
            <p style="text-align: center;">
                <a href="{{ $verificationUrl }}" class="button">Verificar Correo Electrónico</a>
            </p>
            <p>Si no solicitaste esta cuenta, puedes ignorar este mensaje.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} MiAplicación. Todos los derechos reservados.
        </div>
    </div>
</body>

</html>