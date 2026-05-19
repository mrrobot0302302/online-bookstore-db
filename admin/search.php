<?php
require_once '../includes/config.php';
requireAdmin();

$search_results = [];
$categories = ['Science', 'Art', 'Religion', 'History', 'Geography'];

if ($_SERVER['REQUEST_METHOD'] == 'GET' && (isset($_GET['keyword']) || isset($_GET['category']))) {
    $keyword = $_GET['keyword'] ?? '';
    $category = $_GET['category'] ?? '';
    $author = $_GET['author'] ?? '';
    $publisher = $_GET['publisher'] ?? '';
    
    $search_results = searchBooks($pdo, $keyword, $category, $author, $publisher);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Books - Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Admin Dashboard</h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="search.php">Search Books</a>
                <a href="add_book.php">Add Book</a>
                <a href="modify_book.php">Modify Books</a>
                <a href="manage_orders.php">Manage Orders</a>
                <a href="view_orders.php">Sales Orders</a>
                <a href="reports.php">Reports</a>
                <a href="../logout.php">Logout</a>
            </nav>
        </header>
        
        <main>
            <h2>Search Books</h2>
            <p class="page-description">
                Search for books by ISBN, title, category, author, or publisher. 
                Available to both Admin and Customer users (Requirement 5).
            </p>
            
            <div class="search-form">
                <form method="GET" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="keyword">Search by ISBN or Title:</label>
                            <input type="text" id="keyword" name="keyword" 
                                   value="<?php echo htmlspecialchars($_GET['keyword'] ?? ''); ?>" 
                                   placeholder="Enter ISBN or book title...">
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
                        <button type="submit" class="btn btn-primary">Search</button>
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
                    <p class="no-results">No books found matching your search criteria.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ISBN</th>
                                <th>Title</th>
                                <th>Authors</th>
                                <th>Publisher</th>
                                <th>Category</th>
                                <th>Year</th>
                                <th>Price</th>
                                <th>In Stock</th>
                                <th>Threshold</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($search_results as $book): ?>
                            <tr>
                                <td><?php echo $book['ISBNID']; ?></td>
                                <td><?php echo htmlspecialchars($book['title']); ?></td>
                                <td><?php echo htmlspecialchars($book['authors'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($book['publisher_name']); ?></td>
                                <td><?php echo $book['category']; ?></td>
                                <td><?php echo $book['publication_year']; ?></td>
                                <td>$<?php echo number_format($book['price'], 2); ?></td>
                                <td>
                                    <?php 
                                    echo $book['quantity'];
                                    if ($book['quantity'] < $book['threshold']) {
                                        echo ' <span class="stock-warning">(Low)</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo $book['threshold']; ?></td>
                                <td>
                                    <?php if ($book['quantity'] == 0): ?>
                                        <span class="status-out">Out of Stock</span>
                                    <?php elseif ($book['quantity'] < $book['threshold']): ?>
                                        <span class="status-low">Low Stock</span>
                                    <?php else: ?>
                                        <span class="status-ok">In Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="modify_book.php?search=<?php echo urlencode($book['ISBNID']); ?>" 
                                       class="btn btn-small">Edit</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>