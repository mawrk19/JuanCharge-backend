@component('mail::message')
# Welcome Aboard!

Hello **{{ $userName }}**,

Thank you for joining the JuanCharge community! Your account is officially set up and you're ready to start finding green energy stations, recycling intelligently, and earning rewards.

@component('mail::panel')
**Your Account Information**
* **Email:** {{ $email }}
* **Password:** {{ $password }}
* **Balance:** {{ $points }} Points
@endcomponent

@component('mail::button', ['url' => config('app.frontend_url', 'https://juancharge.vercel.app') . '/login', 'color' => 'green'])
Access Your Account
@endcomponent

**What you can do with JuanCharge:**
* Charge your devices at any of our green kiosks
* Recycle materials effortlessly to earn reward points
* Track your positive environmental impact
* Climb the eco-warrior leaderboard

Thank you,<br>
The JuanCharge Team
@endcomponent
