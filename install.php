<?php
$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "realestate";

try {
    // Connect to MySQL
    $conn = new PDO("mysql:host=$servername", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Use the database
    $conn->exec("USE realestate");

    // Create users table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS users (
            user_id INT AUTO_INCREMENT PRIMARY KEY,
            firstname VARCHAR(30) NOT NULL,
            lastname VARCHAR(30) NOT NULL,
            email VARCHAR(60) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            date_joined DATETIME DEFAULT CURRENT_TIMESTAMP,
            address VARCHAR(50)
        )
    ");

    // Create roles table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS roles (
            roleID INT PRIMARY KEY,
            roleName ENUM('Buyer', 'Seller') NOT NULL
        )
    ");

    // Create user_roles table (many-to-many relationship)
    $conn->exec("
        CREATE TABLE IF NOT EXISTS user_roles (
            userID INT,
            roleID INT,
            PRIMARY KEY(userID, roleID),
            FOREIGN KEY(userID) REFERENCES users(user_id),
            FOREIGN KEY(roleID) REFERENCES roles(roleID)
        )
    ");

    // Create property_type table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS property_type (
            Property_typeID INT PRIMARY KEY,
            Property_type_name ENUM('Detatched', 'Semi-detatched', 'Terraced', 'Flat', 'Bungalow','Cottage', 'Maisonette', 'Studio', 'Farmhouse','Mansion') NOT NULL
        )
    ");

    // Create properties table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS properties (
            property_id INT AUTO_INCREMENT PRIMARY KEY,
            Property_typeID INT,
            title VARCHAR(70),
            description TEXT,
            price INT,
            location VARCHAR(70),
            city VARCHAR(30),
            postcode VARCHAR(10),
            date_listed DATETIME DEFAULT CURRENT_TIMESTAMP,
            status ENUM('Sold', 'Under offer', 'For sale'),
            bedrooms ENUM('1', '2', '3', '4', '5', '6', '7', '8', '9', '10+'),
            bathrooms ENUM('1', '2', '3', '4', '5', '6', '7', '8', '9', '10+'),
            area_sqft INT,
            garden_sqft INT,
            garage INT,
            FOREIGN KEY(Property_typeID) REFERENCES property_type(Property_typeID)
        )
    ");

    // Create features table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS features (
            featureID INT PRIMARY KEY,
            featureName TEXT
        )
    ");

    // Create property_features table (many-to-many)
    $conn->exec("
        CREATE TABLE IF NOT EXISTS property_features (
            propertyID INT,
            featureID INT,
            PRIMARY KEY(propertyID, featureID),
            FOREIGN KEY(propertyID) REFERENCES properties(property_id),
            FOREIGN KEY(featureID) REFERENCES features(featureID)
        )
    ");

    // Create property_images table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS property_images (
            image_id INT PRIMARY KEY AUTO_INCREMENT,
            property_id INT,
            image_url VARCHAR(200),
            caption VARCHAR(200),
            FOREIGN KEY(property_id) REFERENCES properties(property_id)
        )
    ");

    // Create messages table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS messages (
            message_id INT PRIMARY KEY AUTO_INCREMENT,
            senderID INT,
            receiverID INT,
            property_id INT,
            messages TEXT,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(senderID) REFERENCES users(user_id),
            FOREIGN KEY(receiverID) REFERENCES users(user_id),
            FOREIGN KEY(property_id) REFERENCES properties(property_id)
        )
    ");

    // Create favourites table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS favourites (
            buyer_id INT,
            property_id INT,
            PRIMARY KEY(buyer_id, property_id),
            FOREIGN KEY(buyer_id) REFERENCES users(user_id),
            FOREIGN KEY(property_id) REFERENCES properties(property_id)
        )
    ");

    // Create reviews table
    $conn->exec("
        CREATE TABLE IF NOT EXISTS reviews (
            reviewID INT PRIMARY KEY AUTO_INCREMENT,
            buyer_id INT,
            seller_id INT,
            rating INT CHECK(rating BETWEEN 1 AND 5),
            comment TEXT,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(buyer_id) REFERENCES users(user_id),
            FOREIGN KEY(seller_id) REFERENCES users(user_id)
        )
    ");

   
}
?>