@component('mail::message')
# Welcome to JuanCharge!

Hello {{ $user->first_name }},

An account has been created for you in the JuanCharge platform for your LGU. To get started, you need to verify your email address and set up your password.

Please click the button below to verify your email. This link is valid for one hour.

@component('mail::button', ['url' => $verificationUrl, 'color' => 'green'])
Verify Email and Set Password
@endcomponent

If you did not request this, please ignore this email.

Thank you,<br>
The JuanCharge Team
@endcomponent
