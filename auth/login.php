<?php
require_once '../config/database.php';
$db = new Database();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $db->escape_string($_POST['email']);
    $password = $_POST['password'];
    
    $errors = [];
    
    if (empty($email) || empty($password)) {
        $errors[] = "Email dan password harus diisi";
    }
    
    if (empty($errors)) {
        $query = "SELECT * FROM users WHERE email='$email'";
        $result = $db->conn->query($query);
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                if ($user['role'] == 'admin') {
                    redirect('../admin/index.php');
                } else {
                    redirect('../user/index.php');
                }
            } else {
                $errors[] = "Password salah";
            }
        } else {
            $errors[] = "Email tidak ditemukan";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login | AssetCare</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #09637E;
            --secondary: #088395;
            --accent: #7AB2B2;
            --light: #EBF4F6;
        }
        body {
            background: linear-gradient(135deg, var(--light), #ffffff);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .auth-card {
            background: #fff;
            width: 100%;
            max-width: 420px;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(9, 99, 126, 0.15);
        }
        .logo {
            text-align: center;
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 10px;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }
        .btn-primary {
            background: var(--primary);
            border: none;
            padding: 12px;
            font-weight: 600;
            border-radius: 10px;
        }
        .btn-primary:hover {
            background: var(--secondary);
        }
        .forgot-link {
            text-align: center;
            margin-top: 20px;
        }
        .forgot-link a {
            color: var(--primary);
            text-decoration: none;
        }
        .forgot-link a:hover {
            text-decoration: underline;
        }
        .password-wrapper {
            position: relative;
        }
        .password-wrapper input {
            padding-right: 45px;
        }
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--secondary);
            background: none;
            border: none;
            z-index: 10;
        }
        .toggle-password:hover {
            color: var(--primary);
        }
    </style>
</head>
<body>
<div class="auth-card">
    <div class="logo">AssetCare</div>
    <div class="subtitle">Sistem Sarana Prasarana Kantor<br>Silakan login untuk melanjutkan</div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= $error ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-4">
            <label class="form-label">Password</label>
            <div class="password-wrapper">
                <input type="password" name="password" id="password" class="form-control" required>
                <button type="button" class="toggle-password" onclick="togglePassword()">
                    <i class="far fa-eye" id="toggleIcon"></i>
                </button>
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>

    <div class="forgot-link">
        Lupa password? <a href="https://wa.me/6282116594051?text=Saya%20lupa%20password%20AssetCare" target="_blank">Hubungi Admin via WhatsApp</a>
    </div>
</div>

<script>
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>