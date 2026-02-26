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

// Get the first image for this property
$stmtImg = $pdo->prepare("SELECT image_url FROM property_images WHERE property_id = ? LIMIT 1");
$stmtImg->execute([$id]);
$image = $stmtImg->fetchColumn();

// Get seller details
$stmtSeller = $pdo->prepare("SELECT email, phone FROM users WHERE user_id = ?");
$stmtSeller->execute([$property['seller_id']]);
$seller = $stmtSeller->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html>
<head>
<style>
:root{
    --bg:#eef3fb;
    --card:#ffffff;
    --text:#0f172a;
    --muted:#64748b;
    --line:#dbe3f2;
    --brand:#2f6fed;
    --brand2:#245bd1;
}

*{
    box-sizing:border-box;
}

body{
    margin:0;
    font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;
    background:var(--bg);
    color:var(--text);
    padding:24px 16px;
}

/* main container */
.wrap{
    max-width:900px;
    margin:0 auto;
    background:var(--card);
    border:1px solid var(--line);
    border-radius:12px;
    box-shadow:0 15px 35px rgba(0,0,0,0.08);
    overflow:hidden;
}

.content{
    padding:20px;
}

/* back link */
.back{
    display:inline-block;
    margin-bottom:14px;
    text-decoration:none;
    font-weight:700;
    color:var(--brand);
}
.back:hover{
    text-decoration:underline;
}

/* image container */
.imgbox{
    width:100%;
    border:1px solid var(--line);
    border-radius:12px;
    background:#f7f9ff;
    padding:10px;
}

.imgbox img{
    width:100%;
    height:auto;
    display:block;
    border-radius:8px;
}

/* status/type tags */
.pills{
    display:flex;
    gap:10px;
    margin:16px 0 6px;
}

.pill{
    background:#e9eef8;
    border:1px solid var(--line);
    padding:6px 12px;
    border-radius:8px;
    font-size:13px;
    font-weight:700;
}

/* title */
h1{
    margin:10px 0 6px;
    text-align:center;
    font-size:26px;
}

.address{
    text-align:center;
    color:var(--muted);
    margin-bottom:18px;
}

/* details grid */
.grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:12px;
}

.field{
    background:#f9fbff;
    border:1px solid var(--line);
    border-radius:10px;
    padding:12px;
    font-size:14px;
}

.field strong{
    display:inline-block;
    min-width:130px;
    color:#334155;
}

.wide{
    grid-column:1 / -1;
}

/* review button */
.actions{
    display:flex;
    justify-content:flex-end;
    margin-top:18px;
}

.actions a{
    background:var(--brand);
    color:#fff;
    text-decoration:none;
    padding:10px 20px;
    border-radius:8px;
    font-weight:800;
}

.actions a:hover{
    background:var(--brand2);
}

/* responsive */
@media (max-width:700px){
    .grid{
        grid-template-columns:1fr;
    }

    .imgbox{
        height:260px;
    }
}
</style>
</head>

<body>

<div class="wrap">

    <div class="content">

        <a class="back" href="listings.php">Back to listings</a>

        <div class="imgbox">
            <?php if (!empty($property['main_image'])): ?>
                <img src="<?php echo $property['main_image']; ?>" width="400">
            <?php else: ?>
                <?php if ($image): ?>
                    <img src="<?php echo $image; ?>" width="400">
                <?php else: ?>
                    <p>(No image)</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="pills">
            <span class="pill">Status: <?php echo $property['status']; ?></span>
            <span class="pill">Type: <?php echo $property['property_type_name']; ?></span>
        </div>

        <h1><?php echo $property['title']; ?></h1>

        <p class="address">
            <?php echo $property['location']; ?>,
            <?php echo $property['city']; ?>,
            <?php echo $property['postcode']; ?>
        </p>

        <div class="grid">
            <div class="field wide"><strong>Price:</strong> £<?php echo $property['price']; ?></div>

            <div class="field"><strong>Bedrooms:</strong> <?php echo $property['bedrooms']; ?></div>
            <div class="field"><strong>Bathrooms:</strong> <?php echo $property['bathrooms']; ?></div>

            <div class="field"><strong>Area (sq ft):</strong> <?php echo $property['area_sqft']; ?></div>
            <div class="field"><strong>Garden (sq ft):</strong> <?php echo $property['garden_sqft']; ?></div>

            <div class="field"><strong>Garage spaces:</strong> <?php echo $property['garage']; ?></div>
            <div class="field"><strong>Email:</strong> <?php echo $seller['email']; ?></div>

            <div class="field"><strong>Phone:</strong> <?php echo $seller['phone']; ?></div>
            <div class="field wide"><strong>Description:</strong> <?php echo $property['description']; ?></div>
        </div>

        <div class="actions">
            <a href="reviews.php?property_id=<?php echo $id; ?>">Review</a>
        </div>

    </div>

</div>

</body>
</html>