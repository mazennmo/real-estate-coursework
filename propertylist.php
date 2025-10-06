<?php
/**
 * propertylist.php
 * Create a new property listing (signed-in users only).
 */

session_start();

// ==== AUTH GUARD: must be signed in (session from your signin page) ====
if (empty($_SESSION['user_id'])) {
  header("Location: signin.php");
  exit;
}

// ==== DB CONNECT (PDO) ====
$dsn  = "mysql:host=localhost;dbname=realestate;charset=utf8mb4";
$user = "root";
$pass = "";

$errors = [];
$done = false;
$newPropertyId = null;

// ---- CSRF token (simple, effective) ----
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="'.htmlspecialchars($_SESSION['csrf']).'">'; }

// ---- Tiny helpers for sticky form and error display ----
function old($k){ return htmlspecialchars($_POST[$k] ?? '', ENT_QUOTES); }
function err($k,$errors){ return isset($errors[$k]) ? '<div class="error">'.$errors[$k].'</div>' : ''; }
function checked($name,$val){ return (isset($_POST[$name]) && in_array($val,(array)$_POST[$name])) ? 'checked' : ''; }
function sel($name,$val){ return (isset($_POST[$name]) && $_POST[$name] === $val) ? 'selected' : ''; }

// Allowed values for STATUS 
$ALLOWED_STATUS = ['Sold','Under offer','For sale'];

// Allowed ENUM values for Property Type
$ALLOWED_TYPES = [
  'Detatched','Semi-detatched','Terraced','Flat','Bungalow',
  'Cottage','Maisonette','Studio','Farmhouse','Mansion'
];

// Bedrooms/Bathrooms dropdown labels (we will store '10+' as integer 10)
$ALLOWED_ROOM_LABELS = ['1','2','3','4','5','6','7','8','9','10+'];

