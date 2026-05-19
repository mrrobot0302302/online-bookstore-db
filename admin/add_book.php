<?php
require_once '../includes/config.php';
requireAdmin();

$publishers = $pdo->query("SELECT * FROM Publisher ORDER BY name")->fetchAll();
$authors = $pdo->query("SELECT * FROM Author ORDER BY name")->fetchAll();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get and validate data
    $isbn = trim($_POST['isbn']);
    $title = trim($_POST['title']);
    $pid = $_POST['publisher'];
    $year = $_POST['year'];
    $price = $_POST['price'];
    $category = $_POST['category'];
    $quantity = $_POST['quantity'];
    $threshold = $_POST['threshold'];
    $selected_authors = $_POST['authors'] ?? [];
    
    // Validation
    if (empty($isbn)) $errors[] = "ISBN is required";
    if (empty($title)) $errors[] = "Title is required";
    if (empty($pid)) $errors[] = "Publisher is required";
    if ($year < 1900 || $year > date('Y')) $errors[] = "Invalid publication year";
    if ($price <= 0) $errors[] = "Price must be greater than 0";
    if ($quantity < 0) $errors[] = "Quantity cannot be negative";
    if ($threshold < 1) $errors[] = "Threshold must be at least 1";
    if (empty($selected_authors)) $errors[] = "At least one author is required";
    
    // Check if ISBN already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Book WHERE ISBNID = ?");
    $stmt->execute([$isbn]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = "ISBN already exists";
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Insert book
            $stmt = $pdo->prepare("
                INSERT INTO Book (ISBNID, title, PID, publication_year, price, category, quantity, threshold) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$isbn, $title, $pid, $year, $price, $category, $quantity, $threshold]);
            
            // Insert book authors
            foreach ($selected_authors as $author_id) {
                $stmt = $pdo->prepare("INSERT INTO Book_Authors (AID, ISBNID) VALUES (?, ?)");
                $stmt->execute([$author_id, $isbn]);
            }
            
            $pdo->commit();
            $success = "Book added successfully!";
            
            // Clear form
            $_POST = [];
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Error adding book: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Book - Admin Dashboard</title>
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
            <h2>Add New Book</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="error">
                    <h3>Please fix the following errors:</h3>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" class="book-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="isbn">ISBN *:</label>
                        <input type="text" id="isbn" name="isbn" 
                               value="<?php echo htmlspecialchars($_POST['isbn'] ?? ''); ?>" required
                               placeholder="e.g., 978-3-16-148410-0">
                    </div>
                    
                    <div class="form-group">
                        <label for="title">Title *:</label>
                        <input type="text" id="title" name="title" 
                               value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required
                               placeholder="Book title">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="publisher">Publisher *:</label>
                        <select id="publisher" name="publisher" required>
                            <option value="">Select Publisher</option>
                            <?php foreach ($publishers as $publisher): ?>
                            <option value="<?php echo $publisher['PID']; ?>" 
                                <?php echo ($_POST['publisher'] ?? '') == $publisher['PID'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($publisher['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="year">Publication Year *:</label>
                        <input type="number" id="year" name="year" 
                               value="<?php echo $_POST['year'] ?? date('Y'); ?>" 
                               min="1900" max="<?php echo date('Y'); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Price *:</label>
                        <input type="number" id="price" name="price" 
                               value="<?php echo $_POST['price'] ?? ''; ?>" 
                               step="0.01" min="0.01" required
                               placeholder="e.g., 29.99">
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Category *:</label>
                        <select id="category" name="category" required>
                            <option value="">Select Category</option>
                            <option value="Science" <?php echo ($_POST['category'] ?? '') == 'Science' ? 'selected' : ''; ?>>Science</option>
                            <option value="Art" <?php echo ($_POST['category'] ?? '') == 'Art' ? 'selected' : ''; ?>>Art</option>
                            <option value="Religion" <?php echo ($_POST['category'] ?? '') == 'Religion' ? 'selected' : ''; ?>>Religion</option>
                            <option value="History" <?php echo ($_POST['category'] ?? '') == 'History' ? 'selected' : ''; ?>>History</option>
                            <option value="Geography" <?php echo ($_POST['category'] ?? '') == 'Geography' ? 'selected' : ''; ?>>Geography</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="quantity">Initial Quantity *:</label>
                        <input type="number" id="quantity" name="quantity" 
                               value="<?php echo $_POST['quantity'] ?? '0'; ?>" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="threshold">Threshold *:</label>
                        <input type="number" id="threshold" name="threshold" 
                               value="<?php echo $_POST['threshold'] ?? '5'; ?>" min="1" required>
                        <small class="form-text">Minimum quantity to maintain in stock</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Authors *:</label>
                    <div class="checkbox-grid">
                        <?php foreach ($authors as $author): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="authors[]" value="<?php echo $author['AID']; ?>"
                                <?php echo in_array($author['AID'], $_POST['authors'] ?? []) ? 'checked' : ''; ?>>
                            <?php echo htmlspecialchars($author['name']); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Add Book</button>
                    <button type="reset" class="btn btn-secondary">Reset Form</button>
                    <a href="dashboard.php" class="btn">Cancel</a>
                </div>
                
                <p class="form-note">* Required fields</p>
            </form>
        </main>
    </div>
</body>
</html>