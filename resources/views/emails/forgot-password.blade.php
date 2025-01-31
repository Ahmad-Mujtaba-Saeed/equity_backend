<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
</head>
<body>
    <h2>Reset Your Password</h2>
    <p>You are receiving this email because we received a password reset request for your account.</p>
    <p>Click the button below to reset your password:</p>
    <a href="{{ env('APP_FRONTEND_URL') }}/auth/reset-password?token={{ $token }}" 
       style="background-color: #3B82F6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 20px 0;">
        Reset Password
    </a>
    <p>If you did not request a password reset, no further action is required.</p>
    <p>This password reset link will expire in 60 minutes.</p>
    <p>Regards,<br>{{ config('app.name') }}</p>
</body>
</html>
