<?php
require __DIR__ . '/../app/config.php';

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /login.php?logged_out=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    $isAdmin = ($username === 'admin' && $password === 'cloud123');
    $isMember = false;
    $roleText = "Station Guest";

    if (!$isAdmin) {
        try {
            $stmt = db()->prepare('SELECT * FROM group_members WHERE student_id = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user) {
                // If they have a hashed password in the DB (New Users)
                if (!empty($user['password'])) {
                    if (password_verify($password, $user['password'])) {
                        $isMember = true;
                    }
                } 
                // Fallback for your original core team members who don't have DB passwords
                else if ($password === 'weather2026') {
                    $isMember = true;
                }
                
                if ($isMember) {
                    $_SESSION['user_name'] = $user['student_name'];
                    $roleText = $user['role_name'];
                }
            }
        } catch (PDOException $e) {
            // DB connection failed
        }
    }

    if ($isAdmin || $isMember) {
        $_SESSION['logged_in'] = true;
        $_SESSION['admin_logged_in'] = $isAdmin;
        $_SESSION['user_nim'] = $username;
        
        if ($isAdmin) $roleText = "Station Administrator";
        
        $content = '<section class="panel compact" style="text-align: center;">
          <div style="width: 56px; height: 56px; border-radius: 50%; background: #d1fae5; border: 1px solid #a7f3d0; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; color: var(--success);">
            <i data-lucide="shield-check" style="width: 28px; height: 28px;"></i>
          </div>
          <h2 style="display: block; justify-content: center;">Access Granted</h2>
          <p style="margin-bottom: 24px; color: var(--text-secondary); font-size: 0.92rem;">Welcome, ' . htmlspecialchars($roleText) . '.</p>
          <a class="btn" href="/index.php" style="display: inline-flex; width: auto;"><i data-lucide="radar"></i> Enter Dashboard</a>
        </section>';
    } else {
        $content = '<section class="panel compact error-card" style="text-align: center;">
          <div style="width: 56px; height: 56px; border-radius: 50%; background: #fef2f2; border: 1px solid #fecaca; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; color: var(--error);">
            <i data-lucide="shield-alert" style="width: 28px; height: 28px;"></i>
          </div>
          <h2 style="display: block; color: var(--error); justify-content: center;">Authorization Denied</h2>
          <p style="margin-bottom: 24px; font-size: 0.92rem;">Invalid credentials. Please check your Student ID and password.</p>
          <a class="btn" href="/login.php" style="display: inline-flex; width: auto;"><i data-lucide="refresh-cw"></i> Try Again</a>
        </section>';
    }
    renderPage('Station Access', $content);
    exit;
}

// Handle Notification Banners
$noticeHtml = '';
if (isset($_GET['logged_out'])) {
    $noticeHtml = '
      <div style="margin-bottom: 16px; padding: 10px 14px; background: #e0f2fe; border: 1px solid #bae6fd; border-radius: 10px; font-size: 0.85rem; color: var(--accent-cyan); display: flex; align-items: center; justify-content: center; gap: 8px;">
        <i data-lucide="info" style="width: 16px; height: 16px;"></i>
        <span>Operator session terminated successfully.</span>
      </div>';
} elseif (isset($_GET['registered'])) {
    $noticeHtml = '
      <div style="margin-bottom: 16px; padding: 10px 14px; background: #d1fae5; border: 1px solid #a7f3d0; border-radius: 10px; font-size: 0.85rem; color: var(--success); display: flex; align-items: center; justify-content: center; gap: 8px;">
        <i data-lucide="check-circle" style="width: 16px; height: 16px;"></i>
        <span>Registration complete! You may now log in.</span>
      </div>';
}

$content = $noticeHtml . '
  <section class="panel compact">
    <div style="text-align: center; margin-bottom: 24px;">
      <h2 style="font-family: var(--font-heading); display: block;">Station Login</h2>
      <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 4px;">Log in with your Student ID (NIM)</p>
    </div>
    
    <form method="post" action="/login.php" class="form">
      <div class="form-group">
        <label><i data-lucide="hash"></i> Student ID / Admin</label>
        <input name="username" placeholder="E.g. 102022340119 or admin" required>
      </div>
      <div class="form-group">
        <label><i data-lucide="key-round"></i> Passcode</label>
        <input name="password" type="password" placeholder="Enter password" required>
      </div>
      <button type="submit" class="btn" style="margin-top: 10px;"><i data-lucide="log-in"></i> Access Station</button>
    </form>
    
    <div style="text-align: center; margin-top: 20px; font-size: 0.85rem;">
      <span style="color: var(--text-muted);">No account yet?</span> 
      <a href="/register.php" style="color: var(--accent-cyan); font-weight: 700; text-decoration: none;">Register here</a>
    </div>

    <div class="hint" style="margin-top: 24px;">
        <p><strong>Core Team:</strong> Use NIM & password: <em>weather2026</em></p>
        <p><strong>Admin:</strong> admin / cloud123</p>
    </div>
  </section>';

renderPage('Station Access', $content);
?>