<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Two-Factor Authentication Code</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background-color: #f9f9f9;
            border-radius: 5px;
            padding: 20px;
            border: 1px solid #ddd;
        }
        .code {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            letter-spacing: 5px;
            margin: 20px 0;
            padding: 10px;
            background-color: #eee;
            border-radius: 5px;
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
    <div class="container">
        <h2>Two-Factor Authentication</h2>
        <p>Hello {{ $user->name }},</p>
        <p>Your two-factor authentication code is:</p>
        <div class="code">{{ $code }}</div>
        <p>This code will expire in 10 minutes.</p>
        <p>If you did not request this code, please ignore this email and consider changing your password.</p>
    </div>
    <div class="footer">
        <p>This is an automated message, please do not reply.</p>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>
