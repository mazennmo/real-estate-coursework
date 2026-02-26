<?php
session_start();

// User must be logged in to view favourites
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

$buyer_id = $_SESSION['user_id'];

// Database connection
$dsn  = "mysql:host=localhost;dbname=realestate;charset=utf8mb4";
$user = "root";
$pass = "root";

$pdo = new PDO($dsn, $user, $pass);

/* Unfavourite */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["property_id"])) {

    $propertyId = (int)$_POST["property_id"];

    $sqlDelete = "DELETE FROM favourites 
                  WHERE buyer_id = ? 
                  AND property_id = ?";

    $stmtDelete = $pdo->prepare($sqlDelete);
    $stmtDelete->execute([$buyer_id, $propertyId]);

    header("Location: favourites.php");
    exit;
}

// Get only favourited properties for this user
$sql = "
    SELECT *
    FROM properties
    WHERE property_id IN (
        SELECT property_id
        FROM favourites
        WHERE buyer_id = ?
    )
    ORDER BY property_id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$buyer_id]);
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<style>
:root{
    --bg:#f3f6fb;
    --card:#ffffff;
    --text:#0f172a;
    --muted:#64748b;
    --line:#e5e7eb;
    --blue:#2563eb;
    --blue2:#1d4ed8;
}

*{ box-sizing:border-box; }

body{
    margin:0;
    font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;
    background:var(--bg);
    color:var(--text);
}

.wrap{
    width:100%;
    padding:18px 28px 40px;
}

.topbar{
    margin-bottom:14px;
}

h1{
    margin:0;
    font-size:22px;
    font-weight:800;
}

hr{
    border:none;
    border-top:1px solid var(--line);
    margin:14px 0 18px;
}

/* Property card */
.property-box{
    background:var(--card);
    border:1px solid var(--line);
    border-radius:20px;
    padding:16px;
    margin-bottom:18px;
    box-shadow:0 8px 20px rgba(15, 23, 42, 0.06);
}

.row{
    display:flex;
    gap:18px;
    align-items:flex-start;
}

.row img{
    width:360px;
    height:210px;
    object-fit:cover;
    border-radius:16px;
    background:#e9eef7;
    display:block;
}

.details{
    flex:1;
    display:flex;
    flex-direction:column;
    gap:8px;
}

.details h3{
    margin:0;
    font-size:18px;
    font-weight:900;
}

.details h3 a{
    text-decoration:none;
    color:inherit;
}

.details h3 a:hover{
    text-decoration:underline;
}

.price{
    font-size:20px;
    font-weight:900;
    margin:4px 0;
}

.details-list{
    color:var(--muted);
    font-size:14px;
    display:flex;
    flex-direction:column;
    gap:4px;
}

.details-list strong{
    color:var(--text);
}

/* Unfavourite button */
form{
    margin-top:auto;
    display:flex;
    justify-content:flex-end;
}

button{
    background:#ffffff;
    border:1px solid var(--line);
    padding:8px 14px;
    border-radius:16px;
    font-weight:700;
    cursor:pointer;
}

button:hover{
    background:#f6f8fa;
}

/* Responsive */
@media (max-width:860px){
    .row{
        flex-direction:column;
    }

    .row img{
        width:100%;
        height:240px;
    }
}
</style>
</head>
<body>

<div class="wrap">

    <div class="topbar">
        <h1>My Favourites</h1>
    </div>

    <hr>

    <?php if (count($properties) == 0): ?>
        <p>No favourite properties found.</p>
    <?php endif; ?>

    <?php foreach ($properties as $p): ?>

        <?php
        // Get the first image for this property
        $sqlImg = "SELECT image_url FROM property_images WHERE property_id = ? LIMIT 1";
        $stmtImg = $pdo->prepare($sqlImg);
        $stmtImg->execute([$p['property_id']]);
        $image = $stmtImg->fetchColumn();
        ?>

        <div class="property-box">

            <div class="row">

                <?php if (!empty($p['main_image'])): ?>
                    <img src="<?php echo $p['main_image']; ?>" width="300">
                <?php else: ?>
                    <?php if ($image): ?>
                        <img src="<?php echo $image; ?>" width="300">
                    <?php else: ?>
                        <p>(No image)</p>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="details">

                <h3>
                  <a href="property.php?id=<?php echo $p['property_id']; ?>">
                    <?php echo $p['title']; ?>
                  </a>
                </h3>

                <p class="price">£<?php echo $p['price']; ?></p>

                    <div class="details-list">
                        <p><strong>Status:</strong> <?php echo $p['status']; ?></p>
                        <p><strong>Property Type:</strong> <?php echo $p['property_type_name']; ?></p>
                        <p><strong>Bedrooms:</strong> <?php echo $p['bedrooms']; ?></p>
                        <p><strong>Bathrooms:</strong> <?php echo $p['bathrooms']; ?></p>
                        <p><strong>Location:</strong> <?php echo $p['location']; ?>, <?php echo $p['city']; ?>, <?php echo $p['postcode']; ?></p>
                    </div>

                    <!-- Unfavourite button -->
                    <form method="post">
                        <input type="hidden" name="property_id" value="<?php echo $p['property_id']; ?>">
                        <button type="submit">Unfavourite</button>
                    </form>

                </div>

            </div>

        </div>

    <?php endforeach; ?>

</div>

</body>
</html>