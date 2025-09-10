<?php
// signin.php
session_start();

$dsn  = "mysql:host=localhost;dbname=realestate;charset=utf8mb4";
$user = "root";
$pass = "";

$errors = [];

try {
  $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pwd   = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 30) {
      $errors['email'] = "Enter a valid email (≤30 chars).";
    }
    if ($pwd === '') {
      $errors['password'] = "Password is required.";
    }

    if (!$errors) {
      $stmt = $pdo->prepare("SELECT user_id, firstname, lastname, email, password_hash FROM users WHERE email = ? LIMIT 1");
      $stmt->execute([$email]);
      $userRow = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($userRow && password_verify($pwd, $userRow['password_hash'])) {
        // Success -> set session and go home (adjust destination if needed)
        $_SESSION['user_id']   = (int)$userRow['user_id'];
        $_SESSION['firstname'] = $userRow['firstname'];
        $_SESSION['lastname']  = $userRow['lastname'];
        $_SESSION['email']     = $userRow['email'];

        header("Location: index.html"); // or home.php
        exit;
      } else {
        $errors['auth'] = "Invalid email or password.";
      }
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
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Sign In • Real Estate Platform</title>
<style>
  :root{--brand:#2196f3;--bg:#f5f7fb;--field:#d9ecff;--ink:#27313a;--hint:#6b7785;--err:#c62828;--r:14px}
  *{box-sizing:border-box}
  body{margin:0;font-family:system-ui,Segoe UI,Roboto,Arial;background:var(--bg);color:var(--ink)}
  header{background:var(--brand);color:#fff;padding:16px 20px;display:flex;justify-content:space-between;align-items:center}
  header a{color:#fff;text-decoration:none;border:2px solid #fff;border-radius:999px;padding:8px 12px;font-weight:600}
  .wrap{max-width:540px;margin:40px auto;padding:0 16px}
  .card{background:#fff;border-radius:20px;box-shadow:0 10px 30px rgba(0,0,0,.06);padding:28px}
  h1{text-align:center;margin:4px 0 18px}
  .group{margin:14px 0}
  .input{width:100%;background:var(--field);border:2px solid transparent;border-radius:var(--r);padding:16px 14px;font-size:16px}
  .input:focus{outline:none;border-color:var(--brand);background:#eef6ff}
  .hint{font-size:12px;color:var(--hint);margin:6px 0 0 6px;font-style:italic}
  .error{color:var(--err);font-size:13px;margin-top:6px}
  .btn{display:block;width:180px;margin:22px auto 0;background:var(--brand);color:#fff;border:none;border-radius:var(--r);padding:14px;font-weight:700;cursor:pointer}
  .center{ text-align:center; }
</style>
</head>
<body>
<header>
  <div><strong>Real Estate Platform</strong></div>
  <a href="index.html">Home</a>
</header>

<main class="wrap">
  <form class="card" method="post" novalidate>
    <h1>Sign In</h1>

    <?php if (!empty($errors['db'])): ?>
      <div class="error"><?= $errors['db'] ?></div>
    <?php endif; ?>
    <?php if (!empty($errors['auth'])): ?>
      <div class="error"><?= $errors['auth'] ?></div>
    <?php endif; ?>

    <div class="group">
      <input class="input" type="email" name="email" placeholder="Email" value="<?= old('email') ?>" autofocus required>
      <p class="hint">Use the same email used during registration.</p>
      <?= err('email',$errors) ?>
    </div>

    <div class="group">
      <input class="input" type="password" name="password" placeholder="Password" required>
      <p class="hint">Password must match the one on file for authentication.</p>
      <?= err('password',$errors) ?>
    </div>

    <button class="btn" type="submit">Submit</button>
    <p class="center" style="margin-top:14px;color:#6b7785;">Don’t have an account? <a href="register.php" style="color:#2196f3;text-decoration:none;font-weight:600;">Register</a></p>
  </form>
</main>
</body>
</html>