<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your New Password</title>
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
            border-radius: 10px;
            padding: 30px;
            border: 1px solid #ddd;
        }
        .header {
            background-color: #2196F3;
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            text-align: center;
            margin: -30px -30px 30px -30px;
        }
        .password-box {
            background-color: #e3f2fd;
            border: 2px solid #2196F3;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            margin: 20px 0;
        }
        .password {
            font-size: 24px;
            font-weight: bold;
            color: #1976D2;
            letter-spacing: 2px;
            font-family: 'Courier New', monospace;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .info {
            background-color: #d1ecf1;
            border: 1px solid #17a2b8;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Password Reset Successful</h1>
        </div>

        <p>Hello {{ $userName ?? 'User' }},</p>

        <p>Your password has been successfully reset. Here is your new password:</p>

        <div class="password-box">
            <p style="margin: 0; font-size: 14px; color: #666;">Your New Password:</p>
            <p class="password">{{ $newPassword }}</p>
        </div>

        <div class="info">
            <strong>📧 Email:</strong> {{ $email }}
        </div>

        <div class="warning">
            <strong>⚠️ Important Security Notice:</strong>
            <ul>
                <li><strong>Change this password immediately</strong> after logging in</li>
                <li>Use a strong, unique password that you haven't used before</li>
                <li>Do not share this password with anyone</li>
                <li>Delete this email after changing your password</li>
            </ul>
        </div>

        <p><strong>Next Steps:</strong></p>
        <ol>
            <li>Copy the password above</li>
            <li>Go to the JuanCharge login page</li>
            <li>Login with your email and the new password</li>
            <li>Immediately change your password in your profile settings</li>
        </ol>

        <div class="footer">
            <p>If you didn't request this password reset, please contact support immediately.</p>
            <p>This is an automated email from JuanCharge. Please do not reply to this email.</p>
            <p>&copy; {{ date('Y') }} JuanCharge. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
