<?php
// Connect to database
$pdo = new PDO("mysql:host=localhost;dbname=realestate;charset=utf8mb4", "root", "root");

// Get property ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get property from database
$stmt = $pdo->prepare("SELECT * FROM properties WHERE property_id = ?");
$stmt->execute([$id]);
$property = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    echo "Property not found.";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Property</title>
</head>
<body>

<h1><?php echo $property['title']; ?></h1>

<p>Price: £<?php echo $property['price']; ?></p>

<p>
    <?php echo $property['location']; ?>,
    <?php echo $property['city']; ?>,
    <?php echo $property['postcode']; ?>
</p>

<p><?php echo $property['description']; ?></p>

</body>
</html>