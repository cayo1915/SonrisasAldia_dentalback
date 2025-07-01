<!-- resources/views/emails/reservation_reminder.blade.php -->

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Recordatorio de Reserva</title>
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

        ul.status-list {
            list-style-type: none;
            padding: 0;
        }

        ul.status-list li {
            margin-bottom: 8px;
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
            <h2>Recordatorio de Reserva</h2>
        </div>
        <div class="content">
            <p>Hola <strong>{{ $patientFullName }}</strong>,</p>

            <p>Este es un recordatorio de tu reserva con el doctor(a) <strong>{{ $doctorFullName }}</strong>.</p>

            <ul>
                <li><strong>Fecha:</strong> {{ $date }}</li>
                <li><strong>Hora:</strong> {{ $startHour }} - {{ $endHour }}</li>
            </ul>

            <h3>Estado de la reserva:</h3>
            <ul class="status-list">
                <li><strong>Confirmada:</strong> {{ $isConfirmed ? 'Sí' : 'No' }}</li>
            </ul>

            <p>Por favor, asegúrate de llegar a tiempo y contactarnos si necesitas hacer algún cambio.</p>
        </div>
        <div class="footer">
            Este es un mensaje automático. Por favor, no responder.
        </div>
    </div>
</body>

</html>