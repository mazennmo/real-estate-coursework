<?php
$servername   = "localhost";
$username     = "root";
$password     = "";
$dbname       = "realestate";

try {
    // Connect to MySQL
    $conn = new PDO("mysql:host=$servername", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Create DB if not exists, then use it
    $conn->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->exec("USE `$dbname`");

    /* =========================== USERS / ROLES =========================== */
    $conn->exec("
        CREATE TABLE IF NOT EXISTS users (
            user_id INT AUTO_INCREMENT PRIMARY KEY,
            firstname VARCHAR(30) NOT NULL,
            lastname  VARCHAR(30) NOT NULL,
            email VARCHAR(60) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            date_joined DATETIME DEFAULT CURRENT_TIMESTAMP,
            address VARCHAR(50)
        )
    ");

    $conn->exec("
        CREATE TABLE IF NOT EXISTS roles (
            roleID INT AUTO_INCREMENT PRIMARY KEY,
            roleName ENUM('Buyer','Seller') NOT NULL UNIQUE
        )
    ");
    $conn->exec("INSERT IGNORE INTO roles (roleID, roleName) VALUES (1,'Buyer'),(2,'Seller')");

    $conn->exec("
        CREATE TABLE IF NOT EXISTS user_roles (
            userID INT NOT NULL,
            roleID INT NOT NULL,
            PRIMARY KEY(userID, roleID),
            FOREIGN KEY(userID) REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY(roleID) REFERENCES roles(roleID) ON DELETE CASCADE
        )
    ");

    /* ============================ PROPERTIES ============================ */

    // Create properties with a direct property_type_name column (ENUM for safety)
    $conn->exec("
        CREATE TABLE IF NOT EXISTS properties (
            property_id INT AUTO_INCREMENT PRIMARY KEY,
            property_type_name ENUM(
                'Detached','Semi-detached','Terraced','Flat','Bungalow',
                'Cottage','Maisonette','Studio','Farmhouse','Mansion'
            ) NOT NULL,
            title VARCHAR(70) NOT NULL,
            description TEXT NOT NULL,
            price INT NOT NULL,
            location VARCHAR(70) NOT NULL,
            city VARCHAR(30) NOT NULL,
            postcode VARCHAR(10) NOT NULL,
            date_listed DATETIME DEFAULT CURRENT_TIMESTAMP,
            status ENUM('Sold','Under offer','For sale') NOT NULL,
            bedrooms ENUM('1','2','3','4','5','6','7','8','9','10+') NOT NULL,
            bathrooms ENUM('1','2','3','4','5','6','7','8','9','10+') NOT NULL,
            area_sqft INT NULL,
            garden_sqft INT NULL,
            garage INT NULL
        )
    ");
    $conn->exec("
  ALTER TABLE properties
  ADD COLUMN IF NOT EXISTS property_type_name ENUM(
    'Detached','Semi-detached','Terraced','Flat','Bungalow',
    'Cottage','Maisonette','Studio','Farmhouse','Mansion'
  ) NOT NULL AFTER property_id
");


    // If table existed before without the column, add it 
    $conn->exec("
        ALTER TABLE properties
        ADD COLUMN IF NOT EXISTS property_type_name ENUM(
            'Detached','Semi-detached','Terraced','Flat','Bungalow',
            'Cottage','Maisonette','Studio','Farmhouse','Mansion'
        ) NOT NULL FIRST
    ");

    /* ============================= FEATURES ============================= */
    $conn->exec("
        CREATE TABLE IF NOT EXISTS features (
            featureID INT AUTO_INCREMENT PRIMARY KEY,
            featureName VARCHAR(80) NOT NULL UNIQUE
        )
    ");

    $conn->exec("
        CREATE TABLE IF NOT EXISTS property_features (
            propertyID INT NOT NULL,
            featureID INT NOT NULL,
            PRIMARY KEY(propertyID, featureID),
            FOREIGN KEY(propertyID) REFERENCES properties(property_id) ON DELETE CASCADE,
            FOREIGN KEY(featureID) REFERENCES features(featureID) ON DELETE CASCADE
        )
    ");

    /* =========================== PROPERTY IMAGES ======================== */
    $conn->exec("
        CREATE TABLE IF NOT EXISTS property_images (
            image_id INT AUTO_INCREMENT PRIMARY KEY,
            property_id INT NOT NULL,
            image_url VARCHAR(200) NOT NULL,
            caption VARCHAR(200),
            FOREIGN KEY(property_id) REFERENCES properties(property_id) ON DELETE CASCADE
        )
    ");

    /* =============================== MESSAGES =========================== */
    $conn->exec("
        CREATE TABLE IF NOT EXISTS messages (
            message_id INT AUTO_INCREMENT PRIMARY KEY,
            senderID INT NOT NULL,
            receiverID INT NOT NULL,
            property_id INT NOT NULL,
            messages TEXT NOT NULL,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(senderID)   REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY(receiverID) REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY(property_id) REFERENCES properties(property_id) ON DELETE CASCADE
        )
    ");

    /* =============================== FAVOURITES ========================= */
    $conn->exec("
        CREATE TABLE IF NOT EXISTS favourites (
            buyer_id INT NOT NULL,
            property_id INT NOT NULL,
            PRIMARY KEY(buyer_id, property_id),
            FOREIGN KEY(buyer_id)  REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY(property_id) REFERENCES properties(property_id) ON DELETE CASCADE
        )
    ");

    /* ================================ REVIEWS =========================== */
    $conn->exec("
        CREATE TABLE IF NOT EXISTS reviews (
            reviewID INT AUTO_INCREMENT PRIMARY KEY,
            buyer_id INT NOT NULL,
            seller_id INT NOT NULL,
            rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
            comment TEXT,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(buyer_id) REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY(seller_id) REFERENCES users(user_id) ON DELETE CASCADE
        )
    ");

    echo "Install complete.";
} catch (PDOException $e) {
    echo "Install error: " . htmlspecialchars($e->getMessage());
}
