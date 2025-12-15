<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

$buyerId = (int)$_SESSION['user_id']; 

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

$sql = "
    SELECT
        p.property_id,
        p.title,
        p.price,
        p.location,
        p.city,
        p.postcode,
        p.status
    FROM favourites f
    JOIN properties p ON p.property_id = f.property_id
    WHERE f.buyer_id = :bid
    ORDER BY p.property_id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':bid' => $buyerId]);
$favourites = $stmt->fetchAll(PDO::FETCH_ASSOC);

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>My Favourites</title>
<style>
  body { font-family: Arial, sans-serif; background:#f4f6f8; margin:0; padding:20px; }
  h1 { text-align:center; }
  .property { background:#fff; padding:15px; margin:15px auto; max-width:700px;
              border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
  .price { font-weight:bold; margin:8px 0; }
</style>
</head>
<body>

<h1>My Favourite Properties</h1>

<?php if (empty($favourites)): ?>
  <p style="text-align:center;">You have not favourited any properties yet.</p>
<?php else: ?>
  <?php foreach ($favourites as $p): ?>
    <div class="property">
      <h3><?= h($p['title']) ?></h3>
      <div class="price">£<?= number_format((float)$p['price']) ?></div>
      <p><?= h($p['location']) ?>, <?= h($p['city']) ?>, <?= h($p['postcode']) ?></p>
      <p>Status: <?= h($p['status']) ?></p>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
