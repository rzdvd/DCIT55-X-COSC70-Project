<?php

session_start();

require_once __DIR__ . '/../database/database.php';
require_once __DIR__ . '/../helpers.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($token === '') {
        redirect_with('forgot-password.html', 'Invalid or missing reset token.');
    }

    if (strlen($password) < 6) {
        redirect_with('reset-password.php?token=' . urlencode($token), 'Password must be at least 6 characters.');
    }

    if ($password !== $confirmPassword) {
        redirect_with('reset-password.php?token=' . urlencode($token), 'Passwords do not match.');
    }

    $stmt = $conn->prepare(
        'SELECT email FROM password_resets WHERE reset_token = ? AND expires_at > NOW() LIMIT 1'
    );
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $reset = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$reset) {
        redirect_with('forgot-password.html', 'This reset link is invalid or has expired.');
    }

    $passwordHash = hash_password($password);
    $email = $reset['email'];

    $update = $conn->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
    $update->bind_param('ss', $passwordHash, $email);
    $update->execute();
    $update->close();

    $delete = $conn->prepare('DELETE FROM password_resets WHERE email = ?');
    $delete->bind_param('s', $email);
    $delete->execute();
    $delete->close();

    redirect_with('login.html', null, 'Your password has been reset. You can sign in now.');
}

$tokenValid = false;
$errorMessage = $_GET['error'] ?? null;

