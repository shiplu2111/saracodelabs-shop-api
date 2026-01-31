<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;">

    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">

        <h2 style="color: #333;">Hello, {{ $user->name }}</h2>

        <p style="color: #555; line-height: 1.6;">
            You are receiving this email because we received a password reset request for your account.
        </p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $url }}" style="background-color: #4F46E5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                Reset Password
            </a>
        </div>

        <p style="color: #555; line-height: 1.6;">
            This password reset link will expire in 60 minutes.
            If you did not request a password reset, no further action is required.
        </p>

        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">

        <p style="font-size: 12px; color: #999;">
            If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser: <br>
            <a href="{{ $url }}" style="color: #4F46E5;">{{ $url }}</a>
        </p>
    </div>

</body>
</html>
