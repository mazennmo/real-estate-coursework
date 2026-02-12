<?php
session_start();

// 1) Database connection
$dsn  = "mysql:host=localhost;dbname=realestate;charset=utf8mb4";
$user = "root";
$pass = "root";

$pdo = new PDO($dsn, $user, $pass, [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$error = ""; // variable to store login error message

// 2) Run only when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $email = $_POST['email'];
  $pwd   = $_POST['password'];

  // 3) Find user by email
  $sql = "SELECT user_id, firstname, lastname, email, password_hash
          FROM users
          WHERE email = ?
          LIMIT 1";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$email]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  // 4) If password matches, log in
  if ($row && password_verify($pwd, $row['password_hash'])) {
    $_SESSION['user_id']   = (int)$row['user_id'];
    $_SESSION['firstname'] = $row['firstname'];
    $_SESSION['lastname']  = $row['lastname'];
    $_SESSION['email']     = $row['email'];

    header("Location: homepage.php");
    exit;
  } else {
    // If login fails
    $error = "Invalid email or password.";
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />

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
  <form class="card" method="post">
    <h1>Sign In</h1>


 <?php if ($error): ?>
  <div class="error"><?= $error ?></div>
<?php endif; ?>


    <!-- Email -->
    <div class="group">
      <input class="input" type="email" name="email" placeholder="Email" required>
    </div>

    <!-- Password -->
    <div class="group">
      <input class="input" type="password" name="password" placeholder="Password" required>
    </div>

    <button class="btn" type="submit">Sign In</button>

  </form>
</main>
</body>
</html>