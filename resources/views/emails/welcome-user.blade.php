<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to JuanCharge</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #ffffff;
            background: linear-gradient(135deg, #0a0f0d 0%, #142221 50%, #1a2c28 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background: rgba(20, 34, 33, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(76, 175, 80, 0.3);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4), 0 0 60px rgba(76, 175, 80, 0.1);
        }
        .header {
            background: linear-gradient(120deg, #061e08 0%, #0d3a15 25%, #1e6b2e 50%, #2d8f3f 75%, #4caf50 100%);
            color: #ffffff;
            padding: 30px 40px;
            text-align: left;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(6, 30, 8, 0.95) 0%, rgba(13, 58, 21, 0.9) 25%, rgba(30, 107, 46, 0.8) 50%, rgba(45, 143, 63, 0.85) 75%, rgba(76, 175, 80, 0.9) 100%);
            z-index: 0;
        }
        .header::after {
            content: '';
            position: absolute;
            top: -100%;
            right: -50%;
            width: 300%;
            height: 300%;
            background: radial-gradient(ellipse at top right, rgba(255,255,255,0.2) 0%, transparent 50%);
            animation: shimmer 4s ease-in-out infinite;
            z-index: 1;
        }
        @keyframes shimmer {
            0%, 100% { opacity: 0.3; transform: translate(0, 0); }
            50% { opacity: 0.6; transform: translate(-10%, 10%); }
        }
        .header-content {
            position: relative;
            z-index: 2;
            flex: 1;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            position: relative;
            z-index: 2;
            text-shadow: 0 2px 12px rgba(0, 0, 0, 0.5);
        }
        .header p {
            margin: 8px 0 0;
            font-size: 15px;
            opacity: 0.95;
            position: relative;
            z-index: 2;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.3);
        }
        .content {
            padding: 40px 30px;
            background: rgba(10, 15, 13, 0.3);
        }
        .greeting {
            font-size: 18px;
            color: #ffffff;
            margin-bottom: 20px;
        }
        .welcome-text {
            font-size: 22px;
            color: #ffffff;
            font-weight: 500;
            line-height: 1.8;
            margin: 20px 0 30px;
        }
        .credentials-box {
            background: rgba(0, 0, 0, 0.4);
            border-left: 4px solid #4caf50;
            padding: 25px;
            margin: 30px 0;
            border-radius: 12px;
            border: 1px solid rgba(76, 175, 80, 0.3);
        }
        .credentials-box h2 {
            margin: 0 0 20px;
            color: #66bb6a;
            font-size: 20px;
            font-weight: 600;
        }
        .credential-item {
            margin: 15px 0;
            display: flex;
            align-items: center;
        }
        .credential-label {
            font-weight: 600;
            color: rgba(255, 255, 255, 0.7);
            min-width: 100px;
            display: inline-block;
        }
        .credential-value {
            color: #ffffff;
            font-family: 'Courier New', monospace;
            background: rgba(0, 0, 0, 0.5);
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid rgba(76, 175, 80, 0.3);
            flex: 1;
        }
        .password-value {
            font-size: 18px;
            font-weight: 600;
            letter-spacing: 2px;
            color: #66bb6a;
        }
        .security-notice {
            background: rgba(255, 193, 7, 0.15);
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin: 30px 0;
            border-radius: 12px;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }
        .security-notice h3 {
            margin: 0 0 15px;
            color: #ffc107;
            font-size: 16px;
            display: flex;
            align-items: center;
        }
        .security-notice h3::before {
            content: "⚠️";
            margin-right: 8px;
            font-size: 20px;
        }
        .security-notice ul {
            margin: 0;
            padding-left: 20px;
            color: rgba(255, 255, 255, 0.9);
        }
        .security-notice li {
            margin: 8px 0;
        }
        .login-button {
            display: inline-block;
            background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
            color: #000000;
            padding: 15px 40px;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 700;
            margin: 20px 0;
            text-align: center;
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.4);
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.6);
        }
        .footer {
            background: rgba(0, 0, 0, 0.3);
            padding: 30px;
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            border-top: 1px solid rgba(76, 175, 80, 0.2);
        }
        .footer p {
            margin: 5px 0;
        }
        .footer strong {
            color: #66bb6a;
        }
        .divider {
            height: 1px;
            background: linear-gradient(to right, transparent, rgba(76, 175, 80, 0.3), transparent);
            margin: 30px 0;
        }
        @media only screen and (max-width: 600px) {
            .email-container {
                border-radius: 16px;
            }
            .header {
                flex-direction: column;
                text-align: center;
                padding: 25px 20px;
            }
            .content {
                padding: 30px 20px;
            }
            .credentials-box {
                padding: 20px;
            }
            .credential-item {
                flex-direction: column;
                align-items: flex-start;
            }
            .credential-label {
                margin-bottom: 5px;
            }
            .credential-value {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <h1>⚡ Welcome to JuanCharge</h1>
                <p>Your LGU Portal Account is Ready</p>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                Hello <strong>{{ $user->name }}</strong>,
            </div>

            <p class="welcome-text">
                Welcome to the JuanCharge LGU Portal! We're excited to have you on board. 
                Your account has been successfully created and is ready to use.
            </p>

            <!-- Credentials Box -->
            <div class="credentials-box">
                <h2>🔑 Your Login Credentials</h2>
                
                <div class="credential-item">
                    <span class="credential-label">Email:</span>
                    <span class="credential-value">{{ $user->email }}</span>
                </div>

                <div class="credential-item">
                    <span class="credential-label">Password:</span>
                    <span class="credential-value password-value">{{ $password }}</span>
                </div>

                <div class="credential-item">
                    <span class="credential-label">Role:</span>
                    <span class="credential-value">{{ $user->role }}</span>
                </div>
            </div>

            <!-- Security Notice -->
            <div class="security-notice">
                <h3>Important Security Notice</h3>
                <ul>
                    <li><strong>This is a temporary password</strong> - You will be required to change it on your first login</li>
                    <li>Keep this information <strong>confidential</strong></li>
                    <li><strong>Never share</strong> your password with anyone</li>
                    <li>If you didn't request this account, please contact support immediately</li>
                </ul>
            </div>

            <div class="divider"></div>

            <!-- Login Button -->
            <div style="text-align: center;">
                <a href="{{ config('app.frontend_url', 'http://localhost:3000') }}/login" class="login-button">
                    Access Portal Now →
                </a>
            </div>

            <p style="margin-top: 30px; color: rgba(255, 255, 255, 0.7); font-size: 14px;">
                If the button above doesn't work, copy and paste this URL into your browser:<br>
                <strong style="color: #66bb6a;">{{ config('app.frontend_url', 'http://localhost:3000') }}/login</strong>
            </p>

            <div class="divider"></div>

            <p style="color: rgba(255, 255, 255, 0.7); font-size: 14px;">
                Need help getting started? Contact our support team and we'll be happy to assist you.
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>JuanCharge</strong> - Powering Your Journey ⚡</p>
            <p>This is an automated message. Please do not reply to this email.</p>
            <p style="margin-top: 15px; font-size: 12px; color: #adb5bd;">
                © {{ date('Y') }} JuanCharge. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
