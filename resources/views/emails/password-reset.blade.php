<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset - HealthReach</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #2E7D32;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .button {
            display: inline-block;
            background-color: #4A90E2;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #666;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏥 HealthReach</h1>
        <h2>Password Reset Request</h2>
    </div>
    
    <div class="content">
        <p>Hello <strong>{{ $user['name'] }}</strong>,</p>
        
        <p>We received a request to reset your password for your HealthReach account. If you made this request, please click the button below to reset your password:</p>
        
        <div style="text-align: center;">
            <a href="{{ $resetUrl }}" class="button">Reset My Password</a>
        </div>
        
        <p>Or copy and paste this link into your browser:</p>
        <p style="word-break: break-all; background-color: #f0f0f0; padding: 10px; border-radius: 4px;">
            {{ $resetUrl }}
        </p>
        
        <div class="warning">
            <strong>⚠️ Important Security Information:</strong>
            <ul>
                <li>This link will expire in <strong>{{ $expirationMinutes }} minutes</strong></li>
                <li>If you didn't request this password reset, please ignore this email</li>
                <li>Never share this link with anyone</li>
                <li>HealthReach will never ask for your password via email</li>
            </ul>
        </div>
        
        <p>If you're having trouble with the button above, you can also reset your password by:</p>
        <ol>
            <li>Opening the HealthReach mobile app</li>
            <li>Going to the login screen</li>
            <li>Tapping "Forgot Password?"</li>
            <li>Entering your email address: <strong>{{ $user['email'] }}</strong></li>
        </ol>
        
        <p>If you continue to have problems, please contact our support team.</p>
        
        <p>Best regards,<br>
        The HealthReach Team</p>
    </div>
    
    <div class="footer">
        <p>© {{ date('Y') }} HealthReach. All rights reserved.</p>
        <p>This is an automated message, please do not reply to this email.</p>
        <p>If you need help, contact us at support@healthreach.com</p>
    </div>
</body>
</html>
