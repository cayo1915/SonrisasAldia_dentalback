<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Confirmación de Reserva</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f7f9fa;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: auto;
            background-color: #fff;
            padding: 24px;
            border-radius: 8px;
        }

        .header {
            background-color: #007bff;
            padding: 16px;
            border-radius: 8px 8px 0 0;
            text-align: center;
            color: white;
        }

        .content {
            padding: 16px 0;
        }

        .footer {
            margin-top: 24px;
            font-size: 14px;
            color: #666;
            text-align: center;
        }

        @media only screen and (max-width: 600px) {
            .container {
                padding: 16px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2>Confirmación de Reserva</h2>
        </div>

        <div class="content">
            <p>Hola <strong>{{ $patientFullName }}</strong>,</p>

            <p>Tu reserva con el profesional <strong>{{ $doctorFullName }}</strong> ha sido <strong>confirmada</strong>.</p>

            <p><strong>Detalles de la reserva:</strong></p>
            <ul>
                <li><strong>Fecha:</strong> {{ $date }}</li>
                <li><strong>Hora de inicio:</strong> {{ $startHour }}</li>
                <li><strong>Hora de término:</strong> {{ $endHour }}</li>
            </ul>

            <p>Por favor, asegúrate de llegar a tiempo para tu cita.</p>

            <p>Gracias por confiar en nosotros.<br>— El equipo de atención</p>
        </div>

        <div class="footer">
            Este es un mensaje automático, no respondas este correo.
        </div>
    </div>
</body>

</html>