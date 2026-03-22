<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Your Account Credentials</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px;">

    <div style="background: #ffffff; padding: 30px; border-radius: 8px;">

        <h2>Hello {{ $fullName }},</h2>

        <p>Your account has been created successfully.</p>

        <p><strong>Email:</strong> {{ $email }}</p>
        <p><strong>Password:</strong> {{ $password }}</p>

        <p>Please login and change your password immediately.</p>

        <br>
        <p>Regards,<br>District Admin</p>

    </div>

</body>
</html>
