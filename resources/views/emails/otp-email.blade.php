<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Verification Code</title>
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
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
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
            text-align: center;
        }

        .email-header {
            padding: 40px 40px 20px 40px;
        }

        .email-header h1 {
            color: #1a1a1a;
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }
        
        .brand-text {
            color: #2E7D32;
            font-weight: 800;
            font-size: 18px;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 24px;
            display: block;
        }

        .email-body {
            padding: 10px 40px 40px 40px;
            line-height: 1.6;
            font-size: 16px;
        }

        .email-body p {
            color: #555555;
            margin-bottom: 30px;
        }

        .otp-box {
            background-color: #f9fbf9;
            border: 2px dashed #a5d6a7;
            border-radius: 8px;
            padding: 24px;
            margin: 0 auto 30px auto;
            max-width: 300px;
        }

        .otp-code {
            position: relative;
            font-family: source-code-pro, Menlo, Monaco, Consolas, "Courier New", monospace;
            font-size: 42px;
            font-weight: bold;
            color: #2E7D32;
            letter-spacing: 6px;
            margin: 0;
            line-height: 1;
        }

        .notice-text {
            font-size: 14px;
            color: #888888;
        }

        .notice-text strong {
            color: #555555;
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
            .email-body, .email-header {
                padding-left: 24px;
                padding-right: 24px;
            }
            .otp-code {
                font-size: 36px;
                letter-spacing: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-content">
            <!-- Header -->
            <div class="email-header">
                <span class="brand-text">JuanCharge</span>
                <h1>Verify Your Identity</h1>
            </div>

            <!-- Body -->
            <div class="email-body">
                <p>You recently requested a security code to access your JuanCharge account. Please enter the verification code below to proceed.</p>
                
                <div class="otp-box">
                    <p class="otp-code">{{ $otp }}</p>
                </div>

                <p class="notice-text">
                    This code will expire in <strong>10 minutes</strong>.<br>
                    If you did not request this code, you may safely ignore this email.
                </p>
            </div>

            <!-- Footer -->
            <div class="email-footer">
                <p>&copy; {{ date('Y') }} JuanCharge. All rights reserved.</p>
                <p>This is an automated security message. Please do not reply directly to this email.</p>
            </div>
        </div>
    </div>
</body>
</html>
