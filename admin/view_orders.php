<?php
require_once '../includes/config.php';
requireAdmin();

// Get all sales orders
$orders = $pdo->query("
    SELECT 
        s.sale_id,
        s.sale_date,
        s.quantity,
        s.price_at_sale,
        b.title,
        b.ISBNID,
        u.username,
        CONCAT(up.first_name, ' ', up.last_name) as customer_name,
        up.email,
        up.phone
    FROM Sales s
    JOIN Book b ON s.ISBNID = b.ISBNID
    JOIN Users u ON s.user_id = u.user_id
    JOIN UserProfile up ON u.user_id = up.user_id
    ORDER BY s.sale_date DESC, s.sale_id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$total_sales = 0;
$total_items = 0;
foreach ($orders as $order) {
    $total_sales += $order['quantity'] * $order['price_at_sale'];
    $total_items += $order['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Orders - Admin Dashboard</title>
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
            <h2>Sales Orders History</h2>
            
            <div class="summary-stats">
                <div class="stat-card">
                    <h3>Total Orders</h3>
                    <p class="stat-number"><?php echo count($orders); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Items Sold</h3>
                    <p class="stat-number"><?php echo $total_items; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Revenue</h3>
                    <p class="stat-number">$<?php echo number_format($total_sales, 2); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Avg. Order Value</h3>
                    <p class="stat-number">
                        $<?php echo count($orders) > 0 ? number_format($total_sales / count($orders), 2) : '0.00'; ?>
                    </p>
                </div>
            </div>
            
            <?php if (empty($orders)): ?>
                <p>No sales orders found.</p>
            <?php else: ?>
                <div class="orders-section">
                    <div class="section-header">
                        <h3>All Sales Orders</h3>
                        <div class="filter-options">
                            <button onclick="filterOrders('all')" class="btn btn-small">All</button>
                            <button onclick="filterOrders('today')" class="btn btn-small">Today</button>
                            <button onclick="filterOrders('week')" class="btn btn-small">This Week</button>
                        </div>
                    </div>
                    
                    <table id="ordersTable">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Book</th>
                                <th>ISBN</th>
                                <th>Qty</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): 
                                $order_total = $order['quantity'] * $order['price_at_sale'];
                            ?>
                            <tr data-date="<?php echo $order['sale_date']; ?>">
                                <td><?php echo $order['sale_id']; ?></td>
                                <td><?php echo $order['sale_date']; ?></td>
                                <td>
                                    <?php echo $order['customer_name']; ?><br>
                                    <small>@<?php echo $order['username']; ?></small>
                                </td>
                                <td><?php echo $order['email']; ?></td>
                                <td><?php echo $order['title']; ?></td>
                                <td><?php echo $order['ISBNID']; ?></td>
                                <td><?php echo $order['quantity']; ?></td>
                                <td>$<?php echo number_format($order['price_at_sale'], 2); ?></td>
                                <td>$<?php echo number_format($order_total, 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script>
    function filterOrders(filterType) {
        const rows = document.querySelectorAll('#ordersTable tbody tr');
        const today = new Date().toISOString().split('T')[0];
        const oneWeekAgo = new Date();
        oneWeekAgo.setDate(oneWeekAgo.getDate() - 7);
        const oneWeekAgoStr = oneWeekAgo.toISOString().split('T')[0];
        
        rows.forEach(row => {
            const date = row.getAttribute('data-date');
            let show = true;
            
            switch(filterType) {
                case 'today':
                    show = date === today;
                    break;
                case 'week':
                    show = date >= oneWeekAgoStr;
                    break;
                default:
                    show = true;
            }
            
            row.style.display = show ? '' : 'none';
        });
    }
    </script>
</body>
</html>