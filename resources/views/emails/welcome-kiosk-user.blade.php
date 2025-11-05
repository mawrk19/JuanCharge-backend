<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to JuanCharge</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 40px 20px;
            min-height: 100vh;
        }
        
        .email-container {
            max-width: 650px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }
        
        .header {
            background: linear-gradient(135deg, #0a2342 0%, #1565c0 50%, #2196f3 100%);
            padding: 50px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, 
                rgba(33, 150, 243, 0.2) 0%, 
                rgba(21, 101, 192, 0.3) 50%, 
                rgba(10, 35, 66, 0.4) 100%);
            animation: shimmer 3s ease-in-out infinite;
        }
        
        @keyframes shimmer {
            0%, 100% { opacity: 0.8; }
            50% { opacity: 1; }
        }
        
        .logo {
            font-size: 36px;
            font-weight: bold;
            color: white;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .tagline {
            font-size: 16px;
            color: #b3d9ff;
            position: relative;
            z-index: 1;
            letter-spacing: 1px;
        }
        
        .content {
            padding: 50px 40px;
        }
        
        .welcome-title {
            font-size: 32px;
            color: #0a2342;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .welcome-message {
            font-size: 16px;
            color: #555;
            line-height: 1.8;
            margin-bottom: 35px;
        }
        
        .credentials-box {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-left: 5px solid #2196f3;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 35px;
        }
        
        .credentials-title {
            font-size: 20px;
            color: #0a2342;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .credential-item {
            margin-bottom: 15px;
        }
        
        .credential-label {
            font-size: 13px;
            color: #1565c0;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .credential-value {
            font-size: 18px;
            color: #0a2342;
            font-weight: 600;
            font-family: 'Courier New', monospace;
            background: white;
            padding: 12px 15px;
            border-radius: 6px;
            display: inline-block;
            min-width: 250px;
        }
        
        .points-badge {
            background: linear-gradient(135deg, #2196f3 0%, #1565c0 100%);
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            display: inline-block;
            font-weight: 600;
            font-size: 16px;
            margin-top: 10px;
        }
        
        .cta-button {
            background: linear-gradient(135deg, #2196f3 0%, #1565c0 100%);
            color: #000000;
            padding: 16px 40px;
            text-decoration: none;
            border-radius: 8px;
            display: inline-block;
            font-weight: 600;
            font-size: 16px;
            margin-top: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 15px rgba(33, 150, 243, 0.3);
        }
        
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(33, 150, 243, 0.4);
        }
        
        .info-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-top: 30px;
        }
        
        .info-title {
            font-size: 18px;
            color: #0a2342;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .info-list {
            list-style: none;
            padding-left: 0;
        }
        
        .info-list li {
            padding: 8px 0;
            color: #555;
            font-size: 15px;
            line-height: 1.6;
        }
        
        .info-list li::before {
            content: "⚡";
            margin-right: 10px;
            color: #2196f3;
        }
        
        .footer {
            background: #0a2342;
            color: white;
            text-align: center;
            padding: 30px 40px;
            font-size: 14px;
        }
        
        .footer a {
            color: #64b5f6;
            text-decoration: none;
        }
        
        @media only screen and (max-width: 600px) {
            .content {
                padding: 30px 25px;
            }
            
            .welcome-title {
                font-size: 26px;
            }
            
            .credential-value {
                font-size: 16px;
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">⚡ JuanCharge</div>
            <div class="tagline">Sustainable Energy Solutions</div>
        </div>
        
        <div class="content">
            <h1 class="welcome-title">Welcome to JuanCharge!</h1>
            
            <p class="welcome-message">
                Hello <strong>{{ $userName }}</strong>,<br><br>
                Your kiosk user account has been successfully created! You can now access your account and start earning points for sustainable charging.
            </p>
            
            <div class="credentials-box">
                <div class="credentials-title">🔐 Your Login Credentials</div>
                
                <div class="credential-item">
                    <div class="credential-label">Email Address</div>
                    <div class="credential-value">{{ $email }}</div>
                </div>
                
                <div class="credential-item">
                    <div class="credential-label">Default Password</div>
                    <div class="credential-value">{{ $password }}</div>
                </div>
                
                <div class="credential-item">
                    <div class="credential-label">Current Points</div>
                    <span class="points-badge">{{ $points }} Points</span>
                </div>
            </div>
            
            <p style="color: #d32f2f; font-weight: 600; margin-bottom: 20px;">
                ⚠️ Important: Please change your password after your first login for security purposes.
            </p>
            
            <a href="#" class="cta-button">Login to Your Account</a>
            
            <div class="info-section">
                <div class="info-title">What You Can Do:</div>
                <ul class="info-list">
                    <li>Access charging stations at any JuanCharge kiosk</li>
                    <li>Earn points with every charge</li>
                    <li>Track your charging history</li>
                    <li>Redeem rewards and benefits</li>
                    <li>Monitor your environmental impact</li>
                </ul>
            </div>
        </div>
        
        <div class="footer">
            <p>This is an automated email from JuanCharge.</p>
            <p style="margin-top: 10px;">
                If you have any questions, please contact us at 
                <a href="mailto:support@juancharge.com">support@juancharge.com</a>
            </p>
            <p style="margin-top: 15px; font-size: 12px; color: #90caf9;">
                © 2025 JuanCharge. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
