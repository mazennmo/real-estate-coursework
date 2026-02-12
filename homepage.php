<?php
// Start session to detect whether the user is signed in
session_start();

// Decide where the Sell button should go

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real Estate Platform</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f5f5f5;
            color: #333;
        }

        header {
            background-color: #2196f3;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header h1 {
            margin: 0;
        }

        nav a {
            margin-left: 1.5rem;
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        .search-section {
            background: linear-gradient(to right, #64b5f6, #42a5f5);
            padding: 3rem 2rem;
            text-align: center;
            color: white;
        }

        .search-bar {
            margin-top: 1rem;
            display: flex;
            justify-content: center;
        }

        .search-bar input[type="text"] {
            width: 50%;
            padding: 0.8rem;
            border: none;
            border-radius: 5px 0 0 5px;
            font-size: 1rem;
        }

        .search-bar button {
            padding: 0.8rem 1rem;
            border: none;
            background-color: #1e88e5;
            color: white;
            border-radius: 0 5px 5px 0;
            cursor: pointer;
            font-size: 1rem;
        }

        .sell-cta {
            margin-top: 1.2rem;
            display: flex;
            justify-content: center;
        }

        .sell-btn {
            display: inline-block;
            background-color: #ff9800;
            color: #fff;
            border: none;
            border-radius: 999px;
            padding: 0.85rem 1.4rem;
            font-size: 1rem;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            cursor: pointer;
        }

        .sell-btn:focus,
        .sell-btn:hover {
            outline: none;
            filter: brightness(0.95);
        }

        .features {
            display: flex;
            justify-content: space-around;
            padding: 2rem;
            background-color: #fff;
        }

        .feature-box {
            background-color: #e3f2fd;
            padding: 1.5rem;
            border-radius: 10px;
            width: 25%;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }

        .feature-box h3 {
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>

<header>
    <h1>Find Your Home</h1>
    <nav>
        <a href="signin.php">Sign In</a>
        <a href="register.php">Register</a>
    </nav>
</header>

<section class="search-section">
    <h2>Find your place to call home</h2>

    
    <form class="search-bar" method="get" action="listings.php">
        <input type="text" name="location" placeholder="Enter city, postcode or area...">
        <button type="submit">Search</button>
    </form>

    <div class="sell-cta">
        <a class="sell-btn" href="<?= htmlspecialchars($sellUrl) ?>">
            Sell your property
        </a>
    </div>
</section>

<section class="features">
    <div class="feature-box">
        <a href="listings.php">Browse Listings</a>
        <p>Explore a wide range of homes for sale across the country.</p>
    </div>
    <div class="feature-box">
        <a href="favourites.php">Saved Favourites</a>
        <p>Keep track of the properties you love and compare them easily.</p>
    </div>
    <div class="feature-box">
        <a href="sellers.php">Trusted Sellers</a>
        <p>View seller ratings and reviews to buy with confidence.</p>
    </div>
</section>

</body>
</html>