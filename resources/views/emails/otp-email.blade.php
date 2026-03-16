@component('mail::message')
# Verify Your Identity

Hello,

You recently requested a security code to access your JuanCharge account. Please enter the verification code below to proceed.

@component('mail::panel')
# {{ $otp }}
@endcomponent

This code will expire in **10 minutes**.

If you did not request this code, you may safely ignore this email.

Thank you,<br>
The JuanCharge Team
@endcomponent
