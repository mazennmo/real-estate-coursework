<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

$dsn  = "mysql:host=localhost;dbname=realestate;charset=utf8mb4";
$user = "root";
$pass = "root";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Database connection error.");
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$ALLOWED_STATUS = ['For sale','Under offer','Sold']; // matches DB ENUM
$ALLOWED_TYPES  = [
    'Detached','Semi-detached','Terraced','Flat','Bungalow',
    'Cottage','Maisonette','Studio','Farmhouse','Mansion'
];
$ALLOWED_ROOM_LABELS = ['1','2','3','4','5','6','7','8','9','10+'];

$success = false;
$errorMsg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Simple: seller_id is always the signed-in user
    $seller_id     = (int)$_SESSION['user_id'];

    $title         = trim($_POST['title'] ?? '');
    $property_type = trim($_POST['property_type_name'] ?? '');
    $status        = trim($_POST['status'] ?? '');
    $price         = $_POST['price'] ?? '';

    $bedrooms      = $_POST['bedrooms'] ?? '';
    $bathrooms     = $_POST['bathrooms'] ?? '';

    $area_sqft     = $_POST['area_sqft'] ?? '';
    $garden_sqft   = $_POST['garden_sqft'] ?? '';
    $garage        = $_POST['garage'] ?? '';

    $location      = trim($_POST['location'] ?? '');
    $city          = trim($_POST['city'] ?? '');
    $postcode      = trim($_POST['postcode'] ?? '');
    $description   = trim($_POST['description'] ?? '');

    // Basic validation
    if ($title === '' || $property_type === '' || $status === '' || $price === '' ||
        $location === '' || $city === '' || $postcode === '' || $description === '') {
        $errorMsg = "Please fill in all required fields.";
    } elseif (!in_array($property_type, $ALLOWED_TYPES, true)) {
        $errorMsg = "Invalid property type selected.";
    } elseif (!in_array($status, $ALLOWED_STATUS, true)) {
        $errorMsg = "Invalid status selected.";
    } elseif (!in_array($bedrooms, $ALLOWED_ROOM_LABELS, true) || !in_array($bathrooms, $ALLOWED_ROOM_LABELS, true)) {
        $errorMsg = "Invalid bedrooms/bathrooms selected.";
    } elseif (!is_numeric($price)) {
        $errorMsg = "Price must be a number.";
    } else {

        // Optional numeric fields -> NULL if blank
        $area_sqft   = ($area_sqft === '' ? null : (int)$area_sqft);
        $garden_sqft = ($garden_sqft === '' ? null : (int)$garden_sqft);
        $garage      = ($garage === '' ? null : (int)$garage);

        try {
            // Insert property (includes seller_id)
            $sql = "INSERT INTO properties
                    (seller_id, property_type_name, title, description, price, location, city, postcode, status,
                     bedrooms, bathrooms, area_sqft, garden_sqft, garage)
                    VALUES
                    (:seller_id, :ptype, :title, :descr, :price, :loc, :city, :pc, :status,
                     :beds, :baths, :area, :garden, :garage)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':seller_id' => $seller_id,
                ':ptype'     => $property_type,
                ':title'     => $title,
                ':descr'     => $description,
                ':price'     => (int)$price,
                ':loc'       => $location,
                ':city'      => $city,
                ':pc'        => $postcode,
                ':status'    => $status,
                ':beds'      => $bedrooms,
                ':baths'     => $bathrooms,
                ':area'      => $area_sqft,
                ':garden'    => $garden_sqft,
                ':garage'    => $garage
            ]);

            $newPropertyId = (int)$pdo->lastInsertId();

            // Image upload (optional)
            if ($newPropertyId > 0 && !empty($_FILES['main_image']['name'])) {

                $uploads_dir = __DIR__ . '/uploads';
                if (!is_dir($uploads_dir)) {
                    mkdir($uploads_dir, 0777, true);
                }

                $originalName = basename($_FILES['main_image']['name']);
                $safeName     = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                $filename     = time() . '_' . $safeName;
                $target       = $uploads_dir . '/' . $filename;

                if (is_uploaded_file($_FILES['main_image']['tmp_name']) && move_uploaded_file($_FILES['main_image']['tmp_name'], $target)) {
                    $image_rel_path = 'uploads/' . $filename;

                    $stmtImg = $pdo->prepare("
                        INSERT INTO property_images (property_id, image_url, caption)
                        VALUES (:pid, :url, :cap)
                    ");
                    $stmtImg->execute([
                        ':pid' => $newPropertyId,
                        ':url' => $image_rel_path,
                        ':cap' => 'Main image'
                    ]);
                }
            }

            $success = true;

        } catch (PDOException $e) {
            $errorMsg = "Database error: " . h($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>List a Property • Real Estate Platform</title>
<style>
  :root{--brand:#2196f3;--bg:#f5f7fb;--ink:#27313a;--muted:#6b7785;--radius:14px;}
  *{box-sizing:border-box}
  body{margin:0;font-family:system-ui,Segoe UI,Roboto,Arial;background:var(--bg);color:var(--ink)}
  header{background:var(--brand);color:#fff;padding:16px 20px;display:flex;justify-content:space-between;align-items:center}
  header a{color:#fff;text-decoration:none;border:2px solid #fff;border-radius:999px;padding:8px 12px;font-weight:600}
  .wrap{max-width:1000px;margin:28px auto;padding:0 16px}
  .card{background:#fff;border-radius:20px;box-shadow:0 10px 30px rgba(0,0,0,.07);padding:22px}
  h1{text-align:center;margin:6px 0 18px}
  .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
  @media (max-width:820px){.grid-2{grid-template-columns:1fr}}
  .full{grid-column:1 / -1}
  .group{display:flex;flex-direction:column}
  .label{font-weight:700;margin:2px 0 6px}
  input[type="text"], input[type="number"], select, textarea{
    width:100%;padding:12px;border:2px solid #e4e9f3;border-radius:var(--radius);
    background:#f0f6ff;font-size:15px
  }
  textarea{min-height:120px;resize:vertical}
  .ok{background:#e8f5e9;border:1px solid #a5d6a7;color:#2e7d32;padding:10px 12px;border-radius:10px;margin-bottom:14px}
  .err{background:#ffebee;border:1px solid #ef9a9a;color:#b71c1c;padding:10px 12px;border-radius:10px;margin-bottom:14px}
  .btn{display:block;width:260px;margin:20px auto 0;background:var(--brand);color:#fff;border:none;border-radius:var(--radius);padding:14px;font-weight:700;cursor:pointer}
  .file{background:#fff;border:2px dashed #bcd7ff;padding:12px;border-radius:var(--radius)}
</style>
</head>
<body>
<header>
  <div><strong>Real Estate Platform</strong></div>
  <div><a href="homepage.php">Home</a></div>
</header>

<main class="wrap">
  <div class="card">
    <h1>List a Property</h1>

    <?php if ($success): ?>
      <div class="ok">Your property has been listed.</div>
    <?php endif; ?>

    <?php if ($errorMsg !== ''): ?>
      <div class="err"><?php echo h($errorMsg); ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <div class="grid-2">

        <div class="group full">
          <label class="label">Title</label>
          <input type="text" name="title" required>
        </div>

        <div class="group">
          <label class="label">Property Type</label>
          <select name="property_type_name" required>
            <option value="">Select…</option>
            <?php foreach ($ALLOWED_TYPES as $t): ?>
              <option value="<?php echo h($t); ?>"><?php echo h($t); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="group">
          <label class="label">Status</label>
          <select name="status" required>
            <?php foreach ($ALLOWED_STATUS as $st): ?>
              <option value="<?php echo h($st); ?>"><?php echo h($st); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="group">
          <label class="label">Price (£)</label>
          <input type="number" name="price" min="30000" step="10000" required>
        </div>

        <div class="group">
          <label class="label">Bedrooms</label>
          <select name="bedrooms" required>
            <option value="">Select…</option>
            <?php foreach ($ALLOWED_ROOM_LABELS as $lbl): ?>
              <option value="<?php echo h($lbl); ?>"><?php echo h($lbl); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="group">
          <label class="label">Bathrooms</label>
          <select name="bathrooms" required>
            <option value="">Select…</option>
            <?php foreach ($ALLOWED_ROOM_LABELS as $lbl): ?>
              <option value="<?php echo h($lbl); ?>"><?php echo h($lbl); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="group">
          <label class="label">Area (sq ft) (optional)</label>
          <input type="number" name="area_sqft">
        </div>

        <div class="group">
          <label class="label">Garden (sq ft) (optional)</label>
          <input type="number" name="garden_sqft">
        </div>

        <div class="group">
          <label class="label">Garage spaces (optional)</label>
          <input type="number" name="garage">
        </div>

        <div class="group full">
          <label class="label">Address / Location</label>
          <input type="text" name="location" required>
        </div>

        <div class="group">
          <label class="label">City</label>
          <input type="text" name="city" required>
        </div>

        <div class="group">
          <label class="label">Postcode</label>
          <input type="text" name="postcode" required>
        </div>

        <div class="group full">
          <label class="label">Description</label>
          <textarea name="description" required></textarea>
        </div>

        <div class="group full">
          <label class="label">Main Image (optional)</label>
          <div class="file">
            <input type="file" name="main_image">
          </div>
        </div>

      </div>

      <button class="btn" type="submit">Publish Listing</button>
    </form>
  </div>
</main>
</body>
</html>