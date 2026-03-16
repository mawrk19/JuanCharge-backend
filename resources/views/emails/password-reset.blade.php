@component('mail::message')
# 🔐 Password Reset Request

Hello {{ $userName ?? 'User' }},

We received a request to reset your password for your JuanCharge account. Click the button below to reset your password:

@component('mail::button', ['url' => $resetLink, 'color' => 'green'])
Reset Password
@endcomponent

**⚠️ Important:**
* This link will expire in 1 hour
* If you didn't request this password reset, please ignore this email
* Your password will remain unchanged if you don't click the link

After clicking the link, a new password will be automatically generated and sent to your email.

Thank you,<br>
The JuanCharge Team
@endcomponent
