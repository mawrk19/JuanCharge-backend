<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to JuanCharge!</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
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
            background: linear-gradient(135deg, #1b5e20 0%, #4caf50 50%, #81c784 100%);
            padding: 60px 40px;
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
                rgba(129, 199, 132, 0.2) 0%, 
                rgba(76, 175, 80, 0.3) 50%, 
                rgba(27, 94, 32, 0.4) 100%);
            animation: shimmer 3s ease-in-out infinite;
        }
        
        @keyframes shimmer {
            0%, 100% { opacity: 0.8; }
            50% { opacity: 1; }
        }
        
        .logo {
            font-size: 42px;
            font-weight: bold;
            color: white;
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .tagline {
            font-size: 18px;
            color: #c8e6c9;
            position: relative;
            z-index: 1;
            letter-spacing: 2px;
            font-weight: 300;
        }
        
        .content {
            padding: 50px 40px;
        }
        
        .welcome-badge {
            background: linear-gradient(135deg, #4caf50 0%, #81c784 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            display: inline-block;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 25px;
        }
        
        .welcome-title {
            font-size: 36px;
            color: #1b5e20;
            margin-bottom: 20px;
            font-weight: 700;
        }
        
        .welcome-message {
            font-size: 17px;
            color: #555;
            line-height: 1.8;
            margin-bottom: 35px;
        }
        
        .success-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        
        .credentials-box {
            background: linear-gradient(135deg, #f1f8e9 0%, #dcedc8 100%);
            border-left: 5px solid #4caf50;
            padding: 35px;
            border-radius: 12px;
            margin-bottom: 35px;
        }
        
        .credentials-title {
            font-size: 20px;
            color: #1b5e20;
            margin-bottom: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .credentials-title::before {
            content: "✓";
            background: #4caf50;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-weight: bold;
        }
        
        .credential-item {
            margin-bottom: 18px;
        }
        
        .credential-label {
            font-size: 13px;
            color: #2e7d32;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .credential-value {
            font-size: 18px;
            color: #1b5e20;
            font-weight: 600;
            font-family: 'Courier New', monospace;
            background: white;
            padding: 12px 15px;
            border-radius: 6px;
            display: inline-block;
            min-width: 250px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .points-badge {
            background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
            color: white;
            padding: 10px 25px;
            border-radius: 20px;
            display: inline-block;
            font-weight: 600;
            font-size: 18px;
            margin-top: 10px;
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
        }
        
        .cta-button {
            background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
            color: #000000;
            padding: 18px 45px;
            text-decoration: none;
            border-radius: 8px;
            display: inline-block;
            font-weight: 700;
            font-size: 16px;
            margin-top: 25px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 20px rgba(76, 175, 80, 0.4);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(76, 175, 80, 0.5);
        }
        
        .benefits-section {
            background: #f9fbe7;
            padding: 30px;
            border-radius: 12px;
            margin-top: 35px;
            border: 2px solid #dcedc8;
        }
        
        .benefits-title {
            font-size: 22px;
            color: #1b5e20;
            margin-bottom: 20px;
            font-weight: 700;
            text-align: center;
        }
        
        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        
        .benefit-item {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .benefit-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .benefit-text {
            color: #2e7d32;
            font-size: 14px;
            font-weight: 600;
        }
        
        .next-steps {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            padding: 30px;
            border-radius: 12px;
            margin-top: 30px;
        }
        
        .next-steps-title {
            font-size: 20px;
            color: #1b5e20;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .steps-list {
            list-style: none;
            padding-left: 0;
        }
        
        .steps-list li {
            padding: 12px 0;
            color: #2e7d32;
            font-size: 15px;
            line-height: 1.6;
            display: flex;
            align-items: flex-start;
        }
        
        .steps-list li::before {
            content: "→";
            margin-right: 15px;
            color: #4caf50;
            font-weight: bold;
            font-size: 20px;
        }
        
        .footer {
            background: #1b5e20;
            color: white;
            text-align: center;
            padding: 35px 40px;
            font-size: 14px;
        }
        
        .footer a {
            color: #81c784;
            text-decoration: none;
            font-weight: 600;
        }
        
        .social-links {
            margin-top: 20px;
        }
        
        .social-links a {
            display: inline-block;
            margin: 0 10px;
            font-size: 24px;
        }
        
        @media only screen and (max-width: 600px) {
            .content {
                padding: 30px 25px;
            }
            
            .welcome-title {
                font-size: 28px;
            }
            
            .credential-value {
                font-size: 16px;
                min-width: 100%;
            }
            
            .benefits-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">⚡ JuanCharge</div>
            <div class="tagline">Powering Sustainable Tomorrow</div>
        </div>
        
        <div class="content">
            <div style="text-align: center;">
                <div class="success-icon">🎉</div>
                <span class="welcome-badge">Account Created Successfully</span>
            </div>
            
            <h1 class="welcome-title">Welcome Aboard, {{ $userName }}!</h1>
            
            <p class="welcome-message">
                Thank you for joining the <strong>JuanCharge</strong> community! We're excited to have you on board. 
                Your account has been successfully created and you're now part of our mission to make sustainable energy accessible to everyone.
            </p>
            
            <div style="text-align: center;">
                <a href="#" class="cta-button">Get Started Now →</a>
            </div>
            
            <div class="benefits-section">
                <div class="benefits-title">🌱 What You Can Do Now</div>
                <div class="benefits-grid">
                    <div class="benefit-item">
                        <div class="benefit-icon">🔋</div>
                        <div class="benefit-text">Charge at Any Kiosk</div>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-icon">⭐</div>
                        <div class="benefit-text">Earn Reward Points</div>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-icon">♻️</div>
                        <div class="benefit-text">Track Recyclables</div>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-icon">🏆</div>
                        <div class="benefit-text">Join Leaderboard</div>
                    </div>
                </div>
            </div>
            
            <div class="next-steps">
                <div class="next-steps-title">🚀 Next Steps</div>
                <ul class="steps-list">
                    <li><strong>Login</strong> to your account using your email and password</li>
                    <li><strong>Find</strong> the nearest JuanCharge kiosk location</li>
                    <li><strong>Start charging</strong> and earning points with every session</li>
                    <li><strong>Track</strong> your environmental impact and rewards</li>
                    <li><strong>Redeem</strong> your points for exciting benefits</li>
                </ul>
            </div>
        </div>
        
        <div class="footer">
            <p style="font-size: 16px; font-weight: 600; margin-bottom: 10px;">
                Welcome to the Green Revolution! 🌍
            </p>
            <p>This is an automated welcome email from JuanCharge.</p>
            <p style="margin-top: 15px;">
                Questions? Contact us at 
                <a href="mailto:support@juancharge.com">support@juancharge.com</a>
            </p>
            <div class="social-links">
                <a href="#" title="Facebook">📘</a>
                <a href="#" title="Twitter">🐦</a>
                <a href="#" title="Instagram">📷</a>
            </div>
            <p style="margin-top: 20px; font-size: 12px; color: #a5d6a7;">
                © 2025 JuanCharge. All rights reserved.<br>
                Sustainable Energy • Eco-Friendly • Community Driven
            </p>
        </div>
    </div>
</body>
</html>
