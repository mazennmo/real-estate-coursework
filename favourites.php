<?php
session_start();

/* -----------------------
   Login check
----------------------- */
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

$buyerId = $_SESSION['user_id'];

/* -----------------------
   Database connection
----------------------- */
$host = "localhost";
$db   = "realestate";
$user = "root";
$pass = "root";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Database connection failed");
}

/* -----------------------
   Unfavourite 
----------------------- */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["property_id"])) {

    $propertyId = (int)$_POST["property_id"];

    $sql = "DELETE FROM favourites 
            WHERE buyer_id = :buyer_id 
            AND property_id = :property_id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":buyer_id" => $buyerId,
        ":property_id" => $propertyId
    ]);

    header("Location: favourites.php");
    exit;
}

/* -----------------------
   Get favourite properties
----------------------- */
$sql = "
    SELECT
        p.property_id,
        p.title,
        p.price,
        p.location,
        p.city,
        p.postcode,
        p.status,
        (
            SELECT image_url
            FROM property_images
            WHERE property_id = p.property_id
            LIMIT 1
        ) AS image_url
    FROM favourites f
    JOIN properties p ON p.property_id = f.property_id
    WHERE f.buyer_id = :buyer_id
";

$stmt = $pdo->prepare($sql);
$stmt->execute([":buyer_id" => $buyerId]);
$favourites = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Favourites</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f6f8;
    padding: 20px;
}

h1 {
    text-align: center;
}

.property {
    background: white;
    max-width: 900px;
    margin: 20px auto;
    padding: 15px;
    border-radius: 12px;
    display: flex;
    gap: 15px;
    position: relative;
}

.property img {
    width: 220px;
    height: 140px;
    object-fit: cover;
    border-radius: 10px;
    background: #eee;
}

.info {
    flex: 1;
    padding-right: 140px;
}

.price {
    font-weight: bold;
    font-size: 20px;
    margin: 10px 0;
}

.unfav-form {
    position: absolute;
    right: 20px;
    bottom: 20px;
}

.unfav-btn {
    background: transparent;
    border: 1px solid #cfd6dd;
    padding: 8px 16px;
    border-radius: 14px;
    cursor: pointer;
    font-weight: bold;
}

.unfav-btn:hover {
    background: #f6f8fa;
}
</style>
</head>

<body>

<h1>My Favourite Properties</h1>

<?php if (empty($favourites)): ?>
    <p style="text-align:center;">You have no favourite properties.</p>
<?php else: ?>

<?php foreach ($favourites as $p): ?>
<div class="property">

    <?php if ($p["image_url"]): ?>
        <img src="<?= $p["image_url"] ?>" alt="Property image">
    <?php else: ?>
        <img src="placeholder.jpg" alt="No image">
    <?php endif; ?>

    <div class="info">
        <h3><?= $p["title"] ?></h3>
        <p><?= $p["location"] ?>, <?= $p["city"] ?>, <?= $p["postcode"] ?></p>
        <div class="price">£<?= number_format($p["price"]) ?></div>
        <p>Status: <?= $p["status"] ?></p>
    </div>

    <form method="post" class="unfav-form">
        <input type="hidden" name="property_id" value="<?= $p["property_id"] ?>">
        <button type="submit" class="unfav-btn">Unfavourite</button>
    </form>

</div>
<?php endforeach; ?>

<?php endif; ?>

</body>
</html>  