<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

$buyerId = (int)$_SESSION['user_id'];

/* DB connection */
$host = 'localhost';
$db   = 'realestate';
$user = 'root';
$pass = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Database connection failed.");
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* Get seller_id (prefer property_id) */
$propertyId = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;
$sellerId = 0;

if ($propertyId > 0) {
    $stmt = $pdo->prepare("SELECT seller_id FROM properties WHERE property_id = :pid LIMIT 1");
    $stmt->execute([':pid' => $propertyId]);
    $sellerId = (int)$stmt->fetchColumn();
} else {
    $sellerId = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : 0;
}

if ($sellerId <= 0) die("Invalid seller.");


/* Seller name */
$stmt = $pdo->prepare("SELECT firstname, lastname FROM users WHERE user_id = :sid LIMIT 1");
$stmt->execute([':sid' => $sellerId]);
$seller = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$seller) die("Seller not found.");
$sellerName = trim($seller['firstname'] . ' ' . $seller['lastname']);

/* Existing review (if any) */
$stmt = $pdo->prepare("
    SELECT reviewID, rating, comment
    FROM reviews
    WHERE buyer_id = :bid AND seller_id = :sid
    LIMIT 1
");
$stmt->execute([':bid' => $buyerId, ':sid' => $sellerId]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

$ratingValue  = $existing ? (int)$existing['rating'] : 0;
$commentValue = $existing ? (string)($existing['comment'] ?? '') : "";

/* Submit */
$success = false;
$errorMsg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating  = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = trim($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $errorMsg = "Please choose a rating from 1 to 5.";
    } elseif (strlen($comment) > 1000) {
        $errorMsg = "Comment is too long (max 1000 characters).";
    } else {
        if ($existing) {
            $stmt = $pdo->prepare("
                UPDATE reviews
                SET rating = :rating, comment = :comment, timestamp = CURRENT_TIMESTAMP
                WHERE reviewID = :rid
            ");
            $stmt->execute([
                ':rating'  => $rating,
                ':comment' => ($comment === '' ? null : $comment),
                ':rid'     => (int)$existing['reviewID']
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO reviews (buyer_id, seller_id, rating, comment)
                VALUES (:bid, :sid, :rating, :comment)
            ");
            $stmt->execute([
                ':bid'     => $buyerId,
                ':sid'     => $sellerId,
                ':rating'  => $rating,
                ':comment' => ($comment === '' ? null : $comment)
            ]);
        }

        $success = true;
        $ratingValue  = $rating;
        $commentValue = $comment;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Review Seller</title>
<style>
  body{margin:0;font-family:Arial,sans-serif;background:#f4f6f8;color:#0b1320;padding:20px}
  .card{max-width:650px;margin:0 auto;background:#fff;border-radius:14px;box-shadow:0 6px 18px rgba(0,0,0,.08);padding:18px}
  .top a{text-decoration:none;border:1px solid #cfd6dd;border-radius:10px;padding:8px 10px;background:#fff;color:#0b1320;display:inline-block}
  h1{margin:12px 0 6px}
  .muted{color:#5f6b7a;margin:6px 0}
  label{display:block;font-weight:700;margin:14px 0 6px}
  textarea{width:96%;padding:12px;border:1px solid #d9dee7;border-radius:10px;font-size:15px;min-height:110px;resize:vertical}
  .btn{margin-top:14px;padding:12px 16px;border:none;border-radius:12px;background:#2196f3;color:#fff;font-weight:700;cursor:pointer}
  .ok{background:#e8f5e9;border:1px solid #a5d6a7;color:#2e7d32;padding:10px 12px;border-radius:10px;margin:12px 0}
  .err{background:#ffebee;border:1px solid #ef9a9a;color:#b71c1c;padding:10px 12px;border-radius:10px;margin:12px 0}

  /* 5-dot rating */
  .dots{display:flex;gap:10px;align-items:center;margin-top:6px}
  .dot-item{display:flex;flex-direction:column;align-items:center;gap:6px}
  .dots input{display:none}
  .dot{
    width:18px;height:18px;border-radius:50%;
    border:2px solid #9aa6b2;
    cursor:pointer;
  }
  /* filled when selected */
  .dots input:checked + .dot{
    background:#2196f3;
    border-color:#2196f3;
  }
  .dot-label{font-size:12px;color:#5f6b7a}
</style>
</head>
<body>

<div class="card">
  <div class="top">
    <a href="javascript:history.back()">Back</a>
  </div>

  <h1>Review Seller</h1>
  <p class="muted">You are reviewing: <strong><?php echo h($sellerName); ?></strong></p>

  <?php if ($success): ?>
    <div class="ok">Thank you! Your review has been saved.</div>
  <?php endif; ?>

  <?php if ($errorMsg !== ''): ?>
    <div class="err"><?php echo h($errorMsg); ?></div>
  <?php endif; ?>

  <form method="post">
    <label>Rating</label>

    <div class="dots">
      <?php for ($i=1; $i<=5; $i++): ?>
        <label class="dot-item">
          <input type="radio" name="rating" value="<?php echo $i; ?>" <?php echo ($ratingValue === $i ? 'checked' : ''); ?> required>
          <span class="dot"></span>
          <span class="dot-label"><?php echo $i; ?></span>
        </label>
      <?php endfor; ?>
    </div>

    <label for="comment">Comment (optional)</label>
    <textarea name="comment" id="comment" placeholder="Write your feedback here..."><?php echo h($commentValue); ?></textarea>

    <button class="btn" type="submit">Submit review</button>
  </form>
</div>

</body>
</html>