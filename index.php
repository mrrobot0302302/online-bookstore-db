<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: customer/dashboard.php');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Bookstore - Home</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Online Bookstore</h1>
            <nav>
                <a href="index.php">Home</a>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            </nav>
        </header>
        
        <main>
            <div class="welcome">
                <h2>Welcome to Our Bookstore</h2>
                <p>Browse our collection of books in various categories.</p>
                <div class="categories">
                    <h3>Categories</h3>
                    <div class="category-grid">
                        <div class="category">Science</div>
                        <div class="category">Art</div>
                        <div class="category">Religion</div>
                        <div class="category">History</div>
                        <div class="category">Geography</div>
                    </div>
                </div>
                <div class="auth-buttons">
                    <a href="login.php" class="btn">Login</a>
                    <a href="register.php" class="btn btn-secondary">Register</a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>