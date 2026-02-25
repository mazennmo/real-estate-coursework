<?php
session_start();

// User must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

// Database connection
$dsn  = "mysql:host=localhost;dbname=realestate;charset=utf8mb4";
$user = "root";
$pass = "root";

$pdo = new PDO($dsn, $user, $pass);

$message = "";

// Run when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $seller_id     = $_SESSION['user_id'];
    $title         = $_POST['title'];
    $property_type = $_POST['property_type_name'];
    $status        = $_POST['status'];
    $price         = $_POST['price'];
    $bedrooms      = $_POST['bedrooms'];
    $bathrooms     = $_POST['bathrooms'];
    $location      = $_POST['location'];
    $city          = $_POST['city'];
    $postcode      = $_POST['postcode'];
    $description   = $_POST['description'];

    // Insert property first
    $sql = "INSERT INTO properties
            (seller_id, property_type_name, title, description, price, location, city, postcode, status, bedrooms, bathrooms)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $seller_id, $property_type, $title, $description, $price,
        $location, $city, $postcode, $status, $bedrooms, $bathrooms
    ]);
}
$message = "Property listed successfully!";

// After inserting property
$property_id = $pdo->lastInsertId();

if ($_FILES['main_image']['name']) {

    $filename = $_FILES['main_image']['name'];
    $target = "uploads/" . $filename;

    move_uploaded_file($_FILES['main_image']['tmp_name'], $target);

    $sql = "UPDATE properties SET main_image = ? WHERE property_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$target, $property_id]);
}

?>

<!DOCTYPE html>
<html>
<head>

<style>

:root{
  --brand:#2196f3;
  --bg:#f5f7fb;
  --ink:#27313a;
  --radius:14px;
  --field:#f0f6ff;
  --field-border:#e4e9f3;
}
*{ box-sizing:border-box; }

body{
  margin:0;
  font-family:system-ui, Segoe UI, Roboto, Arial;
  background:var(--bg);
  color:var(--ink);
}

/* Top bar */
header{
  background:var(--brand);
  color:#fff;
  padding:16px 20px;
  display:flex;
  justify-content:space-between;
  align-items:center;
}

header a{
  color:#fff;
  text-decoration:none;
  border:2px solid #fff;
  border-radius:999px;
  padding:8px 12px;
  font-weight:600;
  font-size:14px;
}

/* Page container */
.wrap{
  max-width:1000px;
  margin:28px auto;
  padding:0 16px;
}

.card{
  background:#fff;
  border-radius:20px;
  box-shadow:0 10px 30px rgba(0,0,0,.07);
  padding:22px;
}

h1{
  text-align:center;
  margin:6px 0 18px;
  font-size:20px;
}

/* Success message */
.msg{
  background:#e8f5e9;
  border:1px solid #a5d6a7;
  color:#2e7d32;
  padding:10px 12px;
  border-radius:10px;
  margin-bottom:14px;
  font-size:14px;
}

/* Form grid */
form{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:14px;
}

/* Each field block */
form p{
  margin:0;
  display:flex;
  flex-direction:column;
  font-weight:700;
  font-size:13px;
}

/* Full width rows */
form p:nth-of-type(1),
form p:nth-of-type(7),
form p:nth-of-type(10),
form p:nth-of-type(11),
form p:nth-of-type(12){
  grid-column:1 / -1;
}

/* Inputs */
input, textarea, select{
  margin-top:6px;
  width:100%;
  padding:12px;
  border:2px solid var(--field-border);
  border-radius:var(--radius);
  background:var(--field);
  font-size:15px;
  outline:none;
}

textarea{
  min-height:120px;
  resize:vertical;
}

/* File upload box */
.file-box{
  margin-top:6px;
  border:2px dashed #bcd7ff;
  border-radius:var(--radius);
  padding:14px;
  background:#fff;    
  width:203%;
}

.file-box input[type="file"]{
  margin:0;
  padding:0;
  border:none;
  background:transparent;
  font-size:14px;
}

/* Fix: make file button normal (no rounded corners) */
input[type="file"]{
  border-radius:0 !important;
}

input[type="file"]::-webkit-file-upload-button{
  border-radius:0 !important;
}

/* Button */
button{
  display:block;
  width:260px;
  margin:6px auto 0;
  background:var(--brand);
  color:#fff;
  border:none;
  border-radius:var(--radius);
  padding:14px;
  font-weight:700;
  cursor:pointer;
}

button:hover{
  filter:brightness(0.95);
}

/* Mobile */
@media (max-width:820px){
  form{ grid-template-columns:1fr; }

  form p:nth-of-type(1),
  form p:nth-of-type(7),
  form p:nth-of-type(10),
  form p:nth-of-type(11),
  form p:nth-of-type(12){
    grid-column:auto;
  }
}

</style>

</head>
<body>

<header>
    <strong>Real Estate Platform</strong>
    <a href="homepage.php">Home</a>
</header>

<div class="wrap">
    <div class="card">

        <h1>List a Property</h1>

        <?php if ($message): ?>
            <div class="msg"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">

            <p>Title<br>
            <input type="text" name="title" required></p>

            <p>Property Type<br>
                <select name="property_type_name" required>
                    <option value="" disabled selected>Select property type</option>
                    <option value="Detached">Detached</option>
                    <option value="Semi-detached">Semi-detached</option>
                    <option value="Terraced">Terraced</option>
                    <option value="Flat">Flat</option>
                    <option value="Bungalow">Bungalow</option>
                    <option value="Cottage">Cottage</option>
                    <option value="Maisonette">Maisonette</option>
                    <option value="Studio">Studio</option>
                    <option value="Farmhouse">Farmhouse</option>
                    <option value="Mansion">Mansion</option>
                </select>
            </p>

            <p>Status<br>
            <select name = "status" required>
                <option value="" disabled selected>Select status</option>
                <option value="For sale">For sale</option>
                <option value="Under offer">Under offer</option>
                <option value="Sold">Sold</option>
            </select>
            </p>

            <p>Price<br>
            <input type="number" name="price" min = "30000" required></p>
            
            <p>Bedrooms<br>
            <input type="number" name="bedrooms" required></p>

            <p>Bathrooms<br>
            <input type="number" name="bathrooms" required></p>

            <p>Location<br>
            <input type="text" name="location"></p>

            <p>City<br>
            <input type="text" name="city"></p>

            <p>Postcode<br>
            <input type="text" name="postcode"></p>

            <p>Description<br>
            <textarea name="description"></textarea></p>

            <p>Main Image<br>
                <div class="file-box">
                    <input type="file" name="main_image" required>
                </div>
            </p>

            <p><button type="submit">Publish Listing</button></p>

        </form>

    </div>
</div>

</body>
</html>