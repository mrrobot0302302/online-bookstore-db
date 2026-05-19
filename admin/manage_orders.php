<?php
require_once '../includes/config.php';
requireAdmin();

// Get all publisher orders
$orders = $pdo->query("
    SELECT po.*, b.title, p.name as publisher_name, b.quantity as current_stock
    FROM Publisher_Order po
    JOIN Book b ON po.ISBNID = b.ISBNID
    JOIN Publisher p ON po.PID = p.PID
    ORDER BY 
        CASE WHEN po.status = 'Pending' THEN 1 ELSE 2 END,
        po.order_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Handle order confirmation (Requirement 4)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_order'])) {
    $order_id = $_POST['order_id'];
    
    try {
        $stmt = $pdo->prepare("UPDATE Publisher_Order SET status = 'Confirmed' WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $success = "Order confirmed successfully! Stock has been updated.";
        
        // Refresh orders
        $orders = $pdo->query("
            SELECT po.*, b.title, p.name as publisher_name, b.quantity as current_stock
            FROM Publisher_Order po
            JOIN Book b ON po.ISBNID = b.ISBNID
            JOIN Publisher p ON po.PID = p.PID
            ORDER BY 
                CASE WHEN po.status = 'Pending' THEN 1 ELSE 2 END,
                po.order_date DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        $error = "Error confirming order: " . $e->getMessage();
    }
}

// Handle manual order creation (if needed)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_order'])) {
    $isbn = $_POST['isbn'];
    $quantity = $_POST['quantity'];
    
    try {
        // Get book and publisher info
        $stmt = $pdo->prepare("
            SELECT b.*, p.PID 
            FROM Book b 
            JOIN Publisher p ON b.PID = p.PID 
            WHERE b.ISBNID = ?
        ");
        $stmt->execute([$isbn]);
        $book = $stmt->fetch();
        
        if ($book) {
            $stmt = $pdo->prepare("
                INSERT INTO Publisher_Order (ISBNID, PID, order_date, quantity, status)
                VALUES (?, ?, CURDATE(), ?, 'Pending')
            ");
            $stmt->execute([$isbn, $book['PID'], $quantity]);
            $success = "Manual order created successfully!";
            
            // Refresh orders
            $orders = $pdo->query("
                SELECT po.*, b.title, p.name as publisher_name, b.quantity as current_stock
                FROM Publisher_Order po
                JOIN Book b ON po.ISBNID = b.ISBNID
                JOIN Publisher p ON po.PID = p.PID
                ORDER BY 
                    CASE WHEN po.status = 'Pending' THEN 1 ELSE 2 END,
                    po.order_date DESC
            ")->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error = "Book not found.";
        }
    } catch (Exception $e) {
        $error = "Error creating order: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Admin Dashboard</title>
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
            <h2>Manage Publisher Orders</h2>
            <p class="page-description">
                This page shows all orders placed with publishers. 
                <strong>Auto-orders are created automatically</strong> when stock drops below threshold (Requirement 3).
                You can confirm orders when received (Requirement 4).
            </p>
            
            <?php if (isset($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="orders-section">
                <div class="section-header">
                    <h3>All Publisher Orders</h3>
                    <div class="order-stats">
                        <?php
                        $pending_count = array_reduce($orders, function($carry, $order) {
                            return $carry + ($order['status'] == 'Pending' ? 1 : 0);
                        }, 0);
                        $confirmed_count = count($orders) - $pending_count;
                        ?>
                        <span class="stat-badge pending">Pending: <?php echo $pending_count; ?></span>
                        <span class="stat-badge confirmed">Confirmed: <?php echo $confirmed_count; ?></span>
                    </div>
                </div>
                
                <?php if (empty($orders)): ?>
                    <p>No orders found.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Book</th>
                                <th>Publisher</th>
                                <th>Order Date</th>
                                <th>Quantity</th>
                                <th>Current Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr class="order-row status-<?php echo strtolower($order['status']); ?>">
                                <td><?php echo $order['order_id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($order['title']); ?></strong><br>
                                    <small>ISBN: <?php echo $order['ISBNID']; ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($order['publisher_name']); ?></td>
                                <td><?php echo $order['order_date']; ?></td>
                                <td><?php echo $order['quantity']; ?></td>
                                <td><?php echo $order['current_stock']; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($order['status'] == 'Pending'): ?>
                                    <form method="POST" action="" class="inline-form">
                                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                        <button type="submit" name="confirm_order" class="btn btn-small btn-success"
                                                onclick="return confirm('Confirm this order? Stock will increase by <?php echo $order['quantity']; ?> units.')">
                                            Confirm Order
                                        </button>
                                    </form>
                                    <?php else: ?>
                                        <span class="action-text">Confirmed on <?php echo $order['order_date']; ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <div class="auto-order-info">
                <h3>How Auto-Orders Work (Requirement 3)</h3>
                <ul>
                    <li>When a book's stock quantity drops <strong>from above threshold to below threshold</strong>, an automatic order is created</li>
                    <li>Order quantity is fixed at <strong>20 units</strong> (constant predefined in trigger)</li>
                    <li>Orders are created with status <strong>'Pending'</strong></li>
                    <li>When you confirm an order, stock automatically increases by the ordered quantity (Requirement 4)</li>
                </ul>
                <p><strong>Test:</strong> Go to Modify Books, record a sale that brings stock below threshold, then check back here for new order.</p>
            </div>
        </main>
    </div>
</body>
</html>