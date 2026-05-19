<?php
require_once '../includes/config.php';
requireCustomer();

$user_id = $_SESSION['user_id'];

// Get customer info
$stmt = $pdo->prepare("
    SELECT u.*, up.* 
    FROM Users u 
    JOIN UserProfile up ON u.user_id = up.user_id 
    WHERE u.user_id = ?
");
$stmt->execute([$user_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent orders (last 5)
$stmt = $pdo->prepare("
    SELECT s.*, b.title 
    FROM Sales s 
    JOIN Book b ON s.ISBNID = b.ISBNID 
    WHERE s.user_id = ? 
    ORDER BY s.sale_date DESC, s.sale_id DESC 
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get cart count
$cart_count = getCartCount($pdo, $user_id);

// Calculate total spent from ALL orders, not just recent ones
$stmt = $pdo->prepare("
    SELECT SUM(quantity * price_at_sale) as total_spent
    FROM Sales 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$total_spent_result = $stmt->fetch(PDO::FETCH_ASSOC);
$total_spent = $total_spent_result['total_spent'] ?? 0;

// Count total orders
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total_orders
    FROM Sales 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$total_orders_result = $stmt->fetch(PDO::FETCH_ASSOC);
$total_orders = $total_orders_result['total_orders'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Online Bookstore</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .customer-summary {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 992px) {
            .customer-summary {
                grid-template-columns: 1fr;
            }
        }
        
        .summary-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            border: 1px solid #e0e6ed;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .info-item {
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        
        .info-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .info-value {
            color: #555;
        }
        
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e0e6ed;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #3498db;
            margin: 10px 0;
        }
        
        .recent-section {
            margin-top: 30px;
        }
        
        .quick-actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e6ed;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 2px dashed #dee2e6;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Customer Dashboard</h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="search.php">Search Books</a>
                <a href="cart.php">Cart (<?php echo $cart_count; ?>)</a>
                <a href="orders.php">My Orders</a>
                <a href="profile.php">My Profile</a>
                <a href="../logout.php">Logout</a>
            </nav>
        </header>
        
        <main>
            <h2>Welcome, <?php echo htmlspecialchars($customer['first_name']); ?>!</h2>
            
            <div class="customer-summary">
                <div class="summary-card">
                    <h3>Account Information</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Name:</span>
                            <span class="info-value"><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?php echo htmlspecialchars($customer['email']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Phone:</span>
                            <span class="info-value"><?php echo htmlspecialchars($customer['phone']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Address:</span>
                            <span class="info-value"><?php echo htmlspecialchars($customer['address']); ?></span>
                        </div>
                    </div>
                    <a href="profile.php" class="btn btn-small">Edit Profile</a>
                </div>
                
                <div class="summary-stats">
                    <div class="stat-card">
                        <h3>Items in Cart</h3>
                        <p class="stat-number"><?php echo $cart_count; ?></p>
                        <a href="cart.php" class="btn btn-small">View Cart</a>
                    </div>
                    
                    <div class="stat-card">
                        <h3>Total Orders</h3>
                        <p class="stat-number"><?php echo $total_orders; ?></p>
                        <a href="orders.php" class="btn btn-small">View Orders</a>
                    </div>
                    
                    <div class="stat-card">
                        <h3>Total Spent</h3>
                        <p class="stat-number">$<?php echo number_format($total_spent, 2); ?></p>
                        <a href="orders.php" class="btn btn-small">View Details</a>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($recent_orders)): ?>
            <div class="recent-section">
                <h3>Recent Orders</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Book</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $order): 
                            $order_total = $order['quantity'] * $order['price_at_sale'];
                        ?>
                        <tr>
                            <td><?php echo $order['sale_date']; ?></td>
                            <td><?php echo htmlspecialchars($order['title']); ?></td>
                            <td><?php echo $order['quantity']; ?></td>
                            <td>$<?php echo number_format($order['price_at_sale'], 2); ?></td>
                            <td>$<?php echo number_format($order_total, 2); ?></td>
                            <td>
                                <a href="orders.php#order-<?php echo $order['sale_id']; ?>" class="btn btn-small">View Details</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <h3>No Orders Yet</h3>
                <p>You haven't placed any orders yet. Start shopping now!</p>
                <a href="search.php" class="btn">Browse Books</a>
            </div>
            <?php endif; ?>
            
            <div class="quick-actions">
                <h3>Quick Actions</h3>
                <div class="action-buttons">
                    <a href="search.php" class="btn">Search Books</a>
                    <a href="cart.php" class="btn">View Cart</a>
                    <a href="orders.php" class="btn">My Orders</a>
                    <a href="profile.php" class="btn">Edit Profile</a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>