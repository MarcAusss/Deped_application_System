<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>

<h2>System Login</h2>

<form method="POST" action="/login">
    @csrf

    <input type="email" name="email" placeholder="Email" required>
    <br><br>

    <input type="password" name="password" placeholder="Password" required>
    <br><br>

    <button type="submit">Login</button>
</form>

@if ($errors->any())
    <p style="color:red;">
        {{ $errors->first() }}
    </p>
@endif

</body>
</html>