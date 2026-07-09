<?php
session_start();

// If already logged in, go straight to admin dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: page_admin_dashboard.php");
    exit;
}

// Grab error from URL if redirected back here
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — Trans-Nzoia Community ICT Hub</title>
  <link rel="icon" type="image/png" href="images/favicon.png">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800;900&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --navy:       #003049;
      --navy-dark:  #001f30;
      --navy-mid:   #004060;
      --red:        #D62828;
      --red-hover:  #bf2020;
      --orange:     #F77F00;
      --amber:      #FCBF49;
      --amber-pale: #FFF5D6;
      --text:       #1A1A1A;
      --muted:      #5C5040;
      --border:     #E8D8C0;
      --white:      #FFFFFF;
      --error:      #e53935;
    }
    body {
      font-family: 'Inter', sans-serif;
      background: var(--navy-dark);
      color: var(--text);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 2rem;
    }
    h1, h2, h3 { font-family: 'Poppins', sans-serif; }
    .login-card {
      background: var(--white);
      border-radius: 16px;
      padding: 2.5rem 2rem;
      width: 100%;
      max-width: 420px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.4);
    }
    .login-logo {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 1.8rem;
    }
    .login-logo-badge {
      width: 44px; height: 44px;
      background: var(--red);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-family: 'Poppins', sans-serif;
      font-weight: 900; color: var(--white); font-size: 1rem; flex-shrink: 0;
    }
    .login-logo-text {
      font-family: 'Poppins', sans-serif;
      font-size: 0.85rem; font-weight: 700; color: var(--navy); line-height: 1.3;
    }
    .login-logo-text small {
      display: block; font-size: 0.62rem; font-weight: 400;
      color: var(--muted); letter-spacing: 0.03em;
    }
    .login-heading { font-size: 1.5rem; font-weight: 800; color: var(--navy); margin-bottom: 0.3rem; }
    .login-sub { font-size: 0.83rem; color: var(--muted); margin-bottom: 1.8rem; line-height: 1.5; }
    .form-group { margin-bottom: 1.1rem; }
    .form-label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--navy); margin-bottom: 5px; }
    .form-input {
      width: 100%; padding: 11px 13px;
      border: 1.5px solid var(--border); border-radius: 8px;
      font-size: 0.9rem; font-family: 'Inter', sans-serif;
      color: var(--text); background: var(--white); outline: none; transition: border-color 0.2s;
    }
    .form-input:focus { border-color: var(--red); }
    .form-input::placeholder { color: #bbb; }
    .password-wrap { position: relative; }
    .password-wrap .form-input { padding-right: 44px; }
    .toggle-pw {
      position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
      background: none; border: none; cursor: pointer; color: var(--muted);
      font-size: 0.8rem; font-family: 'Inter', sans-serif; padding: 2px 4px; transition: color 0.2s;
    }
    .toggle-pw:hover { color: var(--navy); }
    .error-msg {
      background: #FDECEA; border: 1px solid #FFCDD2; border-radius: 8px;
      padding: 10px 14px; font-size: 0.8rem; color: var(--error); margin-bottom: 1rem;
    }
    .success-msg {
      background: #E8F5E9; border: 1px solid #C8E6C9; border-radius: 8px;
      padding: 10px 14px; font-size: 0.8rem; color: #2E7D32; margin-bottom: 1rem;
    }
    .login-btn {
      width: 100%; background: var(--red); color: var(--white); border: none;
      padding: 13px; border-radius: 10px; font-size: 0.97rem; font-weight: 700;
      font-family: 'Poppins', sans-serif; cursor: pointer; margin-top: 0.5rem;
      letter-spacing: 0.02em; transition: background 0.2s, transform 0.1s;
    }
    .login-btn:hover  { background: var(--red-hover); }
    .login-btn:active { transform: scale(0.99); }
    .login-note { text-align: center; font-size: 0.74rem; color: rgba(255,255,255,0.35); margin-top: 1.5rem; }
    .back-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      color: var(--muted);
      font-size: 0.82rem;
      font-family: 'Inter', sans-serif;
      font-weight: 500;
      text-decoration: none;
      margin-top: 1.3rem;
      width: 100%;
      padding: 10px 14px;
      border-radius: 8px;
      border: 1px solid var(--border);
      background: transparent;
      transition: all 0.2s;
    }
    .back-btn:hover {
      color: var(--navy);
      background: var(--amber-pale);
      border-color: var(--amber);
    }
    .back-btn svg { flex-shrink: 0; }
    .login-logo-badge {
      width: 44px; height: 44px;
      background: var(--red);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
      overflow: hidden;
      padding: 2px;
    }
    .login-logo-badge img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
    @media (max-width: 480px) {
      body { padding: 1rem; }
      .login-card { padding: 2rem 1.2rem; }
      .login-heading { font-size: 1.3rem; }
    }
  </style>
</head>
<body>

  <div class="login-card">

    <div class="login-logo">
      <div class="login-logo-badge">
        <img src="images/favicon.png" alt="Trans-Nzoia ICT Hub logo">
      </div>
      <div class="login-logo-text">
        Trans-Nzoia Community ICT Hub
        <small>Admin Portal</small>
      </div>
    </div>

    <h1 class="login-heading">Welcome back</h1>
    <p class="login-sub">Sign in to access the admin dashboard and manage enrollments.</p>

    <!-- PHP prints the error here if credentials were wrong -->
    <?php if ($error === 'invalid'): ?>
      <div class="error-msg">Incorrect username or password. Please try again.</div>
    <?php elseif ($error === 'empty'): ?>
      <div class="error-msg">Please enter both your username and password.</div>
    <?php elseif (isset($_GET['reset']) && $_GET['reset'] === 'success'): ?>
      <div class="success-msg">Your password has been reset successfully. Please log in with your new password.</div>
    <?php endif; ?>

    <!-- Form posts directly to login.php -->
    <form action="p_login.php" method="POST">

      <div class="form-group">
        <label class="form-label" for="username">Username</label>
        <input
          class="form-input" type="text" id="username" name="username"
          placeholder="Enter your admin username"
          value="<?php echo htmlspecialchars($_GET['username'] ?? ''); ?>"
          autocomplete="username" required
        >
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <div class="password-wrap">
          <input
            class="form-input" type="password" id="password" name="password"
            placeholder="Enter your password"
            autocomplete="current-password" required
          >
          <button class="toggle-pw" onclick="togglePassword()" type="button" id="togglePwBtn">Show</button>
        </div>
        <div style="text-align: right; margin-top: 6px;">
          <a href="page_forgot-password.php" style="font-size: 0.78rem; color: var(--muted); text-decoration: none; font-weight: 500; transition: color 0.2s;" onmouseover="this.style.color='var(--navy)'" onmouseout="this.style.color='var(--muted)'">Forgot Password?</a>
        </div>
      </div>

      <button class="login-btn" type="submit">Sign In to Admin Panel</button>

    </form>

    <a href="index.html" class="back-btn">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
      Back to Home
    </a>

  </div>

  <p class="login-note">Restricted access &mdash; authorised personnel only.</p>

  <script>
    function togglePassword() {
      var input = document.getElementById('password');
      var btn   = document.getElementById('togglePwBtn');
      var hide  = input.type === 'password';
      input.type   = hide ? 'text' : 'password';
      btn.textContent = hide ? 'Hide' : 'Show';
    }
  </script>

</body>
</html>