if ($token !== '') {
    $stmt = $conn->prepare(
        'SELECT email FROM password_resets WHERE reset_token = ? AND expires_at > NOW() LIMIT 1'
    );
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $tokenValid = (bool) $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if ($token === '' || !$tokenValid) {
    redirect_with('forgot-password.html', 'This reset link is invalid or has expired.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HanapDormIndang Reset Password</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            height: 100vh;
            background: url('../../images/bg.png') no-repeat center center/cover;
            overflow: hidden;
            position: relative;
        }

        body::before {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
            z-index: 0;
        }

        .page {
            position: relative;
            z-index: 1;
            width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .title {
            text-align: center;
            color: white;
            font-size: 42px;
            font-weight: bold;
            line-height: 1.1;
            margin-bottom: 18px;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.2);
        }

        .card {
            width: 340px;
            padding: 30px 26px;
            border-radius: 22px;
            background: rgba(10, 15, 35, 0.78);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 
                0 0 12px rgba(255, 255, 255, 0.12),
                0 0 30px rgba(255, 255, 255, 0.06);
        }

        .text {
            color: #b8c3db;
            font-size: 14px;
            margin-bottom: 18px;
            text-align: center;
            line-height: 1.4;
        }

        .alert {
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 14px;
            font-size: 13px;
            line-height: 1.4;
            display: none;
        }

        .alert-error {
            background: rgba(255, 80, 80, 0.15);
            border: 1px solid rgba(255, 100, 100, 0.5);
            color: #ffb3b3;
        }

        .alert-success {
            background: rgba(80, 255, 160, 0.12);
            border: 1px solid rgba(100, 255, 180, 0.45);
            color: #b8ffd9;
        }

        .form-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            color: #b8c3db;
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 12px 14px;
            border-radius: 12px;
            border: 2px solid rgba(255, 255, 255, 0.15);
            background: rgba(255, 255, 255, 0.06);
            color: white;
            outline: none;
            transition: 0.3s;
            font-size: 14px;
        }

        input::placeholder {
            color: #999;
        }

        input:hover,
        input:focus {
            border-color: #00d9ff;
            box-shadow: 
                0 0 8px rgba(0, 217, 255, 0.7),
                0 0 15px rgba(0, 217, 255, 0.4);
        }

        .password-strength {
            margin-top: 6px;
            height: 4px;
            border-radius: 2px;
            background: rgba(255, 255, 255, 0.1);
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s, background-color 0.3s;
            border-radius: 2px;
        }

        .strength-text {
            font-size: 11px;
            margin-top: 4px;
            color: #999;
            transition: 0.3s;
        }

        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 30px;
            background: linear-gradient(to right, #00d9ff, #ffffff);
            color: black;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 8px;
        }

        button:hover {
            transform: scale(1.03);
            box-shadow: 
                0 0 10px rgba(0, 217, 255, 0.7),
                0 0 20px rgba(255, 255, 255, 0.4);
        }

        button:active {
            transform: scale(0.98);
        }

        .back {
            text-align: center;
            margin-top: 18px;
            font-size: 14px;
        }

        .back a {
            color: #4fdcff;
            text-decoration: none;
            transition: 0.3s;
        }

        .back a:hover {
            text-decoration: underline;
            color: #ffffff;
        }

        .toggle-password {
            position: relative;
        }

        .toggle-btn {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #00d9ff;
            cursor: pointer;
            font-size: 12px;
            width: auto;
            padding: 0;
            margin: 0;
            height: auto;
        }

        .toggle-btn:hover {
            transform: translateY(-50%) scale(1.1);
        }
    </style>
</head>
<body>
    <div class="page">
        <h1 class="title">Set Your<br>New Password</h1>
        <div class="card">
            <div id="formAlert" class="alert"></div>
            
            <p class="text">
                Enter a new password (at least 6 characters) to regain access to your account.
            </p>

            <form method="POST" action="reset-password.php" id="resetForm">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="form-group">
                    <label for="password">New Password</label>
                    <div class="toggle-password">
                        <input 
                            type="password" 
                            id="password"
                            name="password" 
                            placeholder="Enter new password" 
                            required 
                            minlength="6"
                        >
                        <button type="button" class="toggle-btn" onclick="togglePassword('password')">Show</button>
                    </div>
                    <div class="password-strength">
                        <div class="strength-bar" id="strengthBar"></div>
                    </div>
                    <div class="strength-text" id="strengthText"></div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="toggle-password">
                        <input 
                            type="password" 
                            id="confirm_password"
                            name="confirm_password" 
                            placeholder="Repeat your password" 
                            required 
                            minlength="6"
                        >
                        <button type="button" class="toggle-btn" onclick="togglePassword('confirm_password')">Show</button>
                    </div>
                </div>

                <button type="submit">Update Password</button>
            </form>

            <div class="back">
                <a href="login.html">← Back to Login</a>
            </div>
        </div>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        const resetForm = document.getElementById('resetForm');
        const alert = document.getElementById('formAlert');

        // Password strength checker
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let strengthLabel = '';
            let color = '';

            if (password.length >= 6) strength += 1;
            if (password.length >= 10) strength += 1;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 1;
            if (/\d/.test(password)) strength += 1;
            if (/[^a-zA-Z\d]/.test(password)) strength += 1;

            switch (strength) {
                case 0:
                case 1:
                    strengthLabel = 'Weak';
                    color = '#ff6b6b';
                    break;
                case 2:
                case 3:
                    strengthLabel = 'Fair';
                    color = '#ffa500';
                    break;
                case 4:
                    strengthLabel = 'Good';
                    color = '#4ecdc4';
                    break;
                case 5:
                    strengthLabel = 'Strong';
                    color = '#50ffa0';
                    break;
            }

            const percentage = (strength / 5) * 100;
            strengthBar.style.width = percentage + '%';
            strengthBar.style.backgroundColor = color;
            strengthText.textContent = strengthLabel ? 'Strength: ' + strengthLabel : '';
            strengthText.style.color = color;

            // Check if passwords match
            if (confirmInput.value && password !== confirmInput.value) {
                confirmInput.style.borderColor = '#ff6b6b';
            } else if (confirmInput.value) {
                confirmInput.style.borderColor = 'rgba(255, 255, 255, 0.15)';
            }
        });

        // Check confirm password matches
        confirmInput.addEventListener('input', function() {
            if (passwordInput.value !== this.value && this.value) {
                this.style.borderColor = '#ff6b6b';
            } else {
                this.style.borderColor = 'rgba(255, 255, 255, 0.15)';
            }
        });

        // Form validation and submission
        resetForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const password = passwordInput.value.trim();
            const confirmPassword = confirmInput.value.trim();

            if (!password) {
                showAlert('Please enter a password', 'error');
                return;
            }

            if (password.length < 6) {
                showAlert('Password must be at least 6 characters', 'error');
                return;
            }

            if (password !== confirmPassword) {
                showAlert('Passwords do not match', 'error');
                return;
            }

            this.submit();
        });

        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const btn = event.target;
            if (field.type === 'password') {
                field.type = 'text';
                btn.textContent = 'Hide';
            } else {
                field.type = 'password';
                btn.textContent = 'Show';
            }
        }

        function showAlert(message, type) {
            alert.textContent = message;
            alert.className = 'alert alert-' + type;
            alert.style.display = 'block';
            alert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        // Handle URL error messages
        window.addEventListener('load', function() {
            const params = new URLSearchParams(window.location.search);
            const error = params.get('error');
            if (error) {
                showAlert(decodeURIComponent(error), 'error');
            }
        });
    </script>
</body>
</html>
