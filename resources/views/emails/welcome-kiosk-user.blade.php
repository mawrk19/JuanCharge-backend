@component('mail::message')
# Welcome to JuanCharge!

Hello **{{ $userName }}**,

Your kiosk user account has been successfully created! You can now access your account and start earning points for sustainable charging.

@component('mail::panel')
**Your Login Credentials**
* **Email:** {{ $email }}
* **Password:** {{ $password }}
* **Current Points:** {{ $points }} Points
@endcomponent

⚠️ **Important:** Please change your password after your first login for security purposes.

@component('mail::button', ['url' => config('app.frontend_url', 'https://juancharge.vercel.app') . '/login', 'color' => 'green'])
Login to Your Account
@endcomponent

**What You Can Do:**
* Access charging stations at any JuanCharge kiosk
* Earn points with every charge
* Track your charging history
* Redeem rewards and benefits

Thank you,<br>
The JuanCharge Team
@endcomponent
