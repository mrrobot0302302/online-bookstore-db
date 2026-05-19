<?php
require_once '../includes/config.php';
requireCustomer();

$user_id = $_SESSION['user_id'];

// Get cart items
$cart_items = getCartItems($pdo, $user_id);
$cart_total = getCartTotal($pdo, $user_id);

// Handle remove from cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_item'])) {
    $isbn = $_POST['isbn'];
    removeFromCart($pdo, $user_id, $isbn);
    header('Location: cart.php');
    exit();
}

// Handle update quantity
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_quantity'])) {
    $isbn = $_POST['isbn'];
    $quantity = (int)$_POST['quantity'];
    
    if ($quantity <= 0) {
        removeFromCart($pdo, $user_id, $isbn);
    } else {
        // Check stock availability
        $book = getBookById($pdo, $isbn);
        if ($book && $quantity <= $book['quantity']) {
            updateCartQuantity($pdo, $user_id, $isbn, $quantity);
        } else {
            $_SESSION['cart_error'] = "Cannot update quantity: Only {$book['quantity']} copies available for '{$book['title']}'.";
        }
    }
    header('Location: cart.php');
    exit();
}

// Handle clear cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['clear_cart'])) {
    clearCart($pdo, $user_id);
    header('Location: cart.php');
    exit();
}

// Check for success messages
if (isset($_SESSION['add_to_cart_success'])) {
    $success = $_SESSION['add_to_cart_success'];
    unset($_SESSION['add_to_cart_success']);
}

// Check for error messages
if (isset($_SESSION['cart_error'])) {
    $error = $_SESSION['cart_error'];
    unset($_SESSION['cart_error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart - Online Bookstore</title>
    <link rel="stylesheet" href="../css/style.css">
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
            <h2>Shopping Cart</h2>
            
            <?php if (isset($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (empty($cart_items)): ?>
                <div class="empty-state">
                    <h3>Your Cart is Empty</h3>
                    <p>You haven't added any books to your cart yet.</p>
                    <div class="empty-actions">
                        <a href="search.php" class="btn btn-primary">Browse Books</a>
                        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="cart-summary">
                    <h3>Cart Summary</h3>
                    <div class="summary-grid">
                        <div class="summary-item">
                            <div class="summary-label">Total Items</div>
                            <div class="summary-value"><?php echo array_sum(array_column($cart_items, 'quantity')); ?></div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-label">Unique Books</div>
                            <div class="summary-value"><?php echo count($cart_items); ?></div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-label">Cart Total</div>
                            <div class="summary-value">$<?php echo number_format($cart_total, 2); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="cart-items">
                    <h3>Cart Items</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Book Title</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Available Stock</th>
                                <th>Item Total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $item): 
                                $item_total = $item['quantity'] * $item['price'];
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['title']); ?></strong><br>
                                    <small>ISBN: <?php echo $item['ISBNID']; ?></small>
                                </td>
                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                <td>
                                    <form method="POST" action="" class="quantity-controls">
                                        <input type="hidden" name="isbn" value="<?php echo $item['ISBNID']; ?>">
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                               min="1" max="<?php echo $item['stock']; ?>">
                                        <button type="submit" name="update_quantity" class="btn btn-small">
                                            Update
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <?php 
                                    echo $item['stock'];
                                    if ($item['stock'] < $item['quantity']) {
                                        echo ' <span class="stock-warning">(Insufficient)</span>';
                                    }
                                    ?>
                                </td>
                                <td>$<?php echo number_format($item_total, 2); ?></td>
                                <td>
                                    <form method="POST" action="" onsubmit="return confirm('Remove this item from cart?');">
                                        <input type="hidden" name="isbn" value="<?php echo $item['ISBNID']; ?>">
                                        <button type="submit" name="remove_item" class="btn btn-small btn-danger">
                                            Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="total-row">
                                <td colspan="4" style="text-align: right;"><strong>Cart Total:</strong></td>
                                <td colspan="2"><strong>$<?php echo number_format($cart_total, 2); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="cart-actions">
                        <div class="action-group">
                            <form method="POST" action="" onsubmit="return confirm('Clear all items from cart?');">
                                <button type="submit" name="clear_cart" class="btn btn-danger">
                                    Clear Cart
                                </button>
                            </form>
                            <a href="search.php" class="btn btn-secondary">Continue Shopping</a>
                        </div>
                        <div class="checkout-group">
                            <?php 
                            // Check if any items have insufficient stock
                            $can_checkout = true;
                            foreach ($cart_items as $item) {
                                if ($item['stock'] < $item['quantity']) {
                                    $can_checkout = false;
                                    break;
                                }
                            }
                            ?>
                            <?php if (!$can_checkout): ?>
                                <div class="warning-message">
                                    <p>Some items in your cart have insufficient stock. Please update quantities before checkout.</p>
                                </div>
                            <?php else: ?>
                                <a href="checkout.php" class="btn btn-primary btn-large">
                                    Proceed to Checkout
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>