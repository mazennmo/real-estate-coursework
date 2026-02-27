<?php
session_start();

/* DB connection */
$pdo = new PDO(
    "mysql:host=localhost;dbname=realestate;charset=utf8mb4",
    "root",
    "root"
);

/* Get property ID */
$propertyId = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;

/* Get seller_id from property */
$stmt = $pdo->prepare("SELECT seller_id FROM properties WHERE property_id = ?");
$stmt->execute([$propertyId]);
$sellerId = $stmt->fetchColumn();

/* Get seller name */
$stmt = $pdo->prepare("SELECT firstname, lastname FROM users WHERE user_id = ?");
$stmt->execute([$sellerId]);
$seller = $stmt->fetch(PDO::FETCH_ASSOC);

$sellerName = $seller['firstname'] . " " . $seller['lastname'];

/* Store rating and comment */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $buyerId = (int)$_SESSION['user_id'];
    $rating  = (int)$_POST['rating'];
    $comment = $_POST['comment'];

    $stmt = $pdo->prepare("INSERT INTO reviews (buyer_id, seller_id, rating,comment) VALUES (?, ?, ?,?)");
    $stmt->execute([$buyerId, $sellerId, $rating,$comment]);

    echo "<p>Thank you for your review.</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
<style>
body{
    margin:0;
    font-family:Arial, sans-serif;
    background:#f4f6f8;
    padding:20px;
    color:#0b1320;
}

.card{
    max-width:700px;
    margin:0 auto;
    background:#ffffff;
    border-radius:14px;
    box-shadow:0 8px 24px rgba(0,0,0,0.08);
    padding:24px;
}

.back-btn{
    display:inline-block;
    margin-bottom:18px;
    padding:8px 14px;
    border:1px solid #cfd6dd;
    border-radius:10px;
    background:#ffffff;
    text-decoration:none;
    font-weight:bold;
    color:#0b1320;
}

.back-btn:hover{
    background:#f6f8fa;
}

h1{
    margin:0 0 8px 0;
}

.muted{
    color:#5f6b7a;
    margin-bottom:20px;
}

label{
    display:block;
    margin-bottom:8px;
    font-weight:bold;
}

.rating{
    display:flex;
    gap:12px;
    margin-bottom:20px;
}

.rating label{
    font-weight:normal;
}

textarea{
    width:100%;
    min-height:120px;
    border:1px solid #d9dee7;
    border-radius:10px;
    padding:12px;
    font-size:14px;
    resize:vertical;
}

.submit-btn{
    margin-top:16px;
    padding:12px 18px;
    background:#2196f3;
    color:white;
    border:none;
    border-radius:12px;
    font-weight:bold;
    cursor:pointer;
}

.submit-btn:hover{
    background:#1976d2;
}
</style>
</head>

<body>

<div class="card">

<a class="back-btn" href="property.php?property_id=<?php echo $propertyId; ?>">Back to property</a>

<h1>Review Seller</h1>

<p class="muted">You are reviewing: <strong><?php echo $sellerName; ?></strong></p>

<form method="post">

<label>Rating</label>

<div class="rating">
<label><input type="radio" name="rating" value="1" required> 1</label>
<label><input type="radio" name="rating" value="2" required> 2</label>
<label><input type="radio" name="rating" value="3" required> 3</label>
<label><input type="radio" name="rating" value="4" required> 4</label>
<label><input type="radio" name="rating" value="5" required> 5</label>
</div>

<label>Comment (optional)</label>
<textarea name="comment"></textarea>

<button type="submit" class="submit-btn">Submit Review</button>

</form>

</div>

</body>
</html>