<?php
session_start();
require_once 'page_db.php'; // gives us $pdo

// ---- Fixed, non-editable security questions ----
// These are hardcoded on the server. The form never lets a user submit
// custom question text, so there is nothing here for an attacker to
// inject into the security_question_* columns.
$SECURITY_QUESTIONS = [
    1 => 'What was the name of your first school?',
    2 => 'What is your favorite color?',
    3 => 'In what town or city was your first job?',
];

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // Questions always come from the server-side fixed list, never from $_POST.
    $q1 = $SECURITY_QUESTIONS[1];
    $q2 = $SECURITY_QUESTIONS[2];
    $q3 = $SECURITY_QUESTIONS[3];

    $a1 = trim($_POST['security_answer_1'] ?? '');
    $a2 = trim($_POST['security_answer_2'] ?? '');
    $a3 = trim($_POST['security_answer_3'] ?? '');

    // ---- Basic validation ----
    if ($username === '' || $fullName === '' || $email === '' || $password === '') {
        $errors[] = "Please fill in all required fields (username, full name, email, password).";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    if ($password !== $confirm) {
        $errors[] = "Password and confirmation do not match.";
    }
    if ($a1 === '' || $a2 === '' || $a3 === '') {
        $errors[] = "Please answer all three security questions.";
    }

    // ---- Check for existing username/email ----
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = :username OR email = :email");
        $stmt->execute([':username' => $username, ':email' => $email]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "That username or email is already registered.";
        }
    }

    // ---- Insert ----
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO admins (
                username, full_name, password, email,
                security_question_1, security_answer_1,
                security_question_2, security_answer_2,
                security_question_3, security_answer_3
            ) VALUES (
                :username, :full_name, :password, :email,
                :q1, :a1,
                :q2, :a2,
                :q3, :a3
            )");

            $stmt->execute([
                ':username' => $username,
                ':full_name' => $fullName,
                ':password' => password_hash($password, PASSWORD_DEFAULT),
                ':email'    => $email,
                ':q1' => $q1,
                ':a1' => password_hash($a1, PASSWORD_DEFAULT),
                ':q2' => $q2,
                ':a2' => password_hash($a2, PASSWORD_DEFAULT),
                ':q3' => $q3,
                ':a3' => password_hash($a3, PASSWORD_DEFAULT),
            ]);

            $success = true;
        } catch (PDOException $e) {
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register Admin — Trans-Nzoia Community ICT Hub</title>
  <link rel="icon" type="image/png" href="images/favicon.png">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800;900&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --navy:       #003049;
      --navy-dark:  #001f30;
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
    .card {
      background: var(--white);
      border-radius: 16px;
      padding: 2.5rem 2rem;
      width: 100%;
      max-width: 520px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.4);
    }
    .logo {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 1.6rem;
    }
    .logo-badge {
      width: 44px; height: 44px;
      background: var(--red);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      overflow: hidden; flex-shrink: 0; padding: 2px;
    }
    .logo-badge img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
    .logo-text { font-family: 'Poppins', sans-serif; font-size: 0.85rem; font-weight: 700; color: var(--navy); line-height: 1.3; }
    .logo-text small { display: block; font-size: 0.62rem; font-weight: 400; color: var(--muted); }
    .heading { font-size: 1.5rem; font-weight: 800; color: var(--navy); margin-bottom: 0.3rem; }
    .sub { font-size: 0.83rem; color: var(--muted); margin-bottom: 1.6rem; line-height: 1.5; }
    fieldset { border: none; margin-bottom: 1.2rem; }
    legend {
      font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 0.85rem;
      color: var(--navy); margin-bottom: 0.6rem; padding: 0;
    }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .form-group { margin-bottom: 1rem; }
    .form-label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--navy); margin-bottom: 5px; }
    .form-input {
      width: 100%; padding: 11px 13px;
      border: 1.5px solid var(--border); border-radius: 8px;
      font-size: 0.9rem; font-family: 'Inter', sans-serif;
      color: var(--text); background: var(--white); outline: none; transition: border-color 0.2s;
    }
    .form-input:focus { border-color: var(--red); }
    .hint { font-size: 0.72rem; color: var(--muted); margin-top: 4px; }
    .question-block { margin-bottom: 1.1rem; }
    .question-text {
      font-size: 0.85rem; font-weight: 600; color: var(--navy);
      background: var(--amber-pale); border: 1px solid var(--amber);
      border-radius: 8px; padding: 9px 12px; margin-bottom: 8px;
    }
    .error-box {
      background: #FDECEA; border: 1px solid #FFCDD2; border-radius: 8px;
      padding: 12px 14px; font-size: 0.8rem; color: var(--error); margin-bottom: 1rem;
    }
    .error-box ul { margin-left: 1.1rem; margin-top: 4px; }
    .success-box {
      background: #E8F5E9; border: 1px solid #C8E6C9; border-radius: 8px;
      padding: 12px 14px; font-size: 0.85rem; color: #2E7D32; margin-bottom: 1rem;
    }
    .submit-btn {
      width: 100%; background: var(--red); color: var(--white); border: none;
      padding: 13px; border-radius: 10px; font-size: 0.97rem; font-weight: 700;
      font-family: 'Poppins', sans-serif; cursor: pointer; margin-top: 0.4rem;
      letter-spacing: 0.02em; transition: background 0.2s, transform 0.1s;
    }
    .submit-btn:hover  { background: var(--red-hover); }
    .submit-btn:active { transform: scale(0.99); }
    .back-link {
      display: block; text-align: center; margin-top: 1.2rem;
      font-size: 0.82rem; color: var(--muted); text-decoration: none; font-weight: 500;
    }
    .back-link:hover { color: var(--navy); }
    @media (max-width: 480px) {
      .form-row { grid-template-columns: 1fr; }
      .card { padding: 2rem 1.2rem; }
    }
  </style>
