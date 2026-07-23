<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Petty Cash Monitor</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f4f6f9;
            --surface: #ffffff;
            --border: #e5e8ed;
            --text: #1b2559;
            --text-secondary: #68769f;
            --text-muted: #a3aed0;
            --primary: #4318ff;
            --primary-dark: #3311d9;
            --danger: #ee5d50;
            --danger-bg: #fdecea;
            --radius: 16px;
            --radius-sm: 10px;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }
        .login-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: 0 10px 40px rgba(0,0,0,.08);
            width: 100%;
            max-width: 420px;
            padding: 40px 36px;
        }
        .login-header { text-align: center; margin-bottom: 32px; }
        .login-header h1 { font-size: 1.4rem; font-weight: 800; color: var(--text); }
        .login-header p { font-size: 0.88rem; color: var(--text-secondary); margin-top: 6px; }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block; font-size: 0.8rem; font-weight: 600;
            color: var(--text-secondary); margin-bottom: 6px;
            text-transform: uppercase; letter-spacing: 0.3px;
        }
        .form-control {
            width: 100%; padding: 11px 14px;
            border: 1.5px solid var(--border); border-radius: var(--radius-sm);
            font-size: 0.9rem; font-family: inherit; color: var(--text);
            background: var(--surface); transition: border-color .15s, box-shadow .15s;
        }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(67,24,255,.12); }
        .btn {
            width: 100%; padding: 12px; border: none; border-radius: var(--radius-sm);
            font-size: 0.9rem; font-weight: 700; font-family: inherit; cursor: pointer;
            transition: all .15s; text-align: center; text-decoration: none;
        }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-dark); box-shadow: 0 4px 14px rgba(67,24,255,.3); }
        .error-msg {
            background: var(--danger-bg); color: #b82c23; padding: 12px 16px;
            border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 500;
            margin-bottom: 20px; border: 1px solid rgba(238,93,80,.2);
        }
        .remember-row { display: flex; align-items: center; gap: 8px; margin-bottom: 20px; }
        .remember-row input[type="checkbox"] { accent-color: var(--primary); width: 16px; height: 16px; }
        .remember-row label { font-size: 0.84rem; color: var(--text-secondary); margin: 0; text-transform: none; letter-spacing: 0; font-weight: 400; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <h1>Petty Cash Monitor</h1>
            <p>Sign in to your account</p>
        </div>

        @if($errors->any())
            <div class="error-msg">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required autofocus placeholder="admin@example.com">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" required placeholder="Enter password">
            </div>
            <div class="remember-row">
                <input type="checkbox" name="remember" id="remember">
                <label for="remember">Remember me</label>
            </div>
            <button type="submit" class="btn btn-primary">Sign In</button>
        </form>
    </div>
</body>
</html>
