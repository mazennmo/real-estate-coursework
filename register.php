<?php
// ==== 1) Database connection settings ====
$dsn  = "mysql:host=localhost;dbname=realestate;charset=utf8mb4";
$user = "root";
$pass = "root";

// Track errors and success state
$errors = [];
$done = false;

try {
  // ==== 2) Connect to MySQL with PDO ====
  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
  ]);

  // ==== 3) Run this only when form is submitted ====
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 3a) Get form values (trim spaces)
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname  = trim($_POST['lastname']  ?? '');
    $email     = trim($_POST['email']     ?? '');
    $phone     = trim($_POST['phone']     ?? '');
    $address   = trim($_POST['address']   ?? '');
    $pwd       = $_POST['password']        ?? '';
    $cpwd      = $_POST['confirm_password']?? '';

    // 3b) Validation
    if ($firstname === '') $errors['firstname'] = "Please enter your first name.";
    if ($lastname  === '') $errors['lastname']  = "Please enter your last name.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = "Please enter a valid email.";
    if ($pwd === '') $errors['password'] = "Please enter a password.";
    if ($pwd !== $cpwd) $errors['confirm_password'] = "Passwords do not match.";

    // 3c) Check if email already exists
    if (!$errors) {
      $check = $pdo->prepare("SELECT 1 FROM users WHERE email = ?");
      $check->execute([$email]);
      if ($check->fetch()) {
        $errors['email'] = "This email is already registered.";
      }
    }

    // 3d) Insert new user if no errors
    if (!$errors) {
      $hash = password_hash($pwd, PASSWORD_BCRYPT);

      $ins = $pdo->prepare("
        INSERT INTO users (firstname, lastname, email, password_hash, phone, address)
        VALUES (:fn, :ln, :em, :phash, :ph, :ad)
      ");
      $ins->execute([
        ':fn'    => $firstname,
        ':ln'    => $lastname,
        ':em'    => $email,
        ':phash' => $hash,
        ':ph'    => $phone !== '' ? $phone : null,
        ':ad'    => $address !== '' ? $address : null
      ]);

      $done = true;
      // Optional: clear posted values after success
      $_POST = [];
    }
  }
} catch (PDOException $e) {
  $errors['db'] = "Database error: " . htmlspecialchars($e->getMessage());
}

// ==== 4) Small helpers to re-fill fields and show errors ====
function old($k){ return htmlspecialchars($_POST[$k] ?? '', ENT_QUOTES); }
function err($k,$errors){ return isset($errors[$k]) ? '<div class="error">'.$errors[$k].'</div>' : ''; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Register</title>
<style>
  body{font-family:Arial, sans-serif; background:#f5f5f5; margin:0; color:#333;}
  header{background:#2196f3; color:#fff; padding:12px 16px; display:flex; justify-content:space-between; align-items:center;}
  header a{color:#fff; text-decoration:none; border:1px solid #fff; padding:6px 10px; border-radius:16px;}
  .wrap{max-width:700px; margin:24px auto; padding:0 12px;}
  .card{background:#fff; padding:20px; border-radius:12px; box-shadow:0 4px 16px rgba(0,0,0,0.08);}
  h1{margin:8px 0 16px; text-align:center;}
  .row{display:grid; grid-template-columns:1fr 1fr; gap:10px;}
  @media(max-width:640px){ .row{grid-template-columns:1fr;} }
  .group{margin:10px 0;}
  .input{width:95%; padding:12px; border:1px solid #ccc; border-radius:8px; font-size:15px;}
  .btn{width:100%; padding:12px; background:#2196f3; color:#fff; border:none; border-radius:8px; font-weight:bold; cursor:pointer; margin-top:10px;}
  .error{color:#c62828; font-size:13px; margin-top:6px;}
  .ok{background:#e8f5e9; border:1px solid #a5d6a7; color:#2e7d32; padding:10px; border-radius:8px; margin-bottom:12px;}
  .hint{font-size:12px; color:#666; margin:4px 0;}
</style>
</head>
<body>
<header>
  <strong>Real Estate Platform</strong>
  <a href="homepage.php">Home</a>
</header>

<main class="wrap">
  <div class="card">

    <?php if ($done): ?>
      <div class="ok">Account created. You can now <a href="signin.php">sign in</a>.</div>
    <?php endif; ?>

    <?php if (!empty($errors['db'])): ?>
      <div class="error"><?= $errors['db'] ?></div>
    <?php endif; ?>

    <h1>Register</h1>

    <form method="post" novalidate>
      <div class="row">
        <div class="group">
          <div class="hint">First name</div>
          <input class="input" type="text" name="firstname" value="<?= old('firstname') ?>" required>
          <?= err('firstname',$errors) ?>
        </div>
        <div class="group">
          <div class="hint">Last name</div>
          <input class="input" type="text" name="lastname" value="<?= old('lastname') ?>" required>
          <?= err('lastname',$errors) ?>
        </div>
      </div>

      <div class="group">
        <div class="hint">Email (must be unique)</div>
        <input class="input" type="email" name="email" value="<?= old('email') ?>" required>
        <?= err('email',$errors) ?>
      </div>

      <div class="group">
        <div class="hint">Phone (optional)</div>
        <input class="input" type="tel" name="phone" value="<?= old('phone') ?>">
        <?= err('phone',$errors) ?>
      </div>

      <div class="group">
        <div class="hint">Address (optional)</div>
        <input class="input" type="text" name="address" value="<?= old('address') ?>">
        <?= err('address',$errors) ?>
      </div>

      <div class="row">
        <div class="group">
          <div class="hint">Password</div>
          <input class="input" type="password" name="password" required>
          <?= err('password',$errors) ?>
        </div>
        <div class="group">
          <div class="hint">Confirm Password</div>
          <input class="input" type="password" name="confirm_password" required>
          <?= err('confirm_password',$errors) ?>
        </div>
      </div>

      <button class="btn" type="submit">Create Account</button>
    </form>
  </div>
</main>
</body>
</html>