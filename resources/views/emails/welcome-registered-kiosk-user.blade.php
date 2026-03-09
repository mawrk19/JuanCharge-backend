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
        }

        .email-header {
            background-color: #2E7D32;
            padding: 40px 40px 30px 40px;
            text-align: center;
        }
        
        .header-logo {
            font-size: 28px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: 1px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .header-subtitle {
            color: #a5d6a7;
            font-size: 14px;
            margin: 0;
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

        .welcome-text {
            color: #555555;
            margin-bottom: 30px;
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

        .data-value {
            font-family: source-code-pro, Menlo, Monaco, Consolas, "Courier New", monospace;
            background-color: #ffffff;
            border: 1px solid #dcdcdc;
            padding: 4px 8px;
            border-radius: 4px;
            color: #2E7D32;
            font-weight: bold;
        }

        .perks-grid {
            margin: 30px 0;
            border-top: 1px solid #eeeeee;
            padding-top: 24px;
        }

        .perks-title {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 16px;
        }

        .perk-item {
            margin-bottom: 12px;
            font-size: 15px;
            color: #555555;
            padding-left: 20px;
            position: relative;
        }
        
        .perk-item::before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #4CAF50;
            font-weight: bold;
        }

        .button-container {
            text-align: center;
            margin: 40px 0 20px 0;
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
                padding: 30px 24px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-content">
            <!-- Header -->
            <div class="email-header">
                <div class="header-logo">JuanCharge</div>
                <p class="header-subtitle">Powering a Sustainable Tomorrow</p>
            </div>

            <!-- Body -->
            <div class="email-body">
                <h2>Welcome Aboard, {{ $userName }}!</h2>
                
                <p class="welcome-text">
                    Thank you for joining the JuanCharge community! Your account is officially set up and you're ready to start finding green energy stations, recycling intelligently, and earning rewards.
                </p>
                
                <div class="credentials-box">
                    <p style="margin-bottom: 16px; font-weight: 600; color: #1a1a1a;">Your Account Information</p>
                    <p><span class="credential-label">Email:</span> {{ $email }}</p>
                    <p><span class="credential-label">Password:</span> <span class="data-value">{{ $password }}</span></p>
                    <p style="margin-top: 12px; padding-top: 12px; border-top: 1px dashed #e2ece3;">
                        <span class="credential-label">Balance:</span> <b style="color: #4CAF50;">{{ $points }} Points</b>
                    </p>
                </div>

                <div class="perks-grid">
                    <div class="perks-title">What you can do with JuanCharge:</div>
                    <div class="perk-item">Charge your devices at any of our green kiosks</div>
                    <div class="perk-item">Recycle materials effortlessly to earn reward points</div>
                    <div class="perk-item">Track your positive environmental impact</div>
                    <div class="perk-item">Climb the eco-warrior leaderboard</div>
                </div>

                <div class="button-container">
                    <a href="{{ config('app.frontend_url', 'https://juancharge.vercel.app') }}/login" class="action-button">
                        Access Your Account
                    </a>
                </div>
            </div>

            <!-- Footer -->
            <div class="email-footer">
                <p>&copy; {{ date('Y') }} JuanCharge. All rights reserved.</p>
                <p>Sustainable Energy • Eco-Friendly • Community Driven</p>
                <p style="margin-top: 16px;">This is an automated welcome message. Please do not reply directly to this email.</p>
            </div>
        </div>
    </div>
</body>
</html>
