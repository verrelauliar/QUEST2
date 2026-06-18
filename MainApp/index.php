<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../Config/supabase.php';

use Core\Services\AuthService;

$authService = new AuthService($supabase);

// Session Check
if ($authService->validateSession()['valid']) {
    $role = $_SESSION['user']['role'];
    header("Location: " . ($role === 'student' ? 'MainStudent/student_dashboard.php' : 'MainTeacher/teacher_dashboard.php'));
    exit;
}

$error = "";

// Login Handling
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $result = $authService->authenticateUser(
        trim($_POST["userid"]),
        trim($_POST["password"]),
        $_POST["role"]
    );
    
    if ($result['success']) {
        $authService->createSecureSession($result['user_data']);
        $role = $_POST["role"];
        header("Location: " . ($role === 'student' ? 'MainStudent/student_dashboard.php' : 'MainTeacher/teacher_dashboard.php'));
        exit;
    }
    $error = $result['message'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - QUEST</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../Assets/CSS/GlobalCSS/global.css">
    
    <script>
        function setRole(role) {
            document.getElementById("role").value = role;

            document.getElementById("login-title").innerText =
                role === "student" ? "Login as a Student" : "Login as a Teacher";

            const btnStudent = document.getElementById("btn-student");
            const btnTeacher = document.getElementById("btn-teacher");

            if (role === 'student') {
                btnStudent.classList.replace('btn-outline-dark', 'btn-dark');
                btnTeacher.classList.replace('btn-dark', 'btn-outline-dark');
            } else {
                btnTeacher.classList.replace('btn-outline-dark', 'btn-dark');
                btnStudent.classList.replace('btn-dark', 'btn-outline-dark');
            }
        }

        // === FUNGSI SHOW / HIDE PASSWORD ===
        function togglePassword() {
            const pwd = document.getElementById("password");
            const icon = document.getElementById("toggle-icon");

            if (pwd.type === "password") {
                pwd.type = "text";
                icon.textContent = "🙈"; 
            } else {
                pwd.type = "password";
                icon.textContent = "👁️"; 
            }
        }
    </script>
</head>
<body onload="setRole('student')">

<div class="container-fluid min-vh-100">
    <div class="row min-vh-100">
        
        <div class="col-lg-6 col-md-15 d-flex flex-column justify-content-center px-5 py-4 bg-white">
            <div style="max-width: 450px; margin: 0 auto; width: 100%;">

                <div class="mb-5 text-start">
                    <img src="../Assets/Images/logo_mumtazajhs2.png" alt="Logo" height="50" class="mb-3">
                    
                    <h3 class="fw-bold text-dark mb-0">Welcome to QUEST.</h3>
                    <p class="text-secondary small">(QUick Exam & Student Test)</p>
                </div>

                <div class="mb-4">
                    <h4 id="login-title" class="fw-bold text-dark mb-0">Login as a Student</h4>
                    <p class="text-secondary small mb-0">Please enter your credentials to access your account.</p>
                </div>

                <div class="d-flex gap-3 mb-4">
                    <button type="button" id="btn-student" class="btn btn-dark rounded-pill px-4" onclick="setRole('student')">Student</button>
                    <button type="button" id="btn-teacher" class="btn btn-outline-dark rounded-pill px-4" onclick="setRole('teacher')">Teacher</button>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 mb-3"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="role" id="role" value="student">

                    <div class="mb-3">
                        <input type="text" name="userid" class="form-control bg-light border-0 py-2" placeholder="User ID" required>
                    </div>

                    <!-- === INPUT PASSWORD + TOGGLE SHOW/HIDE === -->
                    <div class="mb-3 position-relative">
                        <input 
                            type="password" 
                            name="password" 
                            id="password" 
                            class="form-control bg-light border-0 py-2" 
                            placeholder="Password" 
                            required
                        >
                        <button 
                            type="button" 
                            class="btn position-absolute top-50 end-0 translate-middle-y me-2 p-0"
                            style="border: none; background: transparent;"
                            onclick="togglePassword()"
                        >
                            <span id="toggle-icon">👁️</span>
                        </button>
                    </div>

                    <div class="form-check mb-4">
                        <input type="checkbox" class="form-check-input" name="remember" id="remember">
                        <label class="form-check-label text-secondary small" for="remember">Remember my account</label>
                    </div>

                    <button type="submit" class="btn btn-dark w-100 py-2 rounded mb-3 brand-bg">Login</button>
                    <a href="#" class="text-secondary small text-decoration-none">Forgot your password?</a>
                </form>

                <footer class="mt-5 text-muted small text-center text-lg-start">
                    <p class="mb-0">Copyright © SMP Islam Mumtaza 2025</p>
                    <p>QUEST Development</p>
                </footer>
            </div>
        </div>

        <div class="col-lg-6 d-none d-lg-block p-0">
            <img src="../Assets/Images/mumtazaschool.jpg" alt="School Building" class="w-100 h-100 object-fit-cover">
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
