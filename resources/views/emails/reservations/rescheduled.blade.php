<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Reserva Reagendada</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .container {
            max-width: 600px;
            margin: auto;
            background-color: #ffffff;
            padding: 24px;
            border-radius: 8px;
        }

        .header {
            background-color: #0A74DA;
            color: white;
            padding: 16px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }

        .content {
            font-size: 16px;
            line-height: 1.6;
            color: #333333;
        }

        .footer {
            margin-top: 24px;
            text-align: center;
            font-size: 14px;
            color: #777777;
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
            <h2>Reserva Reagendada</h2>
        </div>
        <div class="content">
            <p>Hola <strong>{{ $patientFullName }}</strong>,</p>
            <p>Tu reserva con el doctor(a) <strong>{{ $doctorFullName }}</strong> ha sido reagendada para:</p>

            <ul>
                <li><strong>Fecha:</strong> {{ $date }}</li>
                <li><strong>Hora:</strong> {{ $startHour }} - {{ $endHour }}</li>
            </ul>

            <p>Gracias por tu comprensión.</p>
        </div>
        <div class="footer">
            Este es un mensaje automático. Por favor, no responder.
        </div>
    </div>
</body>

</html>