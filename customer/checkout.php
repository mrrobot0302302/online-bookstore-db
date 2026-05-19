<?php
require_once '../includes/config.php';
requireCustomer();

$user_id = $_SESSION['user_id'];

// Get cart items
$cart_items = getCartItems($pdo, $user_id);
$cart_total = getCartTotal($pdo, $user_id);

// Handle checkout
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['checkout'])) {
    // SIMPLE VALIDATION - Just check if fields are filled
    $errors = [];
    
    if (empty($_POST['card_number'])) {
        $errors[] = "Card number is required.";
    }
    
    if (empty($_POST['expiry_date'])) {
        $errors[] = "Expiry date is required.";
    }
    
    if (empty($_POST['cvv'])) {
        $errors[] = "CVV is required.";
    }
    
    if (empty($_POST['card_holder'])) {
        $errors[] = "Card holder name is required.";
    }
    
    // Check stock
    foreach ($cart_items as $item) {
        if ($item['stock'] < $item['quantity']) {
            $errors[] = "Insufficient stock for '{$item['title']}'. Only {$item['stock']} copies available.";
        }
    }
    
    if (empty($errors)) {
        // Process checkout
        $result = processCheckout($pdo, $user_id);
        
        if ($result === true) {
            $_SESSION['checkout_success'] = true;
            $_SESSION['order_total'] = $cart_total;
            header('Location: orders.php');
            exit();
        } else {
            $error = "Checkout failed: " . $result;
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Redirect if cart is empty
if (empty($cart_items)) {
    header('Location: cart.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Online Bookstore</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .checkout-container {
            display: flex;
            gap: 30px;
            margin-top: 20px;
        }
        .order-summary {
            flex: 1;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
        }
        .payment-form {
            flex: 1;
            background: white;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #ddd;
        }
        .cart-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .total-row {
            font-weight: bold;
            font-size: 1.2em;
            color: #27ae60;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .btn-large {
            padding: 15px 30px;
            font-size: 1.1em;
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
            <h2>Checkout</h2>
            
            <?php if (isset($error)): ?>
                <div class="error" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 15px 0;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="checkout-container">
                <div class="order-summary">
                    <h3>Order Summary</h3>
                    <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item">
                        <div>
                            <strong><?php echo htmlspecialchars($item['title']); ?></strong><br>
                            <small>Qty: <?php echo $item['quantity']; ?> × $<?php echo number_format($item['price'], 2); ?></small>
                        </div>
                        <div>$<?php echo number_format($item['quantity'] * $item['price'], 2); ?></div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="cart-item total-row">
                        <div>Total:</div>
                        <div>$<?php echo number_format($cart_total, 2); ?></div>
                    </div>
                    
                    <div style="margin-top: 20px;">
                        <h4>Shipping Address</h4>
                        <?php 
                        $stmt = $pdo->prepare("SELECT address FROM UserProfile WHERE user_id = ?");
                        $stmt->execute([$user_id]);
                        $address = $stmt->fetch();
                        ?>
                        <p><?php echo nl2br(htmlspecialchars($address['address'] ?? 'No address provided')); ?></p>
                        <a href="profile.php" class="btn btn-small" style="margin-top: 10px;">Update Address</a>
                    </div>
                </div>
                
                <div class="payment-form">
                    <h3>Payment Details</h3>
                    <form method="POST" action="">
                        <div>
                            <label>Card Holder Name *</label>
                            <input type="text" name="card_holder" placeholder="John Doe" required>
                        </div>
                        
                        <div>
                            <label>Card Number *</label>
                            <input type="text" name="card_number" placeholder="4111 1111 1111 1111" required>
                        </div>
                        
                        <div style="display: flex; gap: 15px;">
                            <div style="flex: 1;">
                                <label>Expiry Date (MM/YY) *</label>
                                <input type="text" name="expiry_date" placeholder="12/25" required>
                            </div>
                            <div style="flex: 1;">
                                <label>CVV *</label>
                                <input type="text" name="cvv" placeholder="123" required>
                            </div>
                        </div>
                        
                        <div style="margin-top: 30px;">
                            <a href="cart.php" class="btn btn-secondary">Back to Cart</a>
                            <button type="submit" name="checkout" class="btn btn-primary btn-large">
                                Pay $<?php echo number_format($cart_total, 2); ?>
                            </button>
                        </div>
                    </form>
                    
                    <div style="margin-top: 20px; padding: 15px; background: #e8f4fd; border-radius: 5px;">
                        <h4>Test Card Details</h4>
                        <p>For testing, use:<br>
                        Card: <strong>4111111111111111</strong><br>
                        Expiry: <strong>12/30</strong><br>
                        CVV: <strong>123</strong><br>
                        Name: <strong>Any name</strong></p>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
    // Simple form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const btn = this.querySelector('button[type="submit"]');
        btn.innerHTML = 'Processing Payment...';
        btn.disabled = true;
        return true;
    });
    </script>
</body>
</html>