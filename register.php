<?php
// register.php — matches install page fields exactly

$dsn  = "mysql:host=localhost;dbname=realestate;charset=utf8mb4";
$user = "root";
$pass = "";

$errors = [];
$done = false;

try {
  $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname  = trim($_POST['lastname'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $address   = trim($_POST['address'] ?? '');
    $pwd       = $_POST['password'] ?? '';
    $cpwd      = $_POST['confirm_password'] ?? '';

    // Validate (schema-safe)
    if ($firstname === '' || mb_strlen($firstname) > 30) $errors['firstname'] = "First name is required (≤30 chars).";
    if ($lastname  === '' || mb_strlen($lastname)  > 30) $errors['lastname']  = "Last name is required (≤30 chars).";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 30) $errors['email'] = "Valid email required (≤30 chars).";
    if ($phone !== '' && mb_strlen($phone) > 20) $errors['phone'] = "Phone must be ≤20 chars.";
    if ($address !== '' && mb_strlen($address) > 50) $errors['address'] = "Address must be ≤50 chars.";
    if (strlen($pwd) < 8 || !preg_match('/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',$pwd)) $errors['password'] = "Min 8 chars incl. upper, lower & number.";
    if ($pwd !== $cpwd) $errors['confirm_password'] = "Passwords do not match.";

    // Unique email check (users.email is UNIQUE)
    if (!$errors) {
      $q = $pdo->prepare("SELECT 1 FROM users WHERE email = ?");
      $q->execute([$email]);
      if ($q->fetch()) $errors['email'] = "This email is already registered.";
    }

    // Insert
    if (!$errors) {
      $hash = password_hash($pwd, PASSWORD_BCRYPT);
      $stmt = $pdo->prepare("
        INSERT INTO users (firstname, lastname, email, password_hash, phone, address)
        VALUES (:fn, :ln, :em, :phash, :ph, :ad)
      ");
      $stmt->execute([
        ':fn'    => $firstname,
        ':ln'    => $lastname,
        ':em'    => $email,
        ':phash' => $hash,
        ':ph'    => $phone !== '' ? $phone : null,
        ':ad'    => $address !== '' ? $address : null
      ]);
      $done = true;
    }
  }
} catch (PDOException $e) {
  $errors['db'] = "Database error: " . htmlspecialchars($e->getMessage());
}

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
  :root{--brand:#2196f3;--bg:#f5f7fb;--field:#d9ecff;--ink:#27313a;--hint:#6b7785;--err:#c62828;--r:14px}
  *{box-sizing:border-box} body{margin:0;font-family:system-ui,Segoe UI,Roboto,Arial;background:var(--bg);color:var(--ink)}
  header{background:var(--brand);color:#fff;padding:16px 20px;display:flex;justify-content:space-between;align-items:center}
  header a{color:#fff;text-decoration:none;border:2px solid #fff;border-radius:999px;padding:8px 12px;font-weight:600}
  .wrap{max-width:720px;margin:32px auto;padding:0 16px}
  .card{background:#fff;border-radius:20px;box-shadow:0 10px 30px rgba(0,0,0,.06);padding:28px}
  h1{text-align:center;margin:4px 0 18px}
  .row{display:grid;gap:12px;grid-template-columns:1fr 1fr}
  @media (max-width:640px){.row{grid-template-columns:1fr}}
  .group{margin:12px 0}
  .hint{font-size:12px;color:var(--hint);margin:0 0 6px 6px}
  .input{width:100%;background:var(--field);border:2px solid transparent;border-radius:var(--r);padding:16px 14px;font-size:16px}
  .input:focus{outline:none;border-color:var(--brand);background:#eef6ff}
  .error{color:var(--err);font-size:13px;margin-top:6px}
  .btn{width:100%;margin-top:16px;background:var(--brand);color:#fff;border:none;border-radius:var(--r);padding:14px;font-weight:700;cursor:pointer}
  .ok{background:#e8f5e9;border:1px solid #a5d6a7;color:#2e7d32;padding:10px 12px;border-radius:10px;margin-bottom:14px}
</style>
</head>
<body>
<header>
  <div><strong>Real Estate Platform</strong></div>
  <a href="homepage.php">Home</a>
</header>

<main class="wrap">
  <div class="card">
    <?php if ($done): ?>
      <div class="ok">Account created successfully. You can now <a href="signin.php">sign in</a>.</div>
    <?php endif; ?>
    <?php if (!empty($errors['db'])): ?>
      <div class="error"><?= $errors['db'] ?></div>
    <?php endif; ?>

    <h1>Register</h1>
    <form method="post" novalidate>
      <div class="row">
        <div class="group">
          <p class="hint">Separate fields for first and last names.</p>
          <input class="input" type="text" name="firstname" placeholder="First Name" value="<?= old('firstname') ?>" required>
          <?= err('firstname',$errors) ?>
        </div>
        <div class="group">
          <p class="hint">&nbsp;</p>
          <input class="input" type="text" name="lastname" placeholder="Last Name" value="<?= old('lastname') ?>" required>
          <?= err('lastname',$errors) ?>
        </div>
      </div>

      <div class="group">
        <p class="hint">Email must be valid and unique.</p>
        <input class="input" type="email" name="email" placeholder="Email" value="<?= old('email') ?>" required>
        <?= err('email',$errors) ?>
      </div>

      <div class="group">
        <p class="hint">Phone number used for contact (optional).</p>
        <input class="input" type="tel" name="phone" placeholder="Phone Number" value="<?= old('phone') ?>">
        <?= err('phone',$errors) ?>
      </div>

      <div class="group">
        <p class="hint">Address is optional but useful for location-based searches.</p>
        <input class="input" type="text" name="address" placeholder="Address (Optional)" value="<?= old('address') ?>">
        <?= err('address',$errors) ?>
      </div>

      <div class="row">
        <div class="group">
          <p class="hint">Passwords must meet complexity requirements.</p>
          <input class="input" type="password" name="password" placeholder="Password" required>
          <?= err('password',$errors) ?>
          <p class="hint">Min 8 chars, include upper, lower & a number.</p>
        </div>
        <div class="group">
          <p class="hint">&nbsp;</p>
          <input class="input" type="password" name="confirm_password" placeholder="Confirm Password" required>
          <?= err('confirm_password',$errors) ?>
        </div>
      </div>

      <button class="btn" type="submit">Create Account</button>
    </form>
  </div>
</main>
</body>
</html>
