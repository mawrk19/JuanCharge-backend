@component('mail::message')
# ✅ Password Reset Successful

Hello {{ $userName ?? 'User' }},

Your password has been successfully reset. Here is your new password:

@component('mail::panel')
**{{ $newPassword }}**
@endcomponent

**📧 Email:** {{ $email }}

**⚠️ Important Security Notice:**
* **Change this password immediately** after logging in
* Use a strong, unique password that you haven't used before
* Do not share this password with anyone
* Delete this email after changing your password

**Next Steps:**
1. Copy the password above
2. Go to the JuanCharge login page
3. Login with your email and the new password
4. Immediately change your password in your profile settings

Thank you,<br>
The JuanCharge Team
@endcomponent
