<?php
require_once '../includes/config.php';
requireCustomer();

$user_id = $_SESSION['user_id'];
$search_results = [];
$categories = ['Science', 'Art', 'Religion', 'History', 'Geography'];

// Handle the POST request for adding to cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $isbn = $_POST['isbn'] ?? '';
    $quantity = (int)($_POST['quantity'] ?? 1);
    
    if (empty($isbn)) {
        $error = "Invalid book selection.";
    } elseif ($quantity <= 0) {
        $error = "Quantity must be at least 1.";
    } else {
        $result = addToCart($pdo, $user_id, $isbn, $quantity);
        
        if ($result === true) {
            $_SESSION['success_message'] = "Book added to cart successfully!";
            header("Location: search.php?" . $_SERVER['QUERY_STRING']);
            exit();
        } else {
            $error = $result;
        }
    }
}

// Handle search (GET request)
if ($_SERVER['REQUEST_METHOD'] == 'GET' && (isset($_GET['search']) || isset($_GET['keyword']))) {
    $keyword = $_GET['keyword'] ?? '';
    $category = $_GET['category'] ?? '';
    $author = $_GET['author'] ?? '';
    $publisher = $_GET['publisher'] ?? '';
    
    $search_results = searchBooks($pdo, $keyword, $category, $author, $publisher);
}

// Check for success message
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Books - Online Bookstore</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Customer Dashboard</h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="search.php">Search Books</a>
                <a href="cart.php">Cart (<?php echo getCartCount($pdo, $user_id); ?>)</a>
                <a href="orders.php">My Orders</a>
                <a href="profile.php">My Profile</a>
                <a href="../logout.php">Logout</a>
            </nav>
        </header>
        
        <main>
            <h2>Search Books</h2>
            
            <?php if (isset($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="search-form">
                <form method="GET" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="keyword">Search:</label>
                            <input type="text" id="keyword" name="keyword" 
                                   value="<?php echo htmlspecialchars($_GET['keyword'] ?? ''); ?>" 
                                   placeholder="Enter title, ISBN, or category...">
                        </div>
                        
                        <div class="form-group">
                            <label for="category">Category:</label>
                            <select id="category" name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat; ?>" 
                                        <?php echo isset($_GET['category']) && $_GET['category'] == $cat ? 'selected' : ''; ?>>
                                    <?php echo $cat; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="author">Author:</label>
                            <input type="text" id="author" name="author" 
                                   value="<?php echo htmlspecialchars($_GET['author'] ?? ''); ?>" 
                                   placeholder="Author name...">
                        </div>
                        
                        <div class="form-group">
                            <label for="publisher">Publisher:</label>
                            <input type="text" id="publisher" name="publisher" 
                                   value="<?php echo htmlspecialchars($_GET['publisher'] ?? ''); ?>" 
                                   placeholder="Publisher name...">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="search" class="btn btn-primary">Search</button>
                        <a href="search.php" class="btn btn-secondary">Clear</a>
                    </div>
                </form>
            </div>
            
            <?php if (!empty($search_results) || (isset($_GET['keyword']) && empty($search_results))): ?>
            <div class="search-results">
                <div class="results-header">
                    <h3>Search Results</h3>
                    <?php if (!empty($search_results)): ?>
                        <span class="results-count"><?php echo count($search_results); ?> book(s) found</span>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($search_results)): ?>
                    <p class="no-results">No books found.</p>
                <?php else: ?>
                    <div class="book-grid">
                        <?php foreach ($search_results as $book): ?>
                        <div class="book-card">
                            <div class="book-header">
                                <h4><?php echo htmlspecialchars($book['title']); ?></h4>
                                <span class="stock-status <?php echo $book['quantity'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                                    <?php echo $book['quantity'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                                </span>
                            </div>
                            
                            <div class="book-details">
                                <p><strong>ISBN:</strong> <?php echo $book['ISBNID']; ?></p>
                                <p><strong>Category:</strong> <?php echo $book['category']; ?></p>
                                <p><strong>Price:</strong> $<?php echo number_format($book['price'], 2); ?></p>
                                <p><strong>Available:</strong> <?php echo $book['quantity']; ?> copies</p>
                            </div>
                            
                            <div class="book-actions">
                                <form method="POST" action="">
                                    <input type="hidden" name="isbn" value="<?php echo $book['ISBNID']; ?>">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Quantity:</label>
                                            <input type="number" name="quantity" value="1" min="1" max="<?php echo $book['quantity']; ?>"
                                                   <?php echo $book['quantity'] == 0 ? 'disabled' : ''; ?>>
                                        </div>
                                        <div class="form-group">
                                            <button type="submit" name="add_to_cart" class="btn btn-success"
                                                    <?php echo $book['quantity'] == 0 ? 'disabled' : ''; ?>>
                                                <?php echo $book['quantity'] > 0 ? 'Add to Cart' : 'Out of Stock'; ?>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>