</head>
<body>

  <div class="card">
    <div class="logo">
      <div class="logo-badge"><img src="images/favicon.png" alt="Trans-Nzoia ICT Hub logo"></div>
      <div class="logo-text">
        Trans-Nzoia Community ICT Hub
        <small>Admin Registration</small>
      </div>
    </div>

    <h1 class="heading">Create Admin Account</h1>
    <p class="sub">Register a new administrator. Passwords and security answers are hashed before being stored.</p>

    <?php if (!empty($errors)): ?>
      <div class="error-box">
        <strong>Please fix the following:</strong>
        <ul>
          <?php foreach ($errors as $err): ?>
            <li><?php echo htmlspecialchars($err); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="success-box">
        Admin account <strong><?php echo htmlspecialchars($username); ?></strong> was created successfully.
        <a href="page_admin-login.php" style="color:#2E7D32; text-decoration:underline;">Go to login</a>.
      </div>
    <?php else: ?>

    <form action="" method="POST" autocomplete="off">

      <fieldset>
        <legend>Account details</legend>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="username">Username *</label>
            <input class="form-input" type="text" id="username" name="username" required
                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
          </div>
          <div class="form-group">
            <label class="form-label" for="full_name">Full Name *</label>
            <input class="form-input" type="text" id="full_name" name="full_name" required
                   value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="email">Email *</label>
          <input class="form-input" type="email" id="email" name="email" required
                 value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="password">Password *</label>
            <input class="form-input" type="password" id="password" name="password" required>
            <div class="hint">At least 8 characters.</div>
          </div>
          <div class="form-group">
            <label class="form-label" for="confirm_password">Confirm Password *</label>
            <input class="form-input" type="password" id="confirm_password" name="confirm_password" required>
          </div>
        </div>
      </fieldset>

      <fieldset>
        <legend>Security questions (required, used for password recovery)</legend>
        <p class="hint" style="margin-bottom: 1rem;">These questions are fixed and cannot be edited. Just enter your answer below each one.</p>

        <?php foreach ($SECURITY_QUESTIONS as $num => $questionText): ?>
          <div class="question-block">
            <div class="question-text"><?php echo $num; ?>. <?php echo htmlspecialchars($questionText); ?></div>
            <div class="form-group">
              <label class="form-label" for="security_answer_<?php echo $num; ?>">Your Answer *</label>
              <input class="form-input" type="text" id="security_answer_<?php echo $num; ?>"
                     name="security_answer_<?php echo $num; ?>" required autocomplete="off">
            </div>
          </div>
        <?php endforeach; ?>
      </fieldset>

      <button class="submit-btn" type="submit">Create Admin Account</button>
    </form>

    <a href="page_admin-login.php" class="back-link">Back to Login</a>
    <?php endif; ?>

  </div>

</body>
</html>