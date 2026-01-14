<?php
session_start();

/* -------------------------
   Database connection
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

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* -------------------------
   Get property id
------------------------- */
$propertyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($propertyId <= 0) die("Invalid property.");

/* -------------------------
   Fetch property (includes seller_id)
------------------------- */
$stmt = $pdo->prepare("
    SELECT
        property_id,
        property_type_name,
        title,
        description,
        price,
        location,
        city,
        postcode,
        date_listed,
        status,
        bedrooms,
        bathrooms,
        area_sqft,
        garden_sqft,
        garage,
        seller_id
    FROM properties
    WHERE property_id = :pid
    LIMIT 1
");
$stmt->execute([':pid' => $propertyId]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$p) die("Property not found.");

/* -------------------------
   Fetch seller name using seller_id
------------------------- */
$sellerName = "Seller";

if (!empty($p['seller_id'])) {
    $stmtSeller = $pdo->prepare("
        SELECT firstname, lastname
        FROM users
        WHERE user_id = :sid
        LIMIT 1
    ");
    $stmtSeller->execute([':sid' => (int)$p['seller_id']]);
    $seller = $stmtSeller->fetch(PDO::FETCH_ASSOC);

    if ($seller) {
        $sellerName = trim($seller['firstname'] . ' ' . $seller['lastname']);
    }
}

/* -------------------------
   Main image
------------------------- */
$stmtImg = $pdo->prepare("
    SELECT image_url
    FROM property_images
    WHERE property_id = :pid
    ORDER BY image_id ASC
    LIMIT 1
");
$stmtImg->execute([':pid' => $propertyId]);
$mainImg = $stmtImg->fetchColumn();

if (!$mainImg) $mainImg = "assets/placeholder.jpg";
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?php echo h($p['title']); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  body{margin:0;font-family:Arial,sans-serif;background:#f2f4f7;color:#0b1320}
  .wrap{max-width:900px;margin:0 auto;padding:16px}
  .top a{text-decoration:none;border:1px solid #ccc;border-radius:8px;padding:8px 10px;background:#fff;color:#0b1320}
  .card{background:#fff;border:1px solid #ddd;border-radius:12px;overflow:hidden;margin-top:12px}
  .hero img{width:100%;max-height:380px;object-fit:co;display:block}
  .content{padding:14px}
  .pill{display:inline-block;background:#e9eef8;border-radius:999px;padding:6px 10px;font-weight:700;font-size:12px;margin-right:6px}
  .price{font-size:24px;font-weight:800;margin:10px 0}
  .muted{color:#5f6b7a}
  .grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px}
  @media(max-width:700px){.grid{grid-template-columns:1fr}}
  .box{border:1px solid #eee;border-radius:10px;padding:10px}
  .actions{
  display:flex;
  gap:10px;
  justify-content:flex-end;
  padding:12px 14px;
  border-top:1px solid #eee;
}

.action-btn{
  text-decoration:none;
  border:1px solid #d7dbe3;
  border-radius:12px;
  padding:10px 14px;
  background:#fff;
  color:#0b1320;
  font-weight:700;
}

.action-btn:hover{
  background:#f6f8fa;
}

</style>
</head>
<body>
<div class="wrap">

  <div class="top">
    <a href="listings.php">Back to listings</a>
  </div>

  <div class="card">
    <div class="hero">
      <img src="<?php echo h($mainImg); ?>" alt="Property image">
    </div>

    <div class="content">
      <span class="pill"><?php echo h($p['property_type_name']); ?></span>
      <span class="pill"><?php echo h($p['status']); ?></span>

      <h1 style="margin:10px 0 6px;"><?php echo h($p['title']); ?></h1>

      <div class="muted">
        <?php echo h($p['location']); ?>, <?php echo h($p['city']); ?>, <?php echo h($p['postcode']); ?>
      </div>

      <div class="price">£<?php echo number_format((float)$p['price']); ?></div>

      <div class="muted">
        Listed by: <strong><?php echo h($sellerName); ?></strong>
      </div>

      <div class="muted">
        Email: <strong><?php
          // Fetch seller email
          $stmtEmail = $pdo->prepare("SELECT email FROM users WHERE user_id = :sid LIMIT 1");
          $stmtEmail->execute([':sid' => (int)$p['seller_id']]);
          $sellerEmail = $stmtEmail->fetchColumn();
          echo h($sellerEmail);
        ?></strong>
      </div>

      <div class="muted">
        Phone number: <strong><?php
          // Fetch seller phone number
          $stmtPhone = $pdo->prepare("SELECT phone FROM users WHERE user_id = :sid LIMIT 1");
          $stmtPhone->execute([':sid' => (int)$p['seller_id']]);
          $sellerPhone = $stmtPhone->fetchColumn();
          echo h($sellerPhone);
          ?></strong>
      </div>



      <?php if (!empty($p['date_listed'])): ?>
        <div class="muted">Date listed: <?php echo h(date('d/m/Y', strtotime($p['date_listed']))); ?></div>
      <?php endif; ?>

      <div class="grid">
        <div class="box"><strong>Bedrooms:</strong> <?php echo h($p['bedrooms']); ?></div>
        <div class="box"><strong>Bathrooms:</strong> <?php echo h($p['bathrooms']); ?></div>
        <div class="box"><strong>Area (sq ft):</strong> <?php echo h($p['area_sqft'] ?? '—'); ?></div>
        <div class="box"><strong>Garden (sq ft):</strong> <?php echo h($p['garden_sqft'] ?? '—'); ?></div>
        <div class="box"><strong>Garage spaces:</strong> <?php echo h($p['garage'] ?? '—'); ?></div>
      </div>

      <div class="box" style="margin-top:10px;">
        <strong>Description</strong>
        <p class="muted" style="white-space:pre-wrap;margin:6px 0 0;"><?php echo h($p['description']); ?></p>
      </div>

    </div>
  <div class="actions">
    <a class="action-btn" href="reviews.php?property_id=<?php echo (int)$propertyId; ?>">Review</a>
    <a class="action-btn" href="messages.php">Message</a>
  </div>


  </div>

</div>
</body>
</html>