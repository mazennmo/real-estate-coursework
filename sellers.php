<?php
session_start();

/* DB connection */
$host = 'localhost';
$db   = 'realestate';
$user = 'root';
$pass = 'root';

$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);

/* Fetch sellers who have at least one property */
$sql = "
    SELECT DISTINCT u.user_id, u.firstname, u.lastname, u.email
    FROM users u
    JOIN properties p ON p.seller_id = u.user_id
    ORDER BY u.lastname, u.firstname
";

$stmt = $pdo->query($sql);
$sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* -------------------------
   ratings breakdown per seller 
------------------------- */
$ratingsBySeller = [];

if (!empty($sellers)) {
    $ids = array_map(fn($s) => (int)$s['user_id'], $sellers);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $sqlRatings = "
        SELECT
            seller_id,
            COUNT(*) AS total_reviews,
            ROUND(AVG(rating), 1) AS avg_rating,
            SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) AS count_5,
            SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) AS count_4,
            SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) AS count_3,
            SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) AS count_2,
            SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) AS count_1
        FROM reviews
        WHERE seller_id IN ($placeholders)
        GROUP BY seller_id
    ";

    $stmtR = $pdo->prepare($sqlRatings);
    $stmtR->execute($ids);

    foreach ($stmtR->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $ratingsBySeller[(int)$r['seller_id']] = $r;
    }
}

/* -------------------------
   prepare ONE statement to fetch comments for a seller
------------------------- */
$stmtComments = $pdo->prepare("
    SELECT rating, comment, timestamp
    FROM reviews
    WHERE seller_id = ?
      AND comment IS NOT NULL
      AND comment <> ''
    ORDER BY timestamp DESC
");
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body{
      margin:0;
      font-family: Arial, sans-serif;
      background:#f4f6f8;
      color:#0b1320;
      padding:20px;
    }
    .wrap{
      max-width:1000px;
      margin:0 auto;
    }
    .top{
      max-width:1000px;
      margin:0 auto 14px;
    }
    .top a{
      display:inline-block;
      text-decoration:none;
      border:1px solid #cfd6dd;
      border-radius:12px;
      padding:8px 12px;
      background:#fff;
      color:#0b1320;
      font-size:14px;
    }
    h1{
      margin:10px 0 14px;
      font-size:42px;
      font-weight:800;
    }

    /* FIX: use a DIV card instead of styling <p> */
    .card{
      background:#fff;
      border-radius:16px;
      box-shadow:0 6px 18px rgba(0,0,0,.08);
      padding:16px;
      margin:0 0 14px 0;

      display:flex;
      justify-content:space-between;
      gap:40px;
      line-height:1.6;
    }

    .left{ flex:1; }
    .right{
      flex:1;
      border-left:1px solid #e5e7eb;
      padding-left:20px;
    }

    /* Mobile */
    @media (max-width:800px){
      .card{ flex-direction:column; }
      .right{
        border-left:none;
        padding-left:0;
        border-top:1px solid #e5e7eb;
        padding-top:12px;
      }
    }

    hr{ display:none; }
    strong{ font-weight:800; }
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
              $r = $ratingsBySeller[$sid] ?? null;

              /* Fetch ALL comments for this seller */
              $stmtComments->execute([$sid]);
              $comments = $stmtComments->fetchAll(PDO::FETCH_ASSOC);
          ?>

          <div class="card">
            <div class="left">
              <?php echo $s['firstname'] . " " . $s['lastname']; ?>
              - <?php echo $s['email']; ?>
              <br>

              <?php
                  if ($r) {
                      echo "<br>Total reviews: " . $r['total_reviews'];
                      echo "<br>Average rating: " . $r['avg_rating'] . " / 5";
                      echo "<br>Breakdown: ";
                      echo "5* " . $r['count_5'] . "|";
                      echo "4* " . $r['count_4'] . "|";
                      echo "3* " . $r['count_3'] . "|";
                      echo "2* " . $r['count_2'] . "|";
                      echo "1* " . $r['count_1'];
                  } else {
                      echo "<br>Total reviews: 0";
                      echo "<br>Average rating: No ratings yet";
                      echo "<br>Breakdown: 5* 0|4* 0|3* 0|2* 0|1* 0";
                  }
              ?>
            </div>

            <div class="right">
              <?php
                  echo "<strong>Comments:</strong>";

                  if (empty($comments)) {
                      echo "<br>No comments yet.";
                  } else {
                      foreach ($comments as $c) {
                          echo "<br>- (" . (int)$c['rating'] . "*) " . $c['comment'];
                      }
                  }
              ?>
            </div>
          </div>

          <hr>
      <?php endforeach; ?>
  <?php endif; ?>
</div>

</body>
</html>