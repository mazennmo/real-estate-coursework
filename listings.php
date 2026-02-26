<?php
session_start();

// Database connection
$dsn  = "mysql:host=localhost;dbname=realestate;charset=utf8mb4";
$user = "root";
$pass = "root";

$pdo = new PDO($dsn, $user, $pass);

// Add to favourites (runs when favourite button is clicked)
if (isset($_POST['favourite_property_id'])) {

    // User must be logged in to favourite
    if (!isset($_SESSION['user_id'])) {
        header("Location: signin.php");
        exit;
    }

    $buyer_id    = $_SESSION['user_id'];
    $property_id = (int) $_POST['favourite_property_id'];

    $sqlFav = "INSERT IGNORE INTO favourites (buyer_id, property_id) VALUES (?, ?)";
    $stmtFav = $pdo->prepare($sqlFav);
    $stmtFav->execute([$buyer_id, $property_id]);

}

// Search value (from the form)
$search = "";
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}

// Sort value (from the form)
$sort = "";
if (isset($_GET['sort'])) {
    $sort = $_GET['sort'];
}

// Min / Max price (from the form)
$min_price = "";
if (isset($_GET['min_price'])) {
    $min_price = $_GET['min_price'];
}

$max_price = "";
if (isset($_GET['max_price'])) {
    $max_price = $_GET['max_price'];
}

$sql = "SELECT * FROM properties";
$where = [];
$params = [];

// Search filter
if ($search != "") {
    $where[] = "(location LIKE ? OR city LIKE ? OR postcode LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Min price filter
if ($min_price != "") {
    $where[] = "price >= ?";
    $params[] = $min_price;
}

// Max price filter
if ($max_price != "") {
    $where[] = "price <= ?";
    $params[] = $max_price;
}

// Combine WHERE conditions
if (count($where) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

// Sorting
if ($sort == "high") {
    $sql .= " ORDER BY price DESC";
} elseif ($sort == "low") {
    $sql .= " ORDER BY price ASC";
} else {
    $sql .= " ORDER BY property_id DESC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
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
            max-width:none;
            margin:0;
            padding:18px 28px 40px;
        }

        .topbar{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:12px;
            margin-bottom:14px;
        }

        h1{
            margin:0;
            font-size:22px;
            font-weight:800;
        }

        .auth-links{
            display:flex;
            gap:10px;
            align-items:center;
        }

        .auth-links a{
            color:var(--blue);
            text-decoration:none;
            font-weight:700;
            padding:10px 14px;
            border-radius:999px;
            background:#ffffff;
            border:1px solid var(--line);
        }
        .auth-links a:hover{
            background:#eaf1ff;
            border-color:#cfe0ff;
        }

        form.search-form{
            display:flex;
            gap:10px;
            align-items:center;
            flex-wrap:wrap;
            margin-bottom:14px;
        }

        .search-bar{
            width:360px;
            font-size:14px;
            padding:12px 14px;
            border:1px solid var(--line);
            border-radius:999px;
            outline:none;
            background:#fff;
        }

        input[type="number"], select{
            padding:12px 12px;
            border:1px solid var(--line);
            border-radius:999px;
            outline:none;
            background:#fff;
            font-size:14px;
            min-width:150px;
        }

        button{
            padding:12px 16px;
            border:none;
            border-radius:999px;
            background:var(--blue);
            color:#fff;
            font-weight:800;
            cursor:pointer;
        }
        button:hover{ background:var(--blue2); }

        hr{
            border:none;
            border-top:1px solid var(--line);
            margin:14px 0 18px;
        }

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
            align-items:stretch;
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
            gap:10px;
        }

        .price{
            font-size:22px;
            font-weight:900;
            margin:0;
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
        .details h3 a:hover{ text-decoration:underline; }

        .kv{
            display:flex;
            flex-direction:column;
            gap:6px;
            color:var(--muted);
            font-size:14px;
        }
        .kv p{
            margin:0;
        }
        .kv strong{
            color:var(--text);
        }

        .details form.fav-form{
            margin-top:auto;
            display:flex;
            justify-content:flex-end;
        }

        .details form.fav-form button{
            background:#ffffff;
            color:var(--text);
            border:1px solid var(--line);
            padding:10px 14px;
            font-weight:800;
        }
        .details form.fav-form button:hover{
            background:#f8fafc;
        }

        @media (max-width:860px){
            .wrap{ padding:16px; }
            .row{ flex-direction:column; }
            .row img{ width:100%; height:240px; }
            .search-bar{ width:100%; }
            input[type="number"], select{ flex:1; min-width:150px; }
            .topbar{ flex-direction:column; align-items:flex-start; }
            .auth-links{ width:100%; justify-content:flex-end; }
        }
    </style>
</head>
<body>

<div class="wrap">

    <div class="topbar">
        <h1>Browse Listings</h1>
        <div class="auth-links">
            <a href="register.php">Register</a>
            <a href="signin.php">Sign In</a>
        </div>
    </div>

    <form method="get" action="listings.php" class="search-form">
        <input type="text" name="search" placeholder="Search" value="<?php echo $search; ?>" class="search-bar">

        <input type="number" name="min_price" placeholder="Min £" value="<?php echo $min_price; ?>">
        <input type="number" name="max_price" placeholder="Max £" value="<?php echo $max_price; ?>">

        <select name="sort">
            <option value="">Sort by</option>
            <option value="high" <?php if($sort=="high") echo "selected"; ?>>Highest Price</option>
            <option value="low" <?php if($sort=="low") echo "selected"; ?>>Lowest Price</option>
        </select>

        <button type="submit">Search</button>
    </form>

    <hr>

    <?php if (count($properties) == 0): ?>
        <p>No properties found.</p>
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

                    <!-- Favourite button -->
                    <form method="post" action="listings.php" class="fav-form">
                        <input type="hidden" name="favourite_property_id" value="<?php echo $p['property_id']; ?>">
                        <button type="submit">Favourite</button>
                    </form>
                </div>

            </div>

        </div>

    <?php endforeach; ?>

</div>

</body>
</html>