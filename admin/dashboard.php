<?php
require_once '../includes/config.php';
requireAdmin();

// Get statistics for dashboard
$stats = [
    'total_books' => $pdo->query("SELECT COUNT(*) FROM Book")->fetchColumn(),
    'total_publishers' => $pdo->query("SELECT COUNT(*) FROM Publisher")->fetchColumn(),
    'total_customers' => $pdo->query("SELECT COUNT(*) FROM Users WHERE role = 'CUSTOMER'")->fetchColumn(),
    'pending_orders' => $pdo->query("SELECT COUNT(*) FROM Publisher_Order WHERE status = 'Pending'")->fetchColumn(),
    'low_stock_books' => $pdo->query("SELECT COUNT(*) FROM Book WHERE quantity < threshold")->fetchColumn(),
    'total_sales_today' => $pdo->query("SELECT SUM(quantity * price_at_sale) FROM Sales WHERE sale_date = CURDATE()")->fetchColumn() ?? 0,
];

// Get recent sales
$recent_sales = $pdo->query("
    SELECT s.*, b.title, u.username, CONCAT(up.first_name, ' ', up.last_name) as customer_name
    FROM Sales s
    JOIN Book b ON s.ISBNID = b.ISBNID
    JOIN Users u ON s.user_id = u.user_id
    JOIN UserProfile up ON u.user_id = up.user_id
    ORDER BY s.sale_date DESC, s.sale_id DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Get low stock books
$low_stock = $pdo->query("
    SELECT b.*, p.name as publisher_name
    FROM Book b
    JOIN Publisher p ON b.PID = p.PID
    WHERE b.quantity < b.threshold
    ORDER BY b.quantity ASC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Online Bookstore</title>
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
            <h2>Welcome, Admin</h2>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Books</h3>
                    <p class="stat-number"><?php echo $stats['total_books']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Publishers</h3>
                    <p class="stat-number"><?php echo $stats['total_publishers']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Customers</h3>
                    <p class="stat-number"><?php echo $stats['total_customers']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Pending Orders</h3>
                    <p class="stat-number"><?php echo $stats['pending_orders']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Low Stock Books</h3>
                    <p class="stat-number"><?php echo $stats['low_stock_books']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Today's Sales</h3>
                    <p class="stat-number">$<?php echo number_format($stats['total_sales_today'], 2); ?></p>
                </div>
            </div>
            
            <div class="dashboard-sections">
                <div class="section">
                    <h3>Recent Sales</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Book</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_sales as $sale): ?>
                            <tr>
                                <td><?php echo $sale['sale_date']; ?></td>
                                <td><?php echo $sale['customer_name']; ?></td>
                                <td><?php echo $sale['title']; ?></td>
                                <td><?php echo $sale['quantity']; ?></td>
                                <td>$<?php echo $sale['price_at_sale']; ?></td>
                                <td>$<?php echo number_format($sale['quantity'] * $sale['price_at_sale'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="section">
                    <h3>Low Stock Alert</h3>
                    <?php if (!empty($low_stock)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ISBN</th>
                                <th>Title</th>
                                <th>Publisher</th>
                                <th>Current</th>
                                <th>Threshold</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($low_stock as $book): ?>
                            <tr>
                                <td><?php echo $book['ISBNID']; ?></td>
                                <td><?php echo $book['title']; ?></td>
                                <td><?php echo $book['publisher_name']; ?></td>
                                <td><?php echo $book['quantity']; ?></td>
                                <td><?php echo $book['threshold']; ?></td>
                                <td><span class="status-warning">Low Stock</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p>All books have sufficient stock.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="quick-actions">
                <h3>Quick Actions</h3>
                <div class="action-buttons">
                    <a href="add_book.php" class="btn">Add New Book</a>
                    <a href="modify_book.php" class="btn">Modify Books</a>
                    <a href="manage_orders.php" class="btn">Manage Orders</a>
                    <a href="reports.php" class="btn">View Reports</a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>