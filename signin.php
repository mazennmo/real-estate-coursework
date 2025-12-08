<?php
// ==== 1) Start session so we can remember the logged-in user ====
session_start();

// ==== 2) Database connection settings ====
$dsn  = "mysql:host=localhost;dbname=realestate;charset=utf8mb4";
$user = "root";
$pass = "root";

// Keep track of errors
$errors = [];

try {
  // ==== 3) Connect to the database (PDO) ====
  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
  ]);

  // ==== 4) Only run login logic after the form is submitted ====
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 4a) Read what the user typed
    $email = trim($_POST['email'] ?? '');
    $pwd   = $_POST['password'] ?? '';

    // 4b) Very simple checks
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errors['email'] = "Please enter a valid email.";
    }
    if ($pwd === '') {
      $errors['password'] = "Please enter your password.";
    }

    // 4c) If no errors so far, try to find the user in the database
    if (!$errors) {
      $sql = "SELECT user_id, firstname, lastname, email, password_hash
              FROM users
              WHERE email = ?
              LIMIT 1";
      $stmt = $pdo->prepare($sql);
      $stmt->execute([$email]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      // 4d) If user exists and password is correct, log them in
      if ($row && password_verify($pwd, $row['password_hash'])) {
        $_SESSION['user_id']   = (int)$row['user_id'];
        $_SESSION['firstname'] = $row['firstname'];
        $_SESSION['lastname']  = $row['lastname'];
        $_SESSION['email']     = $row['email'];

        // Go to homepage after login
        header("Location: homepage.php");
        exit;
      } else {
        // Show a generic error for wrong email/password
        $errors['auth'] = "Invalid email or password.";
      }
    }
  }

} catch (PDOException $e) {
  // Database error (e.g., server down, wrong credentials)
  $errors['db'] = "Database error: " . htmlspecialchars($e->getMessage());
}

// ==== 5) Small helper functions ====
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
  body{font-family:Arial, sans-serif; margin:0; background:#f5f5f5; color:#333;}
  header{background:#2196f3; color:#fff; padding:12px 16px; display:flex; justify-content:space-between; align-items:center;}
  header a{color:#fff; text-decoration:none; border:1px solid #fff; padding:6px 10px; border-radius:16px;}
  .wrap{max-width:520px; margin:28px auto; padding:0 12px;}
  .card{background:#fff; padding:20px; border-radius:12px; box-shadow:0 4px 16px rgba(0,0,0,0.08);}
  h1{text-align:center; margin:8px 0 16px;}
  .group{margin:12px 0;}
  .input{width:98%; padding:12px; border:1px solid #ccc; border-radius:8px; font-size:15px;}
  .btn{display:block; width:180px; margin:18px auto 0; padding:12px; background:#2196f3; color:#fff; border:none; border-radius:8px; font-weight:bold; cursor:pointer;}
  .error{color:#c62828; font-size:13px; margin-top:6px;}
  .hint{font-size:12px; color:#666; margin:4px 0;}
  .center{text-align:center;}
</style>
</head>
<body>
<header>
  <strong>Real Estate Platform</strong>
  <a href="homepage.php">Home</a>
</header>

<main class="wrap">
  <form class="card" method="post" novalidate>
    <h1>Sign In</h1>

    <!-- Show database errors -->
    <?php if (!empty($errors['db'])): ?>
      <div class="error"><?= $errors['db'] ?></div>
    <?php endif; ?>

    <!-- Show wrong-credentials message -->
    <?php if (!empty($errors['auth'])): ?>
      <div class="error"><?= $errors['auth'] ?></div>
    <?php endif; ?>

    <!-- Email -->
    <div class="group">
      <input class="input" type="email" name="email" placeholder="Email" value="<?= old('email') ?>" autofocus required>
      
      <?= err('email',$errors) ?>
    </div>

    <!-- Password -->
    <div class="group">
      <input class="input" type="password" name="password" placeholder="Password" required>
      
      <?= err('password',$errors) ?>
    </div>

    <button class="btn" type="submit">Sign In</button>

    <p class="center" style="margin-top:12px; color:#666;">
      Don’t have an account?
      <a href="register.php" style="color:#2196f3; text-decoration:none; font-weight:bold;">Register</a>
    </p>
  </form>
</main>
</body>
</html>