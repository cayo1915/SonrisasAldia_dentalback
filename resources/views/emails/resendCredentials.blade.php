<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecimiento de Contraseña</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #333;
        }

        p {
            color: #555;
        }

        .verification-code {
            display: inline-block;
            font-size: 24px;
            font-weight: bold;
            background-color: #e0f0ff;
            color: #007BFF;
            padding: 12px 20px;
            border-radius: 6px;
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Restablecimiento de Contraseña</h1>
        <p>Hemos recibido una solicitud para restablecer su contraseña. Use el siguiente código para continuar con el proceso:</p>
        <div class="verification-code">
            {{ $verificationCode }}
        </div>
        <p>Si no solicitó este cambio, puede ignorar este correo.</p>
        <p>Gracias,</p>
        <p>El equipo de soporte.</p>
    </div>
</body>

</html>