<?php
require __DIR__ . '/../app/config.php';

// Redirect if already logged in
if (isset($_SESSION['logged_in'])) {
    header('Location: /index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['student_name'] ?? '');
    $nim = trim($_POST['student_id'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($name !== '' && $nim !== '' && $password !== '') {
        try {
            // Check if the NIM already exists
            $stmt = db()->prepare('SELECT id FROM group_members WHERE student_id = ?');
            $stmt->execute([$nim]);
            
            if ($stmt->fetch()) {
                $error = "That Student ID (NIM) is already registered.";
            } else {
                // Securely hash the password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $defaultRole = 'Station Guest';
                
                // Insert the new user into the shared database
                $insertStmt = db()->prepare('INSERT INTO group_members (student_name, student_id, role_name, password) VALUES (?, ?, ?, ?)');
                $insertStmt->execute([$name, $nim, $defaultRole, $hashedPassword]);
                
                // Redirect to login page with a success flag
                header('Location: /login.php?registered=1');
                exit;
            }
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

$errorHtml = '';
if ($error) {
    $errorHtml = '
      <div style="margin-bottom: 16px; padding: 10px 14px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; font-size: 0.85rem; color: var(--error); display: flex; align-items: center; gap: 8px;">
        <i data-lucide="circle-alert" style="width: 16px; height: 16px;"></i>
        <span>' . htmlspecialchars($error) . '</span>
      </div>';
}

$content = '
  <section class="panel compact">
    <div style="text-align: center; margin-bottom: 24px;">
      <div style="width: 56px; height: 56px; border-radius: 50%; background: #e0f2fe; border: 1px solid #bae6fd; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; color: var(--accent-cyan);">
        <i data-lucide="user-plus" style="width: 28px; height: 28px;"></i>
      </div>
      <h2 style="font-family: var(--font-heading); display: block;">Guest Registration</h2>
      <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 4px;">Create an account to access the station.</p>
    </div>
    
    ' . $errorHtml . '
    
    <form method="post" action="/register.php" class="form">
      <div class="form-group">
        <label><i data-lucide="user"></i> Full Name</label>
        <input name="student_name" placeholder="E.g. Kelly Vance" required>
      </div>
      
      <div class="form-group">
        <label><i data-lucide="hash"></i> Student ID (NIM)</label>
        <input name="student_id" placeholder="Enter valid NIM" required>
      </div>

      <div class="form-group">
        <label><i data-lucide="key-round"></i> Create Password</label>
        <input name="password" type="password" placeholder="Create a secure password" required>
      </div>

      <button type="submit" class="btn" style="margin-top: 10px;"><i data-lucide="user-check"></i> Register Account</button>
    </form>
    
    <div style="text-align: center; margin-top: 20px; font-size: 0.85rem;">
      <span style="color: var(--text-muted);">Already have an account?</span> 
      <a href="/login.php" style="color: var(--accent-cyan); font-weight: 700; text-decoration: none;">Log in here</a>
    </div>
  </section>';

renderPage('Register', $content);
?>