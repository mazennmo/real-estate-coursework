<?php
session_start();

/* -------------------------
   DB connection
------------------------- */
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

/* -------------------------
   Sellers = users who have listings
   + rating breakdown
   FIX: prevent duplicated reviews caused by JOIN properties
------------------------- */
$sql = "
SELECT
    users.user_id,
    users.firstname,
    users.lastname,

    COUNT(DISTINCT reviews.reviewID) AS total_reviews,
    ROUND(AVG(DISTINCT reviews.rating), 1) AS avg_rating,

    COUNT(DISTINCT CASE WHEN reviews.rating = 5 THEN reviews.reviewID END) AS count_5,
    COUNT(DISTINCT CASE WHEN reviews.rating = 4 THEN reviews.reviewID END) AS count_4,
    COUNT(DISTINCT CASE WHEN reviews.rating = 3 THEN reviews.reviewID END) AS count_3,
    COUNT(DISTINCT CASE WHEN reviews.rating = 2 THEN reviews.reviewID END) AS count_2,
    COUNT(DISTINCT CASE WHEN reviews.rating = 1 THEN reviews.reviewID END) AS count_1

FROM users
JOIN properties ON properties.seller_id = users.user_id
LEFT JOIN reviews ON reviews.seller_id = users.user_id

GROUP BY users.user_id, users.firstname, users.lastname
ORDER BY avg_rating DESC, total_reviews DESC
";

$stmt = $pdo->query($sql);
$sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* -------------------------
   Latest comments (max 3 per seller)
------------------------- */
$stmtC = $pdo->query("
    SELECT seller_id, rating, comment, timestamp
    FROM reviews
    WHERE comment IS NOT NULL AND comment <> ''
    ORDER BY timestamp DESC
");
$rows = $stmtC->fetchAll(PDO::FETCH_ASSOC);

$commentsBySeller = [];
foreach ($rows as $r) {
    $sid = (int)$r['seller_id'];
    if (!isset($commentsBySeller[$sid])) {
        $commentsBySeller[$sid] = [];
    }
    if (count($commentsBySeller[$sid]) < 3) {
        $commentsBySeller[$sid][] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Trusted Sellers</title>
<style>
  body{margin:0;font-family:Arial,sans-serif;background:#f4f6f8;color:#0b1320;padding:20px}
  .top{max-width:1000px;margin:0 auto 14px;display:flex;justify-content:space-between}
  .top a{text-decoration:none;border:1px solid #cfd6dd;border-radius:12px;padding:10px 14px;background:#fff;color:#0b1320}
  .wrap{max-width:1000px;margin:0 auto}
  h1{margin:10px 0 14px;font-size:42px}
  .card{background:#fff;border-radius:16px;box-shadow:0 6px 18px rgba(0,0,0,.08);padding:16px;margin-bottom:14px}
  .name{font-size:20px;font-weight:800}
  .muted{color:#5f6b7a;margin:6px 0}
  .grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px}
  @media(max-width:800px){.grid{grid-template-columns:1fr}}
  .box{border:1px solid #eef1f5;border-radius:14px;padding:12px}
  .pill{border:1px solid #d9dee7;border-radius:999px;padding:8px 12px;background:#f9fbff;font-size:14px}
  .counts{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
  .comment{border-top:1px solid #eef1f5;padding-top:8px;margin-top:8px}
  small{color:#5f6b7a}
</style>
</head>
<body>

<div class="top">
  <a href="homepage.php">Back to homepage</a>
</div>

<div class="wrap">
  <h1>Trusted Sellers</h1>

  <?php if (empty($sellers)): ?>
    <div class="card">No sellers found.</div>
  <?php else: ?>

    <?php foreach ($sellers as $s): ?>
      <?php
        $sid = (int)$s['user_id'];
        $name = trim($s['firstname'] . ' ' . $s['lastname']);
        if ($name === '') $name = 'Seller';
      ?>

      <div class="card">
        <div class="name"><?php echo h($name); ?></div>

        <div class="muted">
          Average rating:
          <?php echo $s['avg_rating'] !== null ? h($s['avg_rating']) . ' / 5' : 'No ratings yet'; ?>
          • <?php echo (int)$s['total_reviews']; ?> reviews
        </div>

        <div class="grid">
          <div class="box">
            <strong>Rating breakdown</strong>
            <div class="counts">
              <span class="pill">5★ <?php echo (int)$s['count_5']; ?></span>
              <span class="pill">4★ <?php echo (int)$s['count_4']; ?></span>
              <span class="pill">3★ <?php echo (int)$s['count_3']; ?></span>
              <span class="pill">2★ <?php echo (int)$s['count_2']; ?></span>
              <span class="pill">1★ <?php echo (int)$s['count_1']; ?></span>
            </div>
          </div>

          <div class="box">
            <strong>Recent comments</strong>

            <?php if (empty($commentsBySeller[$sid])): ?>
              <div class="muted">No comments yet.</div>
            <?php else: ?>
              <?php foreach ($commentsBySeller[$sid] as $c): ?>
                <div class="comment">
                  <strong><?php echo (int)$c['rating']; ?>★</strong>
                  <?php echo h($c['comment']); ?><br>
                  <small><?php echo h(date('d/m/Y', strtotime($c['timestamp']))); ?></small>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

    <?php endforeach; ?>
  <?php endif; ?>
</div>

</body>
</html>
