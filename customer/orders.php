<?php
require_once '../includes/config.php';
requireCustomer();

$user_id = $_SESSION['user_id'];

// Get all orders for this customer with book details
$stmt = $pdo->prepare("
    SELECT 
        s.sale_id,
        s.sale_date,
        s.quantity,
        s.price_at_sale,
        b.title,
        b.ISBNID,
        b.category,
        CONCAT(up.first_name, ' ', up.last_name) as customer_name,
        up.email,
        up.phone,
        up.address
    FROM Sales s
    JOIN Book b ON s.ISBNID = b.ISBNID
    JOIN Users u ON s.user_id = u.user_id
    JOIN UserProfile up ON u.user_id = up.user_id
    WHERE s.user_id = ?
    ORDER BY s.sale_date DESC, s.sale_id DESC
");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group orders by sale date for better display
$grouped_orders = [];
foreach ($orders as $order) {
    $date = $order['sale_date'];
    if (!isset($grouped_orders[$date])) {
        $grouped_orders[$date] = [];
    }
    $grouped_orders[$date][] = $order;
}

// Calculate totals
$total_orders = count($orders);
$total_items = 0;
$total_spent = 0;
foreach ($orders as $order) {
    $total_items += $order['quantity'];
    $total_spent += $order['quantity'] * $order['price_at_sale'];
}

// Check for checkout success message
$checkout_success = false;
if (isset($_SESSION['checkout_success']) && $_SESSION['checkout_success']) {
    $checkout_success = true;
    $order_total = $_SESSION['order_total'] ?? 0;
    unset($_SESSION['checkout_success']);
    unset($_SESSION['order_total']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Online Bookstore</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .orders-container {
            margin-top: 30px;
        }
        
        .order-group {
            background: white;
            border: 1px solid #e0e6ed;
            border-radius: 10px;
            margin-bottom: 25px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        
        .order-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #e0e6ed;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .order-header h3 {
            margin: 0;
            color: #2c3e50;
        }
        
        .order-date {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .order-total {
            font-weight: bold;
            color: #27ae60;
            font-size: 18px;
        }
        
        .order-items {
            padding: 20px;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 15px;
            border-bottom: 1px solid #f1f1f1;
            align-items: center;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .item-info {
            flex: 2;
        }
        
        .item-info h4 {
            margin: 0 0 5px 0;
            color: #2c3e50;
        }
        
        .item-details {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .item-quantity, .item-price, .item-total {
            flex: 1;
            text-align: center;
        }
        
        .order-summary-stats {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            border: 1px solid #e0e6ed;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .stat-item {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e0e6ed;
            text-align: center;
        }
        
        .stat-label {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #3498db;
        }
        
        .empty-orders {
            text-align: center;
            padding: 50px 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 2px dashed #dee2e6;
        }
        
        .empty-orders h3 {
            color: #7f8c8d;
            margin-bottom: 15px;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #28a745;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .success-icon {
            font-size: 24px;
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
                <a href="cart.php">Cart (<?php echo getCartCount($pdo, $user_id); ?>)</a>
                <a href="orders.php">My Orders</a>
                <a href="profile.php">My Profile</a>
                <a href="../logout.php">Logout</a>
            </nav>
        </header>
        
        <main>
            <h2>My Orders</h2>
            
            <?php if ($checkout_success): ?>
            <div class="success-message">
                <span class="success-icon">✅</span>
                <div>
                    <h3>Order Placed Successfully!</h3>
                    <p>Your order has been confirmed and will be processed shortly. Total amount: <strong>$<?php echo number_format($order_total, 2); ?></strong></p>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="order-summary-stats">
                <h3>Order Summary</h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-label">Total Orders</div>
                        <div class="stat-value"><?php echo $total_orders; ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Books Purchased</div>
                        <div class="stat-value"><?php echo $total_items; ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Total Spent</div>
                        <div class="stat-value">$<?php echo number_format($total_spent, 2); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Average Order</div>
                        <div class="stat-value">
                            $<?php echo $total_orders > 0 ? number_format($total_spent / $total_orders, 2) : '0.00'; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (empty($orders)): ?>
            <div class="empty-orders">
                <h3>No Orders Yet</h3>
                <p>You haven't placed any orders yet. Start shopping now!</p>
                <div style="margin-top: 20px;">
                    <a href="search.php" class="btn btn-primary">Browse Books</a>
                    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                </div>
            </div>
            <?php else: ?>
            <div class="orders-container">
                <?php foreach ($grouped_orders as $date => $date_orders): 
                    // Calculate total for this date
                    $date_total = 0;
                    foreach ($date_orders as $order) {
                        $date_total += $order['quantity'] * $order['price_at_sale'];
                    }
                ?>
                <div class="order-group" id="order-<?php echo str_replace('-', '', $date); ?>">
                    <div class="order-header">
                        <div>
                            <h3>Order Date: <?php echo $date; ?></h3>
                            <span class="order-date">Order #<?php echo $date_orders[0]['sale_id']; ?></span>
                        </div>
                        <div class="order-total">Total: $<?php echo number_format($date_total, 2); ?></div>
                    </div>
                    
                    <div class="order-items">
                        <?php foreach ($date_orders as $order): 
                            $item_total = $order['quantity'] * $order['price_at_sale'];
                        ?>
                        <div class="order-item">
                            <div class="item-info">
                                <h4><?php echo htmlspecialchars($order['title']); ?></h4>
                                <div class="item-details">
                                    <span>ISBN: <?php echo $order['ISBNID']; ?></span> | 
                                    <span>Category: <?php echo $order['category']; ?></span>
                                </div>
                            </div>
                            <div class="item-quantity">
                                <span class="label">Qty:</span>
                                <span class="value"><?php echo $order['quantity']; ?></span>
                            </div>
                            <div class="item-price">
                                <span class="label">Price:</span>
                                <span class="value">$<?php echo number_format($order['price_at_sale'], 2); ?></span>
                            </div>
                            <div class="item-total">
                                <span class="label">Total:</span>
                                <span class="value">$<?php echo number_format($item_total, 2); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div style="padding: 15px 20px; background: #f1f8e9; border-top: 1px solid #e0e6ed;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong>Shipping Address:</strong>
                                <p style="margin: 5px 0 0 0; color: #555;"><?php echo nl2br(htmlspecialchars($order['address'])); ?></p>
                            </div>
                            <div style="text-align: right;">
                                <div style="margin-bottom: 5px;">
                                    <strong>Order Status:</strong>
                                    <span style="color: #27ae60; font-weight: bold;">Completed</span>
                                </div>
                                <div>
                                    <strong>Payment Method:</strong>
                                    <span>Credit Card</span>
                                </div>
                                <div>
                                    <strong>Order Total:</strong>
                                    <span style="font-weight: bold; color: #27ae60;">$<?php echo number_format($date_total, 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="pagination" style="text-align: center; margin-top: 30px;">
                <p>Showing all <?php echo $total_orders; ?> orders - Total Spent: <strong>$<?php echo number_format($total_spent, 2); ?></strong></p>
            </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script>
    // Scroll to the latest order if checkout was just completed
    <?php if ($checkout_success && !empty($orders)): ?>
    document.addEventListener('DOMContentLoaded', function() {
        const latestOrder = document.querySelector('.order-group');
        if (latestOrder) {
            latestOrder.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
    <?php endif; ?>
    </script>
</body>
</html>