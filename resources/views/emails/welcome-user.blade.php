<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to JuanCharge</title>
    <style>
        /* Base Reset */
        body, table, td, a {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        table, td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        img {
            -ms-interpolation-mode: bicubic;
        }

        /* Essential styles */
        body {
            margin: 0;
            padding: 0;
            width: 100% !important;
            background-color: #f4f7f6;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
            color: #333333;
        }

        .email-wrapper {
            width: 100%;
            background-color: #f4f7f6;
            padding: 40px 0;
        }

        .email-content {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .email-header {
            background-color: #2E7D32;
            padding: 30px 40px;
            text-align: center;
        }

        .email-header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .email-body {
            padding: 40px;
            line-height: 1.6;
            font-size: 16px;
        }

        .email-body h2 {
            margin-top: 0;
            color: #1a1a1a;
            font-size: 20px;
            font-weight: 600;
        }

        .credentials-box {
            background-color: #f9fbf9;
            border: 1px solid #e2ece3;
            border-radius: 6px;
            padding: 24px;
            margin: 30px 0;
        }

        .credentials-box p {
            margin: 0 0 12px 0;
            font-size: 15px;
            color: #555555;
        }

        .credentials-box p:last-child {
            margin-bottom: 0;
        }

        .credential-label {
            font-weight: 600;
            color: #333333;
            display: inline-block;
            width: 90px;
        }

        .password-value {
            font-family: source-code-pro, Menlo, Monaco, Consolas, "Courier New", monospace;
            background-color: #ffffff;
            border: 1px solid #dcdcdc;
            padding: 4px 8px;
            border-radius: 4px;
            color: #2E7D32;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .notice-box {
            background-color: #fff8e1;
            border-left: 4px solid #ffc107;
            padding: 16px 20px;
            margin: 24px 0;
            border-radius: 4px;
            font-size: 14px;
            color: #665000;
        }

        .notice-box p {
            margin: 0 0 8px 0;
            font-weight: 600;
        }

        .notice-box ul {
            margin: 0;
            padding-left: 20px;
        }

        .notice-box li {
            margin-bottom: 4px;
        }

        .button-container {
            text-align: center;
            margin: 40px 0;
        }

        .action-button {
            display: inline-block;
            background-color: #2E7D32;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.2s;
        }

        .action-button:hover {
            background-color: #1B5E20;
        }

        .email-footer {
            background-color: #f4f7f6;
            padding: 30px 40px;
            text-align: center;
            font-size: 13px;
            color: #888888;
        }

        .email-footer p {
            margin: 0 0 8px 0;
        }

        @media screen and (max-width: 600px) {
            .email-content {
                border-radius: 0;
            }
            .email-body {
                padding: 24px;
            }
            .email-header {
                padding: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-content">
            <!-- Header -->
            <div class="email-header">
                <h1>JuanCharge LGU Portal</h1>
            </div>

            <!-- Body -->
            <div class="email-body">
                <h2>Welcome, {{ $user->name }}</h2>
                <p>An account has been provisioned for you on the JuanCharge LGU Portal. You can now access the system to manage your local government unit's recycling and charging records.</p>
                
                <div class="credentials-box">
                    <p><strong>Your Account Credentials</strong></p>
                    <p><span class="credential-label">Email:</span> {{ $user->email }}</p>
                    <p><span class="credential-label">Password:</span> <span class="password-value">{{ $password }}</span></p>
                </div>

                <div class="notice-box">
                    <p>Security Requirements</p>
                    <ul>
                        <li>This is a temporary system-generated password.</li>
                        <li>You will be required to change your password upon your first login.</li>
                        <li>Do not share these credentials with anyone.</li>
                    </ul>
                </div>

                <div class="button-container">
                    <a href="{{ config('app.frontend_url', 'https://juancharge.vercel.app') }}/login" class="action-button">
                        Sign In to Portal
                    </a>
                </div>

                <p style="color: #666666; font-size: 14px; margin-top: 30px;">
                    If you encounter any issues logging in, please contact the JuanCharge support team immediately.
                </p>
            </div>

            <!-- Footer -->
            <div class="email-footer">
                <p>&copy; {{ date('Y') }} JuanCharge. All rights reserved.</p>
                <p>This is an automated administrative message. Please do not reply directly to this email.</p>
            </div>
        </div>
    </div>
</body>
</html>