try {
  $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

  // ==== Load reference data for checkboxes (features) ====
  $features = $pdo->query("SELECT featureID, featureName FROM features ORDER BY featureName")->fetchAll();

  // ---- Handle submit ----
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF
    if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
      $errors['csrf'] = "Security check failed. Please reload the page and try again.";
    }

    // === Collect ===
    $title           = trim($_POST['title'] ?? '');
    $property_type   = trim($_POST['property_type'] ?? '');  // ENUM string
    $description     = trim($_POST['description'] ?? '');
    $price           = trim($_POST['price'] ?? '');
    $location        = trim($_POST['location'] ?? '');
    $city            = trim($_POST['city'] ?? '');
    $postcode        = trim($_POST['postcode'] ?? '');
    $status          = trim($_POST['status'] ?? 'For sale'); // default
    $bedrooms_label  = trim($_POST['bedrooms'] ?? '');       // '1'..'7' or '10+'
    $bathrooms_label = trim($_POST['bathrooms'] ?? '');      // '1'..'7' or '10+'
    $area_sqft       = trim($_POST['area_sqft'] ?? '');
    $garden_sqft     = trim($_POST['garden_sqft'] ?? '');
    $garage          = trim($_POST['garage'] ?? '');
    $chosenFeatures  = (array)($_POST['featureIDs'] ?? []);   // array of featureID ints

    // Coerce '10+' to integer 10 for storage
    $bedrooms  = ($bedrooms_label === '10+') ? 10 : $bedrooms_label;
    $bathrooms = ($bathrooms_label === '10+') ? 10 : $bathrooms_label;

    // === Validate (lengths & types match your schema) ===
    if ($title === '' || mb_strlen($title) > 70)             $errors['title'] = "Title is required (≤70 chars).";

    // Property Type must be one of the ENUM values
    if (!in_array($property_type, $ALLOWED_TYPES, true)) {
      $errors['property_type'] = "Select a valid property type.";
    }

    if ($description === '' || mb_strlen($description) < 30) $errors['description'] = "Please add a clear description (≥30 chars).";
    if ($price === '' || !ctype_digit($price) || (int)$price < 0) $errors['price'] = "Enter a valid non-negative price (whole number).";

    if ($location === '' || mb_strlen($location) > 70)       $errors['location'] = "Location is required (≤70 chars).";
    if ($city === '' || mb_strlen($city) > 30)               $errors['city'] = "City is required (≤30 chars).";
    if ($postcode === '' || mb_strlen($postcode) > 10)       $errors['postcode'] = "Postcode is required (≤10 chars).";

    if (!in_array($status, $ALLOWED_STATUS, true))           $errors['status'] = "Choose a valid status.";

    // Bedrooms/Bathrooms must be one of our labels; after coercion ensure digits and >=1
    if (!in_array($bedrooms_label, $ALLOWED_ROOM_LABELS, true) || !ctype_digit((string)$bedrooms) || (int)$bedrooms < 1) {
      $errors['bedrooms'] = "Choose bedrooms from the list.";
    }
    if (!in_array($bathrooms_label, $ALLOWED_ROOM_LABELS, true) || !ctype_digit((string)$bathrooms) || (int)$bathrooms < 1) {
      $errors['bathrooms']= "Choose bathrooms from the list.";
    }

    if ($area_sqft !== ''   && (!ctype_digit($area_sqft)   || (int)$area_sqft   < 0)) $errors['area_sqft']   = "Area (sqft) must be a positive integer.";
    if ($garden_sqft !== '' && (!ctype_digit($garden_sqft) || (int)$garden_sqft < 0)) $errors['garden_sqft'] = "Garden (sqft) must be a positive integer.";
    if ($garage !== ''      && (!ctype_digit($garage)      || (int)$garage      < 0)) $errors['garage']      = "Garage spaces must be a positive integer.";

    // === Optional image (stored in property_images) ===
    $image_rel_path = null;
    if (!empty($_FILES['main_image']['name'])) {
      $f = $_FILES['main_image'];
      if ($f['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        $mime = mime_content_type($f['tmp_name']);
        if (!isset($allowed[$mime])) {
          $errors['main_image'] = "Image must be JPG, PNG, or WEBP.";
        } elseif ($f['size'] > 5*1024*1024) {
          $errors['main_image'] = "Image must be ≤ 5MB.";
        } else {
          $ext = $allowed[$mime];
          $filename = 'prop_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
          $targetDir = __DIR__ . '/uploads';
          if (!is_dir($targetDir)) mkdir($targetDir, 0775, true);
          $target = $targetDir.'/'.$filename;
          if (!move_uploaded_file($f['tmp_name'], $target)) {
            $errors['main_image'] = "Failed to save the image.";
          } else {
            $image_rel_path = 'uploads/'.$filename; // relative URL to serve later
          }
        }
      } elseif ($f['error'] !== UPLOAD_ERR_NO_FILE) {
        $errors['main_image'] = "Upload error code: ".$f['error'];
      }
    }

    // === Insert if valid ===
    if (!$errors) {
      $pdo->beginTransaction();

      // 1) Insert into properties (now using Property_type_name ENUM)
      $stmt = $pdo->prepare("
        INSERT INTO properties
          (Property_type_name, title, description, price, location, city, postcode, status,
           bedrooms, bathrooms, area_sqft, garden_sqft, garage)
        VALUES
          (:ptype, :title, :descr, :price, :loc, :city, :pc, :status,
           :beds, :baths, :area, :garden, :garage)
      ");
      $stmt->execute([
        ':ptype'  => $property_type,
        ':title'  => $title,
        ':descr'  => $description,
        ':price'  => (int)$price,
        ':loc'    => $location,
        ':city'   => $city,
        ':pc'     => $postcode,
        ':status' => $status,
        ':beds'   => (int)$bedrooms,
        ':baths'  => (int)$bathrooms,
        ':area'   => ($area_sqft   === '' ? null : (int)$area_sqft),
        ':garden' => ($garden_sqft === '' ? null : (int)$garden_sqft),
        ':garage' => ($garage      === '' ? null : (int)$garage),
      ]);
      $newPropertyId = (int)$pdo->lastInsertId();

      // 2) Link features (property_features)
      if (!empty($chosenFeatures)) {
        $pf = $pdo->prepare("INSERT INTO property_features (propertyID, featureID) VALUES (?, ?)");
        foreach ($chosenFeatures as $fid) {
          if (ctype_digit((string)$fid)) $pf->execute([$newPropertyId, (int)$fid]);
        }
      }

      // 3) Optional image into property_images
      if ($image_rel_path) {
        $pi = $pdo->prepare("INSERT INTO property_images (property_id, image_url, caption) VALUES (?, ?, ?)");
        $pi->execute([$newPropertyId, $image_rel_path, 'Main image']);
      }

      // 4) Ensure the user has the Seller role in user_roles
      //    a) find/create roleID for 'Seller'
      $roleSellerId = null;
      $q = $pdo->prepare("SELECT roleID FROM roles WHERE roleName = 'Seller' LIMIT 1");
      $q->execute();
      $r = $q->fetch();
      if ($r) {
        $roleSellerId = (int)$r['roleID'];
      } else {
        // If roles table is empty/unseeded, insert sensible defaults.
        $pdo->exec("INSERT IGNORE INTO roles (roleID, roleName) VALUES (1,'Buyer')");
        $pdo->exec("INSERT IGNORE INTO roles (roleID, roleName) VALUES (2,'Seller')");
        $roleSellerId = 2;
      }
      //    b) upsert mapping in user_roles
      $ur = $pdo->prepare("SELECT 1 FROM user_roles WHERE userID = ? AND roleID = ? LIMIT 1");
      $ur->execute([$_SESSION['user_id'], $roleSellerId]);
      if (!$ur->fetch()) {
        $ins = $pdo->prepare("INSERT INTO user_roles (userID, roleID) VALUES (?, ?)");
        $ins->execute([$_SESSION['user_id'], $roleSellerId]);
      }

      $pdo->commit();
      $done = true;

      // Rotate CSRF after success to prevent accidental resubmits
      $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
  }
} catch (PDOException $e) {
  $errors['db'] = "Database error: " . htmlspecialchars($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>List a Property • Real Estate Platform</title>
<style>
  :root{--brand:#2196f3;--bg:#f5f7fb;--field:#d9ecff;--ink:#27313a;--hint:#6b7785;--err:#c62828;--r:14px}
  *{box-sizing:border-box}
  body{margin:0;font-family:system-ui,Segoe UI,Roboto,Arial;background:var(--bg);color:var(--ink)}
  header{background:var(--brand);color:#fff;padding:16px 20px;display:flex;justify-content:space-between;align-items:center}
  header a{color:#fff;text-decoration:none;border:2px solid #fff;border-radius:999px;padding:8px 12px;font-weight:600}
  .wrap{max-width:980px;margin:32px auto;padding:0 16px}
  .card{background:#fff;border-radius:20px;box-shadow:0 10px 30px rgba(0,0,0,.06);padding:26px}
  h1{text-align:center;margin:6px 0 18px}
  .row{display:grid;gap:12px;grid-template-columns:1fr 1fr 1fr}
  @media (max-width:980px){.row{grid-template-columns:1fr 1fr}}
  @media (max-width:680px){.row{grid-template-columns:1fr}}
  .group{margin:12px 0}
  .label{font-weight:700;margin:0 0 6px 6px}
  .input,.select,.textarea{
    width:100%;background:var(--field);border:2px solid transparent;border-radius:var(--r);
    padding:14px;font-size:16px
  }
  .textarea{min-height:120px;resize:vertical}
  .input:focus,.select:focus,.textarea:focus{outline:none;border-color:var(--brand);background:#eef6ff}
  .hint{font-size:12px;color:var(--hint);margin:6px 0 0 6px}
  .error{color:var(--err);font-size:13px;margin-top:6px}
  .btn{display:block;width:240px;margin:20px auto 0;background:var(--brand);color:#fff;border:none;border-radius:var(--r);padding:14px;font-weight:700;cursor:pointer}
  .ok{background:#e8f5e9;border:1px solid #a5d6a7;color:#2e7d32;padding:10px 12px;border-radius:10px;margin-bottom:14px}
  .feat{display:grid;grid-template-columns:repeat(3, minmax(140px,1fr));gap:10px}
  .file{background:#fff;border:2px dashed #bcd7ff;padding:14px;border-radius:var(--r)}
</style>
</head>
<body>
<header>
  <div><strong>Real Estate Platform</strong></div>
  <div>
    <a href="homepage.php">Home</a>
    <a href="signin.php">Account</a>
  </div>
</header>

<main class="wrap">
  <div class="card">
    <h1>List a Property</h1>

    <?php if (!empty($errors['db'])): ?>
      <div class="error"><?= $errors['db'] ?></div>
    <?php endif; ?>
    <?php if (!empty($errors['csrf'])): ?>
      <div class="error"><?= $errors['csrf'] ?></div>
    <?php endif; ?>

    <?php if ($done): ?>
      <div class="ok">
        Your property was listed successfully.
        <?php if ($newPropertyId): ?>
          View it: <a href="property.php?id=<?= (int)$newPropertyId ?>">Property #<?= (int)$newPropertyId ?></a>
        <?php endif; ?>
        Your account can now act as a <strong>Seller</strong> when listing, and as a <strong>Buyer</strong> when messaging on other listings.
      </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" novalidate>
      <?= csrf_field() ?>

      <div class="group">
        <div class="label">Title</div>
        <input class="input" type="text" name="title" placeholder="e.g. Modern 3-bed family house" value="<?= old('title') ?>" required>
        <div class="hint">Short, descriptive (≤70 chars).</div>
        <?= err('title',$errors) ?>
      </div>

      <div class="row">
        <div class="group">
          <div class="label">Property Type</div>
          <select class="select" name="property_type_name" required>
            <option value="">Select…</option>
            <?php foreach ($ALLOWED_TYPES as $t): ?>
              <option value="<?= htmlspecialchars($t) ?>" <?= sel('property_type_name',$t) ?>><?= htmlspecialchars($t) ?></option>
            <?php endforeach; ?>
          </select>
          <?= err('property_type_name',$errors) ?>
        </div>

        <div class="group">
          <div class="label">Status</div>
          <select class="select" name="status" required>
            <?php foreach ($ALLOWED_STATUS as $st): ?>
              <option value="<?= htmlspecialchars($st) ?>" <?= (old('status')===$st || (old('status')==='' && $st==='For sale')) ? 'selected':'' ?>>
                <?= htmlspecialchars($st) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?= err('status',$errors) ?>
        </div>
      </div>

      <div class="row">
        <div class="group">
          <div class="label">Price (£)</div>
          <input class="input" type="number" min="0" step="1" name="price" value="<?= old('price') ?>" required>
          <?= err('price',$errors) ?>
        </div>

        <div class="group">
          <div class="label">Bedrooms</div>
          <select class="select" name="bedrooms" required>
            <option value="">Select…</option>
            <?php foreach ($ALLOWED_ROOM_LABELS as $lbl): ?>
              <option value="<?= $lbl ?>" <?= sel('bedrooms',$lbl) ?>><?= $lbl ?></option>
            <?php endforeach; ?>
          </select>
          <?= err('bedrooms',$errors) ?>
        </div>

        <div class="group">
          <div class="label">Bathrooms</div>
          <select class="select" name="bathrooms" required>
            <option value="">Select…</option>
            <?php foreach ($ALLOWED_ROOM_LABELS as $lbl): ?>
              <option value="<?= $lbl ?>" <?= sel('bathrooms',$lbl) ?>><?= $lbl ?></option>
            <?php endforeach; ?>
          </select>
          <?= err('bathrooms',$errors) ?>
        </div>
      </div>

      <div class="row">
        <div class="group">
          <div class="label">Area (sq ft) <span class="hint">(optional)</span></div>
          <input class="input" type="number" min="0" step="1" name="area_sqft" value="<?= old('area_sqft') ?>">
          <?= err('area_sqft',$errors) ?>
        </div>
        <div class="group">
          <div class="label">Garden (sq ft) <span class="hint">(optional)</span></div>
          <input class="input" type="number" min="0" step="1" name="garden_sqft" value="<?= old('garden_sqft') ?>">
          <?= err('garden_sqft',$errors) ?>
        </div>
        <div class="group">
          <div class="label">Garage spaces <span class="hint">(optional)</span></div>
          <input class="input" type="number" min="0" step="1" name="garage" value="<?= old('garage') ?>">
          <?= err('garage',$errors) ?>
        </div>
      </div>

      <div class="group">
        <div class="label">Address / Location</div>
        <input class="input" type="text" name="location" placeholder="House number & street / area" value="<?= old('location') ?>" required>
        <?= err('location',$errors) ?>
      </div>

      <div class="row">
        <div class="group">
          <div class="label">City</div>
          <input class="input" type="text" name="city" value="<?= old('city') ?>" required>
          <?= err('city',$errors) ?>
        </div>
        <div class="group">
          <div class="label">Postcode</div>
          <input class="input" type="text" name="postcode" value="<?= old('postcode') ?>" required>
          <?= err('postcode',$errors) ?>
        </div>
      </div>

      <div class="group">
        <div class="label">Description</div>
        <textarea class="textarea" name="description" placeholder="Describe condition, layout, amenities, transport links, schools, etc." required><?= old('description') ?></textarea>
        <?= err('description',$errors) ?>
      </div>

      <?php if (!empty($features)): ?>
      <div class="group">
        <div class="label">Features</div>
        <div class="feat">
          <?php foreach ($features as $f): ?>
            <label>
              <input type="checkbox" name="featureIDs[]" value="<?= (int)$f['featureID'] ?>" <?= checked('featureIDs',(string)$f['featureID']) ?>>
              <?= htmlspecialchars($f['featureName'] ?: ('Feature #'.$f['featureID'])) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="group">
        <div class="label">Main Image <span class="hint">(JPG/PNG/WEBP, ≤5MB)</span></div>
        <div class="file"><input type="file" name="main_image" accept=".jpg,.jpeg,.png,.webp"></div>
        <?= err('main_image',$errors) ?>
      </div>

      <button class="btn" type="submit">Publish Listing</button>
    </form>
  </div>
</main>
</body>
</html>