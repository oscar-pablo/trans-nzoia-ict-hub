<?php
session_start();
require_once 'page_db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $answer1 = trim($_POST['answer1'] ?? '');
    $answer2 = trim($_POST['answer2'] ?? '');
    $answer3 = trim($_POST['answer3'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($email) || empty($answer1) || empty($answer2) || empty($answer3) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'All fields are required.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New passwords do not match.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, security_answer_1, security_answer_2, security_answer_3 FROM admins WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin) {
                // Verify security answers case-insensitively, trimmed
                $ans1_ok = password_verify(strtolower($answer1), $admin['security_answer_1']);
                $ans2_ok = password_verify(strtolower($answer2), $admin['security_answer_2']);
                $ans3_ok = password_verify(strtolower($answer3), $admin['security_answer_3']);

                if ($ans1_ok && $ans2_ok && $ans3_ok) {
                    // Update password
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $updateStmt = $pdo->prepare("UPDATE admins SET password = :password WHERE id = :id");
                    $updateStmt->execute([
                        ':password' => $hashedPassword,
                        ':id' => $admin['id']
                    ]);
                    
                    // Redirect to login page with success
                    header("Location: page_admin-login.php?reset=success");
                    exit;
                } else {
                    $error = 'One or more security answers are incorrect.';
                }
            } else {
                $error = 'No administrator account found with that email address.';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password — Trans-Nzoia Community ICT Hub</title>
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
    .forgot-card {
      background: var(--white);
      border-radius: 16px;
      padding: 2.5rem 2rem;
      width: 100%;
      max-width: 480px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.4);
    }
    .forgot-logo {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 1.8rem;
    }
    .forgot-logo-badge {
      width: 44px; height: 44px;
      background: var(--red);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
      overflow: hidden;
      padding: 2px;
    }
    .forgot-logo-badge img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
    .forgot-logo-text {
      font-family: 'Poppins', sans-serif;
      font-size: 0.85rem; font-weight: 700; color: var(--navy); line-height: 1.3;
    }
    .forgot-logo-text small {
      display: block; font-size: 0.62rem; font-weight: 400;
      color: var(--muted); letter-spacing: 0.03em;
    }
    .forgot-heading { font-size: 1.5rem; font-weight: 800; color: var(--navy); margin-bottom: 0.3rem; }
    .forgot-sub { font-size: 0.83rem; color: var(--muted); margin-bottom: 1.8rem; line-height: 1.5; }
    
    .form-group { margin-bottom: 1.2rem; }
    .form-label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--navy); margin-bottom: 5px; }
    .form-input {
      width: 100%; padding: 11px 13px;
      border: 1.5px solid var(--border); border-radius: 8px;
      font-size: 0.9rem; font-family: 'Inter', sans-serif;
      color: var(--text); background: var(--white); outline: none; transition: all 0.2s;
    }
    .form-input:focus { border-color: var(--red); }
    .form-input:disabled { background-color: #f5f5f5; color: #888; border-color: #ddd; cursor: not-allowed; }
    .form-input::placeholder { color: #bbb; }
    
    .email-row {
      display: flex;
      gap: 10px;
    }
    .email-row .form-input {
      flex: 1;
    }
    .verify-btn {
      background: var(--navy);
      color: var(--white);
      border: none;
      padding: 0 16px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 0.85rem;
      cursor: pointer;
      transition: background 0.2s;
    }
    .verify-btn:hover { background: var(--navy-mid); }
    .verify-btn:disabled { background: #ccc; cursor: not-allowed; }

    /* Security questions section */
    #questions-section {
      max-height: 0;
      opacity: 0;
      overflow: hidden;
      transition: all 0.4s ease-out;
      border-top: 1px dashed var(--border);
      padding-top: 0;
      margin-top: 0;
    }
    #questions-section.active {
      max-height: 1000px;
      opacity: 1;
      padding-top: 1.5rem;
      margin-top: 1rem;
    }

    .error-msg {
      background: #FDECEA; border: 1px solid #FFCDD2; border-radius: 8px;
      padding: 10px 14px; font-size: 0.8rem; color: var(--error); margin-bottom: 1.2rem;
    }
    .info-msg {
      background: #E8F5E9; border: 1px solid #C8E6C9; border-radius: 8px;
      padding: 10px 14px; font-size: 0.8rem; color: #2E7D32; margin-bottom: 1.2rem;
    }

    .action-btn {
      width: 100%; background: var(--red); color: var(--white); border: none;
      padding: 13px; border-radius: 10px; font-size: 0.97rem; font-weight: 700;
      font-family: 'Poppins', sans-serif; cursor: pointer; margin-top: 0.8rem;
      letter-spacing: 0.02em; transition: background 0.2s, transform 0.1s;
    }
    .action-btn:hover  { background: var(--red-hover); }
    .action-btn:disabled { background: #ccc; cursor: not-allowed; }
    .action-btn:active { transform: scale(0.99); }

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
    
    .loading-spinner {
      display: inline-block;
      width: 16px;
      height: 16px;
      border: 2px solid rgba(255,255,255,0.3);
      border-radius: 50%;
      border-top-color: var(--white);
      animation: spin 0.8s ease-in-out infinite;
      margin-right: 5px;
      vertical-align: middle;
    }
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
  </style>
</head>
<body>

  <div class="forgot-card">
    <div class="forgot-logo">
      <div class="forgot-logo-badge">
        <img src="images/favicon.png" alt="Trans-Nzoia ICT Hub logo">
      </div>
      <div class="forgot-logo-text">
        Trans-Nzoia Community ICT Hub
        <small>Admin Portal</small>
      </div>
    </div>

    <h1 class="forgot-heading">Reset Password</h1>
    <p class="forgot-sub">Enter your email to verify your identity and answer security questions to reset your password.</p>

    <!-- Error container -->
    <div id="local-error" class="error-msg" style="display: none;"></div>
    <?php if ($error): ?>
      <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form action="page_forgot-password.php" method="POST" id="forgotForm">
      
      <!-- Email Address Field -->
      <div class="form-group">
        <label class="form-label" for="email">Admin Email Address</label>
        <div class="email-row">
          <input
            class="form-input" type="email" id="email" name="email"
            placeholder="Enter your registered email"
            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
            required
          >
          <button type="button" class="verify-btn" id="verifyEmailBtn">Verify Email</button>
        </div>
      </div>

      <!-- Security Questions Section (Loaded via AJAX) -->
      <div id="questions-section">
        
        <div class="form-group">
          <label class="form-label" id="q1-label" for="answer1">Question 1</label>
          <input class="form-input" type="text" id="answer1" name="answer1" placeholder="Enter your answer" required disabled>
        </div>

        <div class="form-group">
          <label class="form-label" id="q2-label" for="answer2">Question 2</label>
          <input class="form-input" type="text" id="answer2" name="answer2" placeholder="Enter your answer" required disabled>
        </div>

        <div class="form-group">
          <label class="form-label" id="q3-label" for="answer3">Question 3</label>
          <input class="form-input" type="text" id="answer3" name="answer3" placeholder="Enter your answer" required disabled>
        </div>

        <div class="form-group" style="margin-top: 1.5rem;">
          <label class="form-label" for="new_password">New Password</label>
          <input class="form-input" type="password" id="new_password" name="new_password" placeholder="Min 6 characters" required disabled>
        </div>

        <div class="form-group">
          <label class="form-label" for="confirm_password">Confirm New Password</label>
          <input class="form-input" type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your new password" required disabled>
        </div>

        <button class="action-btn" type="submit" id="submitBtn" disabled>Reset Password</button>
      </div>

    </form>

    <a href="page_admin-login.php" class="back-btn">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
      Back to Login
    </a>
  </div>

  <script>
    const emailInput = document.getElementById('email');
    const verifyBtn = document.getElementById('verifyEmailBtn');
    const questionsSection = document.getElementById('questions-section');
    const localError = document.getElementById('local-error');

    const q1Label = document.getElementById('q1-label');
    const q2Label = document.getElementById('q2-label');
    const q3Label = document.getElementById('q3-label');

    const answer1Input = document.getElementById('answer1');
    const answer2Input = document.getElementById('answer2');
    const answer3Input = document.getElementById('answer3');
    const newPasswordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const submitBtn = document.getElementById('submitBtn');

    verifyBtn.addEventListener('click', function() {
      const email = emailInput.value.trim();
      if (!email) {
        showError('Please enter a valid email address.');
        return;
      }

      // Show loader
      verifyBtn.disabled = true;
      verifyBtn.innerHTML = '<span class="loading-spinner"></span>Verifying...';
      hideError();

      fetch('api_get-questions.php?email=' + encodeURIComponent(email))
        .then(response => response.json())
        .then(data => {
          verifyBtn.disabled = false;
          verifyBtn.innerHTML = 'Verify Email';

          if (data.status === 'success') {
            // Set question labels
            q1Label.textContent = 'Question 1: ' + data.question_1;
            q2Label.textContent = 'Question 2: ' + data.question_2;
            q3Label.textContent = 'Question 3: ' + data.question_3;

            // Enable fields
            answer1Input.disabled = false;
            answer2Input.disabled = false;
            answer3Input.disabled = false;
            newPasswordInput.disabled = false;
            confirmPasswordInput.disabled = false;
            submitBtn.disabled = false;

            // Make email read-only so they don't change it after verification
            emailInput.readOnly = true;
            verifyBtn.style.display = 'none';

            // Show form section
            questionsSection.classList.add('active');
          } else {
            showError(data.message);
            questionsSection.classList.remove('active');
          }
        })
        .catch(err => {
          verifyBtn.disabled = false;
          verifyBtn.innerHTML = 'Verify Email';
          showError('Unable to connect to verification server. Please try again.');
          console.error(err);
        });
    });

    // Form client-side validation
    document.getElementById('forgotForm').addEventListener('submit', function(e) {
      const p1 = newPasswordInput.value;
      const p2 = confirmPasswordInput.value;

      if (p1 !== p2) {
        e.preventDefault();
        showError('New passwords do not match.');
        return;
      }

      if (p1.length < 6) {
        e.preventDefault();
        showError('Password must be at least 6 characters long.');
        return;
      }
    });

    function showError(msg) {
      localError.textContent = msg;
      localError.style.display = 'block';
    }

    function hideError() {
      localError.style.display = 'none';
    }

    // Auto-verify if email is pre-populated after error post
    if (emailInput.value.trim() !== '') {
      // If server returned an error but email is filled, let them verify again or check if we should trigger verify
      // (Let the user click to prevent annoying loops on page load, but we keep the value)
    }
  </script>

</body>
</html>
