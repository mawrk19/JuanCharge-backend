@component('mail::message')
# JuanCharge LGU Portal

## Welcome, {{ $user->name }}

An account has been provisioned for you on the JuanCharge LGU Portal. You can now access the system to manage your local government unit's recycling and charging records.

@component('mail::panel')
**Your Account Credentials**
* **Email:** {{ $user->email }}
* **Password:** {{ $password }}
@endcomponent

**Security Requirements:**
* This is a temporary system-generated password.
* You will be required to change your password upon your first login.
* Do not share these credentials with anyone.

@component('mail::button', ['url' => config('app.frontend_url', 'https://juancharge.vercel.app') . '/login', 'color' => 'green'])
Sign In to Portal
@endcomponent

If you encounter any issues logging in, please contact the JuanCharge support team immediately.

Thank you,<br>
The JuanCharge Team
@endcomponent