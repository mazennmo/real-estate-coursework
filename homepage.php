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
        footer {
            background-color: #1976d2;
            color: white;
            text-align: center;
            padding: 1rem;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <header>
        <h1>Find Your Home</h1>
        <nav>
            <a href="#signin">Sign In</a>
            <a href="#register">Register</a>
        </nav>
    </header>

    <section class="search-section">
        <h2>Find your place to call home</h2>
        <div class="search-bar">
            <input type="text" placeholder="Enter city, postcode or area...">
            <button>Search</button>
        </div>
    </section>

    <section class="features">
        <div class="feature-box">
            <h3>Browse Listings</h3>
            <p>Explore a wide range of homes for sale and rent across the country.</p>
        </div>
        <div class="feature-box">
            <h3>Save Favourites</h3>
            <p>Keep track of the properties you love and compare them easily.</p>
        </div>
        <div class="feature-box">
            <h3>Trusted Sellers</h3>
            <p>View seller ratings and reviews to buy with confidence.</p>
        </div>
    </section>
</html>
