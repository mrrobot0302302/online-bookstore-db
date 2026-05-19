<?php
require_once '../includes/config.php';
requireAdmin();

$report_data = [];
$report_type = '';
$report_date = '';
$report_isbn = '';

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['report_type'])) {
    $report_type = $_GET['report_type'];
    $report_date = $_GET['date'] ?? date('Y-m-d');
    $report_isbn = $_GET['isbn'] ?? '';
    
    switch ($report_type) {
        case 'monthly_sales':
            $report_data = getPreviousMonthSales($pdo);
            break;
            
        case 'daily_sales':
            $report_data = getDailySales($pdo, $report_date);
            break;
            
        case 'top_customers':
            $report_data = getTopCustomers($pdo, 3);
            break;
            
        case 'top_books':
            $report_data = getTopSellingBooks($pdo, 3);
            break;
            
        case 'book_orders':
            if (!empty($report_isbn)) {
                $order_count = getBookOrderCount($pdo, $report_isbn);
                $report_data['order_count'] = $order_count;
                $report_data['orders'] = $pdo->prepare("
                    SELECT po.*, b.title, p.name as publisher_name
                    FROM Publisher_Order po
                    JOIN Book b ON po.ISBNID = b.ISBNID
                    JOIN Publisher p ON po.PID = p.PID
                    WHERE po.ISBNID = ?
                    ORDER BY po.order_date DESC
                ")->execute([$report_isbn])->fetchAll(PDO::FETCH_ASSOC);
            }
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Reports - Admin Dashboard</title>
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
            <h2>System Reports</h2>
            <p class="page-description">
                Generate various reports as specified in Requirement 6.
            </p>
            
            <div class="reports-section">
                <div class="report-selector">
                    <h3>Select Report Type</h3>
                    <form method="GET" action="" class="report-form">
                        <div class="form-group">
                            <label for="report_type">Report Type:</label>
                            <select name="report_type" id="report_type" onchange="toggleReportOptions()">
                                <option value="">-- Select a report --</option>
                                <option value="monthly_sales" <?php echo $report_type == 'monthly_sales' ? 'selected' : ''; ?>>
                                    a) Monthly Sales Report
                                </option>
                                <option value="daily_sales" <?php echo $report_type == 'daily_sales' ? 'selected' : ''; ?>>
                                    b) Daily Sales Report
                                </option>
                                <option value="top_customers" <?php echo $report_type == 'top_customers' ? 'selected' : ''; ?>>
                                    c) Top 5 Customers (Last 3 Months)
                                </option>
                                <option value="top_books" <?php echo $report_type == 'top_books' ? 'selected' : ''; ?>>
                                    d) Top 10 Selling Books (Last 3 Months)
                                </option>
                                <option value="book_orders" <?php echo $report_type == 'book_orders' ? 'selected' : ''; ?>>
                                    e) Book Replenishment Orders
                                </option>
                            </select>
                        </div>
                        
                        <div id="report_options">
                            <?php if ($report_type == 'daily_sales'): ?>
                            <div class="form-group">
                                <label for="date">Select Date:</label>
                                <input type="date" name="date" value="<?php echo $report_date; ?>">
                            </div>
                            <?php elseif ($report_type == 'book_orders'): ?>
                            <div class="form-group">
                                <label for="isbn">Book ISBN:</label>
                                <input type="text" name="isbn" value="<?php echo $report_isbn; ?>" 
                                       placeholder="Enter ISBN (e.g., B001)" required>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Generate Report</button>
                    </form>
                </div>
                
                <?php if (!empty($report_type) && !empty($report_data)): ?>
                <div class="report-results">
                    <h3>Report Results</h3>
                    
                    <?php switch ($report_type): 
                        case 'monthly_sales': ?>
                            <div class="report-summary">
                                <h4>Monthly Sales Report (Previous Month)</h4>
                                <?php if (!empty($report_data)): ?>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Month</th>
                                            <th>Total Sales</th>
                                            <th>Books Sold</th>
                                            <th>Transactions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($report_data as $row): ?>
                                        <tr>
                                            <td><?php echo $row['month']; ?></td>
                                            <td>$<?php echo number_format($row['total_sales'], 2); ?></td>
                                            <td><?php echo $row['total_books_sold']; ?></td>
                                            <td><?php echo $row['total_transactions']; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php else: ?>
                                <p>No sales data for the previous month.</p>
                                <?php endif; ?>
                            </div>
                            <?php break; ?>
                            
                        <?php case 'daily_sales': ?>
                            <div class="report-summary">
                                <h4>Daily Sales Report for <?php echo $report_date; ?></h4>
                                <?php if (!empty($report_data)): 
                                    $daily_total = 0;
                                    $daily_books = 0;
                                ?>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Time</th>
                                            <th>Customer</th>
                                            <th>Book</th>
                                            <th>Quantity</th>
                                            <th>Unit Price</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($report_data as $row): 
                                            $total = $row['quantity'] * $row['price_at_sale'];
                                            $daily_total += $total;
                                            $daily_books += $row['quantity'];
                                        ?>
                                        <tr>
                                            <td><?php echo date('H:i', strtotime($row['sale_date'])); ?></td>
                                            <td><?php echo $row['customer_name']; ?></td>
                                            <td><?php echo $row['title']; ?></td>
                                            <td><?php echo $row['quantity']; ?></td>
                                            <td>$<?php echo number_format($row['price_at_sale'], 2); ?></td>
                                            <td>$<?php echo number_format($total, 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr class="total-row">
                                            <td colspan="3"><strong>Daily Summary:</strong></td>
                                            <td><strong><?php echo $daily_books; ?> books</strong></td>
                                            <td colspan="2"><strong>Total: $<?php echo number_format($daily_total, 2); ?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                                <?php else: ?>
                                <p>No sales on <?php echo $report_date; ?>.</p>
                                <?php endif; ?>
                            </div>
                            <?php break; ?>
                            
                        <?php case 'top_customers': ?>
                            <div class="report-summary">
                                <h4>Top 5 Customers (Last 3 Months)</h4>
                                <?php if (!empty($report_data)): ?>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Rank</th>
                                            <th>Customer</th>
                                            <th>Username</th>
                                            <th>Orders</th>
                                            <th>Books Purchased</th>
                                            <th>Total Spent</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $rank = 1; foreach ($report_data as $row): ?>
                                        <tr>
                                            <td><?php echo $rank++; ?></td>
                                            <td><?php echo $row['customer_name']; ?></td>
                                            <td><?php echo $row['username']; ?></td>
                                            <td><?php echo $row['total_orders']; ?></td>
                                            <td><?php echo $row['total_books_purchased']; ?></td>
                                            <td>$<?php echo number_format($row['total_spent'] ?? 0, 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php else: ?>
                                <p>No customer data for the last 3 months.</p>
                                <?php endif; ?>
                            </div>
                            <?php break; ?>
                            
                        <?php case 'top_books': ?>
                            <div class="report-summary">
                                <h4>Top 10 Selling Books (Last 3 Months)</h4>
                                <?php if (!empty($report_data)): ?>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Rank</th>
                                            <th>Book Title</th>
                                            <th>ISBN</th>
                                            <th>Category</th>
                                            <th>Price</th>
                                            <th>Copies Sold</th>
                                            <th>Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $rank = 1; foreach ($report_data as $row): ?>
                                        <tr>
                                            <td><?php echo $rank++; ?></td>
                                            <td><?php echo $row['title']; ?></td>
                                            <td><?php echo $row['ISBNID']; ?></td>
                                            <td><?php echo $row['category']; ?></td>
                                            <td>$<?php echo number_format($row['price'], 2); ?></td>
                                            <td><?php echo $row['total_sold'] ?? 0; ?></td>
                                            <td>$<?php echo number_format($row['total_revenue'] ?? 0, 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php else: ?>
                                <p>No book sales data for the last 3 months.</p>
                                <?php endif; ?>
                            </div>
                            <?php break; ?>
                            
                        <?php case 'book_orders': ?>
                            <div class="report-summary">
                                <h4>Book Replenishment Orders for ISBN: <?php echo $report_isbn; ?></h4>
                                <?php if (!empty($report_data['order_count'])): 
                                    $count = $report_data['order_count'];
                                ?>
                                <div class="order-summary-card">
                                    <h5>Order Summary</h5>
                                    <p><strong>Total Orders:</strong> <?php echo $count['order_count']; ?></p>
                                    <p><strong>Total Quantity Ordered:</strong> <?php echo $count['total_ordered']; ?></p>
                                    <p><strong>First Order:</strong> <?php echo $count['first_order']; ?></p>
                                    <p><strong>Last Order:</strong> <?php echo $count['last_order']; ?></p>
                                </div>
                                
                                <?php if (!empty($report_data['orders'])): ?>
                                <h5>Order Details</h5>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Book Title</th>
                                            <th>Publisher</th>
                                            <th>Order Date</th>
                                            <th>Quantity</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($report_data['orders'] as $row): ?>
                                        <tr>
                                            <td><?php echo $row['order_id']; ?></td>
                                            <td><?php echo $row['title']; ?></td>
                                            <td><?php echo $row['publisher_name']; ?></td>
                                            <td><?php echo $row['order_date']; ?></td>
                                            <td><?php echo $row['quantity']; ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($row['status']); ?>">
                                                    <?php echo $row['status']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php endif; ?>
                                <?php else: ?>
                                <p>No replenishment orders found for ISBN <?php echo $report_isbn; ?>.</p>
                                <?php endif; ?>
                            </div>
                            <?php break; ?>
                            
                    <?php endswitch; ?>
                </div>
                <?php elseif (!empty($report_type) && empty($report_data)): ?>
                <div class="report-results">
                    <p>No data found for this report.</p>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
    function toggleReportOptions() {
        const reportType = document.getElementById('report_type').value;
        const optionsDiv = document.getElementById('report_options');
        
        let optionsHTML = '';
        
        if (reportType === 'daily_sales') {
            optionsHTML = `
                <div class="form-group">
                    <label for="date">Select Date:</label>
                    <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>">
                </div>
            `;
        } else if (reportType === 'book_orders') {
            optionsHTML = `
                <div class="form-group">
                    <label for="isbn">Book ISBN:</label>
                    <input type="text" name="isbn" placeholder="Enter ISBN (e.g., B001)" required>
                </div>
            `;
        }
        
        optionsDiv.innerHTML = optionsHTML;
    }
    
    // Initialize options if page was loaded with a report type
    document.addEventListener('DOMContentLoaded', function() {
        toggleReportOptions();
    });
    </script>
</body>
</html>