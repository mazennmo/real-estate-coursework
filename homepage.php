<?php
// Start session to detect whether the user is signed in
session_start();

// Decide where the Sell button should go
$sellUrl = isset($_SESSION['user_id']) ? 'propertylist.php' : 'signin.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"> <!-- Sets character encoding for correct display of text -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Makes site responsive on all devices -->
    <title>Real Estate Platform</title>

    <style>
        /* General body styling */
        body {
            font-family: Arial, sans-serif; /* Clean, easy-to-read font */
            margin: 0; /* Remove default margins */
            background-color: #f5f5f5; /* Light grey background */
            color: #333; /* Dark text for good readability */
        }

        /* Header section */
        header {
            background-color: #2196f3; /* Blue background */
            color: white; /* White text */
            padding: 1rem 2rem; /* Spacing inside header */
            display: flex; /* Flexbox for alignment */
            justify-content: space-between; /* Push title left and nav right */
            align-items: center; /* Vertically center elements */
        }

        header h1 {
            margin: 0; /* Remove default margin */
        }

        /* Navigation links */
        nav a {
            margin-left: 1.5rem; /* Space between links */
            color: white; /* White text */
            text-decoration: none; /* Remove underline */
            font-weight: bold; /* Make text stand out */
        }

        /* Search banner section */
        .search-section {
            background: linear-gradient(to right, #64b5f6, #42a5f5); /* Gradient blue background */
            padding: 3rem 2rem; /* Large padding for spacing */
            text-align: center; /* Center text */
            color: white; /* White text */
        }

        /* Search bar layout */
        .search-bar {
            margin-top: 1rem;
            display: flex; /* Input and button side by side */
            justify-content: center;
        }

        /* Search input box */
        .search-bar input[type="text"] {
            width: 50%; /* Takes half screen width */
            padding: 0.8rem;
            border: none;
            border-radius: 5px 0 0 5px; /* Rounded left corners */
            font-size: 1rem;
        }

        /* Search button */
        .search-bar button {
            padding: 0.8rem 1rem;
            border: none;
            background-color: #1e88e5; /* Slightly darker blue */
            color: white;
            border-radius: 0 5px 5px 0; /* Rounded right corners */
            cursor: pointer; /* Show pointer on hover */
            font-size: 1rem;
        }

        /* Sell CTA (call-to-action) */
        .sell-cta {
            margin-top: 1.2rem;
            display: flex;
            justify-content: center;
        }
        .sell-btn {
            display: inline-block;
            background-color: #ff9800; /* Accent colour to stand out */
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

        /* Features section */
        .features {
            display: flex; /* Arrange features side by side */
            justify-content: space-around; /* Space between each box */
            padding: 2rem;
            background-color: #fff; /* White background */
        }

        /* Individual feature box */
        .feature-box {
            background-color: #e3f2fd; /* Light blue background */
            padding: 1.5rem;
            border-radius: 10px; /* Rounded corners */
            width: 25%; /* Each box takes about a quarter of width */
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); /* Subtle shadow */
            text-align: center;
        }

        .feature-box h3 {
            margin-bottom: 0.5rem; /* Small space below heading */
        }

        /* Footer styling (not currently in body) */
        footer {
            background-color: #1976d2; /* Darker blue */
            color: white;
            text-align: center;
            padding: 1rem;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <!-- Top navigation header -->
    <header>
        <h1>Find Your Home</h1>
        <nav>
            <a href="signin.php">Sign In</a> <!-- Link to sign in page -->
            <a href="register.php">Register</a> <!-- Link to register page -->
        </nav>
    </header>

    <!-- Hero search section -->
    <section class="search-section">
        <h2>Find your place to call home</h2>
        <div class="search-bar">
            <input type="text" placeholder="Enter city, postcode or area..."> <!-- Search input -->
            <button>Search</button> <!-- Search button -->
        </div>

        <!-- Sell CTA: decides target based on session -->
        <div class="sell-cta">
            <!-- If signed in → propertylist.php, else → signin.php -->
            <a class="sell-btn" href="<?= htmlspecialchars($sellUrl) ?>" aria-label="Sell your property">
                Sell your property
            </a>
        </div>
    </section>

    <!-- Features section (3 key site features) -->
    <section class="features">
        <div class="feature-box">
            <h3>Browse Listings</h3>
            <p>Explore a wide range of homes for sale across the country.</p>
        </div>
        <div class="feature-box">
            <h3>Saved Favourites</h3>
            <p>Keep track of the properties you love and compare them easily.</p>
        </div>
        <div class="feature-box">
            <h3>Trusted Sellers</h3>
            <p>View seller ratings and reviews to buy with confidence.</p>
        </div>
    </section>
</body>
</html>
