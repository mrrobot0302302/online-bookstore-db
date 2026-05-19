<?php
require_once '../includes/config.php';
requireAdmin();

$publishers = $pdo->query("SELECT * FROM Publisher ORDER BY name")->fetchAll();

// Handle search
$books = [];
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['search'])) {
    $search = trim($_GET['search']);
    if (!empty($search)) {
        $stmt = $pdo->prepare("
            SELECT b.*, p.name as publisher_name 
            FROM Book b 
            JOIN Publisher p ON b.PID = p.PID 
            WHERE b.title LIKE ? OR b.ISBNID LIKE ? OR b.category LIKE ?
            ORDER BY b.title
        ");
        $stmt->execute(["%$search%", "%$search%", "%$search%"]);
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Handle record sale (Requirement 2b)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['record_sale'])) {
    $isbn = $_POST['isbn'];
    $quantity_sold = (int)$_POST['quantity_sold'];
    
    try {
        // Get current quantity
        $stmt = $pdo->prepare("SELECT quantity, title FROM Book WHERE ISBNID = ?");
        $stmt->execute([$isbn]);
        $book = $stmt->fetch();
        
        if ($book) {
            $new_quantity = $book['quantity'] - $quantity_sold;
            
            // Update quantity (trigger will prevent negative)
            $stmt = $pdo->prepare("UPDATE Book SET quantity = ? WHERE ISBNID = ?");
            $stmt->execute([$new_quantity, $isbn]);
            
            $success = "Sale recorded successfully! Sold $quantity_sold copy(ies) of '{$book['title']}'.";
            
            // Refresh search results
            if (isset($_GET['search'])) {
                $search = $_GET['search'];
                $stmt = $pdo->prepare("
                    SELECT b.*, p.name as publisher_name 
                    FROM Book b 
                    JOIN Publisher p ON b.PID = p.PID 
                    WHERE b.title LIKE ? OR b.ISBNID LIKE ? OR b.category LIKE ?
                    ORDER BY b.title
                ");
                $stmt->execute(["%$search%", "%$search%", "%$search%"]);
                $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } else {
            $error = "Book not found.";
        }
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Stock quantity cannot be negative') !== false) {
            $error = "Cannot record sale: Stock would become negative.";
        } else {
            $error = "Error recording sale: " . $e->getMessage();
        }
    }
}

// Handle update book details
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_details'])) {
    $isbn = $_POST['isbn'];
    $title = trim($_POST['title']);
    $pid = $_POST['publisher'];
    $year = $_POST['year'];
    $price = $_POST['price'];
    $category = $_POST['category'];
    $threshold = $_POST['threshold'];
    
    try {
        $stmt = $pdo->prepare("
            UPDATE Book 
            SET title = ?, PID = ?, publication_year = ?, price = ?, category = ?, threshold = ? 
            WHERE ISBNID = ?
        ");
        $stmt->execute([$title, $pid, $year, $price, $category, $threshold, $isbn]);
        $success = "Book details updated successfully!";
        
        // Refresh search results
        if (isset($_GET['search'])) {
            $search = $_GET['search'];
            $stmt = $pdo->prepare("
                SELECT b.*, p.name as publisher_name 
                FROM Book b 
                JOIN Publisher p ON b.PID = p.PID 
                WHERE b.title LIKE ? OR b.ISBNID LIKE ? OR b.category LIKE ?
                ORDER BY b.title
            ");
            $stmt->execute(["%$search%", "%$search%", "%$search%"]);
            $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        $error = "Error updating book: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modify Books - Admin Dashboard</title>
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
            <h2>Modify Books & Record Sales</h2>
            
            <?php if (isset($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="search-section">
                <h3>Search Books to Modify</h3>
                <p>Search by title, ISBN, or category</p>
                <form method="GET" action="">
                    <div class="search-box">
                        <input type="text" name="search" 
                               value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                               placeholder="Enter book title, ISBN, or category...">
                        <button type="submit" class="btn">Search</button>
                        <?php if (isset($_GET['search'])): ?>
                            <a href="modify_book.php" class="btn btn-secondary">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <?php if (isset($_GET['search']) && empty($books) && !empty($_GET['search'])): ?>
                <p class="no-results">No books found matching "<?php echo htmlspecialchars($_GET['search']); ?>"</p>
            <?php endif; ?>
            
            <?php if (!empty($books)): ?>
            <div class="search-results">
                <h3>Search Results (<?php echo count($books); ?> books found)</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ISBN</th>
                            <th>Title</th>
                            <th>Publisher</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Current Stock</th>
                            <th>Threshold</th>
                            <th>Record Sale</th>
                            <th>Edit Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($books as $book): ?>
                        <tr>
                            <td><?php echo $book['ISBNID']; ?></td>
                            <td><?php echo htmlspecialchars($book['title']); ?></td>
                            <td><?php echo htmlspecialchars($book['publisher_name']); ?></td>
                            <td><?php echo $book['category']; ?></td>
                            <td>$<?php echo number_format($book['price'], 2); ?></td>
                            <td>
                                <?php 
                                echo $book['quantity'];
                                if ($book['quantity'] < $book['threshold']) {
                                    echo ' <span class="stock-warning">(Low Stock)</span>';
                                }
                                ?>
                            </td>
                            <td><?php echo $book['threshold']; ?></td>
                            <td>
                                <form method="POST" action="" class="inline-form">
                                    <input type="hidden" name="isbn" value="<?php echo $book['ISBNID']; ?>">
                                    <div class="sale-form">
                                        <input type="number" name="quantity_sold" 
                                               min="1" max="<?php echo $book['quantity']; ?>" 
                                               value="1" style="width: 60px;" required>
                                        <button type="submit" name="record_sale" class="btn btn-small btn-success">
                                            Record Sale
                                        </button>
                                    </div>
                                </form>
                            </td>
                            <td>
                                <button type="button" class="btn btn-small btn-edit" 
                                        onclick="showEditForm('<?php echo $book['ISBNID']; ?>')">
                                    Edit
                                </button>
                            </td>
                        </tr>
                        
                        <!-- Hidden edit form for this book -->
                        <tr id="edit-form-<?php echo $book['ISBNID']; ?>" class="edit-form-row" style="display: none;">
                            <td colspan="9">
                                <div class="edit-form-container">
                                    <h4>Edit Book: <?php echo htmlspecialchars($book['title']); ?></h4>
                                    <form method="POST" action="" class="edit-book-form">
                                        <input type="hidden" name="isbn" value="<?php echo $book['ISBNID']; ?>">
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label>Title:</label>
                                                <input type="text" name="title" 
                                                       value="<?php echo htmlspecialchars($book['title']); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Publisher:</label>
                                                <select name="publisher" required>
                                                    <?php foreach ($publishers as $publisher): ?>
                                                    <option value="<?php echo $publisher['PID']; ?>" 
                                                        <?php echo $publisher['PID'] == $book['PID'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($publisher['name']); ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label>Publication Year:</label>
                                                <input type="number" name="year" 
                                                       value="<?php echo $book['publication_year']; ?>" 
                                                       min="1900" max="<?php echo date('Y'); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Price:</label>
                                                <input type="number" name="price" 
                                                       value="<?php echo $book['price']; ?>" 
                                                       step="0.01" min="0.01" required>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label>Category:</label>
                                                <select name="category" required>
                                                    <option value="Science" <?php echo $book['category'] == 'Science' ? 'selected' : ''; ?>>Science</option>
                                                    <option value="Art" <?php echo $book['category'] == 'Art' ? 'selected' : ''; ?>>Art</option>
                                                    <option value="Religion" <?php echo $book['category'] == 'Religion' ? 'selected' : ''; ?>>Religion</option>
                                                    <option value="History" <?php echo $book['category'] == 'History' ? 'selected' : ''; ?>>History</option>
                                                    <option value="Geography" <?php echo $book['category'] == 'Geography' ? 'selected' : ''; ?>>Geography</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Threshold:</label>
                                                <input type="number" name="threshold" 
                                                       value="<?php echo $book['threshold']; ?>" min="1" required>
                                            </div>
                                        </div>
                                        <div class="form-actions">
                                            <button type="submit" name="update_details" class="btn">Update Book</button>
                                            <button type="button" class="btn btn-secondary" 
                                                    onclick="hideEditForm('<?php echo $book['ISBNID']; ?>')">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="search-note">
                    <p><strong>Note:</strong> Recording a sale will decrease the stock quantity. 
                    The system will automatically prevent negative stock (trigger prevents this).</p>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script>
    function showEditForm(isbn) {
        // Hide all other edit forms
        document.querySelectorAll('.edit-form-row').forEach(row => {
            row.style.display = 'none';
        });
        // Show the selected edit form
        document.getElementById('edit-form-' + isbn).style.display = 'table-row';
    }
    
    function hideEditForm(isbn) {
        document.getElementById('edit-form-' + isbn).style.display = 'none';
    }
    
    // Validate sale quantity
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('input[name="quantity_sold"]').forEach(input => {
            input.addEventListener('change', function() {
                const max = parseInt(this.getAttribute('max'));
                const value = parseInt(this.value);
                if (value > max) {
                    this.value = max;
                    alert(`Cannot sell more than available stock (${max} copies)`);
                }
                if (value < 1) {
                    this.value = 1;
                }
            });
        });
    });
    </script>
</body>
</html>