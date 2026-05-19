<?php
// Database connection function
function getPDO() {
    static $pdo = null;
    if ($pdo === null) {
        $host = 'localhost';
        $dbname = 'online_bookstore';
        $username = 'root';
        $password = '';
        
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed.");
        }
    }
    return $pdo;
}

// ========== BOOK FUNCTIONS ==========
function getBookById($pdo, $isbn) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM Book WHERE ISBNID = ?");
        $stmt->execute([$isbn]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error in getBookById: " . $e->getMessage());
        return null;
    }
}

function searchBooks($pdo, $keyword, $category = null, $author = null, $publisher = null) {
    try {
        $sql = "SELECT b.* FROM Book b WHERE 1=1";
        $params = [];
        
        if (!empty($keyword)) {
            $sql .= " AND (b.title LIKE ? OR b.ISBNID LIKE ? OR b.category LIKE ?)";
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
        }
        
        if (!empty($category)) {
            $sql .= " AND b.category = ?";
            $params[] = $category;
        }
        
        // For author and publisher, we need to join with other tables
        if (!empty($author) || !empty($publisher)) {
            if (!empty($author)) {
                $sql .= " AND b.ISBNID IN (SELECT ba.ISBNID FROM Book_Authors ba JOIN Author a ON ba.AID = a.AID WHERE a.name LIKE ?)";
                $params[] = "%$author%";
            }
            
            if (!empty($publisher)) {
                $sql .= " AND b.PID IN (SELECT p.PID FROM Publisher p WHERE p.name LIKE ?)";
                $params[] = "%$publisher%";
            }
        }
        
        $sql .= " ORDER BY b.title";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error in searchBooks: " . $e->getMessage());
        return [];
    }
}

// ========== CART FUNCTIONS ==========
function getCartItems($pdo, $user_id) {
    try {
        $sql = "SELECT c.*, b.title, b.price, b.quantity as stock 
                FROM Cart c 
                JOIN Book b ON c.ISBNID = b.ISBNID 
                WHERE c.customer_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error in getCartItems: " . $e->getMessage());
        return [];
    }
}

function getCartTotal($pdo, $user_id) {
    $items = getCartItems($pdo, $user_id);
    $total = 0;
    foreach ($items as $item) {
        $total += $item['quantity'] * $item['price'];
    }
    return $total;
}

function addToCart($pdo, $user_id, $isbn, $quantity = 1) {
    try {
        // Check if book exists and has stock
        $stmt = $pdo->prepare("SELECT * FROM Book WHERE ISBNID = ?");
        $stmt->execute([$isbn]);
        $book = $stmt->fetch();
        
        if (!$book) {
            return "Book not found.";
        }
        
        if ($book['quantity'] < $quantity) {
            return "Only {$book['quantity']} copies available.";
        }
        
        // Check if already in cart
        $stmt = $pdo->prepare("SELECT * FROM Cart WHERE customer_id = ? AND ISBNID = ?");
        $stmt->execute([$user_id, $isbn]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update quantity
            $new_quantity = $existing['quantity'] + $quantity;
            if ($book['quantity'] < $new_quantity) {
                return "Cannot add more. Only {$book['quantity']} available.";
            }
            
            $stmt = $pdo->prepare("UPDATE Cart SET quantity = ? WHERE customer_id = ? AND ISBNID = ?");
            $stmt->execute([$new_quantity, $user_id, $isbn]);
        } else {
            // Insert new
            $stmt = $pdo->prepare("INSERT INTO Cart (customer_id, ISBNID, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $isbn, $quantity]);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error in addToCart: " . $e->getMessage());
        return "Error: " . $e->getMessage();
    }
}

function removeFromCart($pdo, $user_id, $isbn) {
    try {
        $stmt = $pdo->prepare("DELETE FROM Cart WHERE customer_id = ? AND ISBNID = ?");
        return $stmt->execute([$user_id, $isbn]);
    } catch (Exception $e) {
        error_log("Error in removeFromCart: " . $e->getMessage());
        return false;
    }
}

function updateCartQuantity($pdo, $user_id, $isbn, $quantity) {
    try {
        if ($quantity <= 0) {
            return removeFromCart($pdo, $user_id, $isbn);
        }
        
        // Check stock
        $stmt = $pdo->prepare("SELECT quantity FROM Book WHERE ISBNID = ?");
        $stmt->execute([$isbn]);
        $book = $stmt->fetch();
        
        if ($book && $quantity <= $book['quantity']) {
            $stmt = $pdo->prepare("UPDATE Cart SET quantity = ? WHERE customer_id = ? AND ISBNID = ?");
            return $stmt->execute([$quantity, $user_id, $isbn]);
        }
        return false;
    } catch (Exception $e) {
        error_log("Error in updateCartQuantity: " . $e->getMessage());
        return false;
    }
}

function clearCart($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM Cart WHERE customer_id = ?");
        return $stmt->execute([$user_id]);
    } catch (Exception $e) {
        error_log("Error in clearCart: " . $e->getMessage());
        return false;
    }
}

function getCartCount($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) as total FROM Cart WHERE customer_id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result['total'] ?: 0;
    } catch (Exception $e) {
        error_log("Error in getCartCount: " . $e->getMessage());
        return 0;
    }
}

// ========== CHECKOUT FUNCTIONS ==========
function processCheckout($pdo, $user_id) {
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Get cart items with FOR UPDATE lock
        $sql = "SELECT c.*, b.title, b.price, b.quantity as stock 
                FROM Cart c 
                JOIN Book b ON c.ISBNID = b.ISBNID 
                WHERE c.customer_id = ? 
                FOR UPDATE";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $cart_items = $stmt->fetchAll();
        
        if (empty($cart_items)) {
            throw new Exception("Your cart is empty.");
        }
        
        // Process each item
        foreach ($cart_items as $item) {
            // Check stock
            if ($item['stock'] < $item['quantity']) {
                throw new Exception("Insufficient stock for '{$item['title']}'. Only {$item['stock']} available.");
            }
            
            // Create sale record
            $sql = "INSERT INTO Sales (user_id, ISBNID, sale_date, quantity, price_at_sale) 
                    VALUES (?, ?, NOW(), ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $item['ISBNID'], $item['quantity'], $item['price']]);
            
            // Update book quantity
            $sql = "UPDATE Book SET quantity = quantity - ? WHERE ISBNID = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$item['quantity'], $item['ISBNID']]);
            
            if ($stmt->rowCount() == 0) {
                throw new Exception("Failed to update stock for '{$item['title']}'.");
            }
        }
        
        // Clear cart
        $sql = "DELETE FROM Cart WHERE customer_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        
        // Commit transaction
        $pdo->commit();
        
        // Update session
        $_SESSION['cart_count'] = 0;
        
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Checkout error: " . $e->getMessage());
        return $e->getMessage();
    }
}

// ========== ORDER FUNCTIONS ==========
function getCustomerOrders($pdo, $user_id) {
    try {
        $sql = "SELECT s.*, b.title 
                FROM Sales s 
                JOIN Book b ON s.ISBNID = b.ISBNID 
                WHERE s.user_id = ? 
                ORDER BY s.sale_date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error in getCustomerOrders: " . $e->getMessage());
        return [];
    }
}

function getTotalSpent($pdo, $user_id) {
    try {
        $sql = "SELECT SUM(quantity * price_at_sale) as total_spent 
                FROM Sales 
                WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result['total_spent'] ?: 0;
    } catch (Exception $e) {
        error_log("Error in getTotalSpent: " . $e->getMessage());
        return 0;
    }
}

// ========== USER FUNCTIONS ==========
function getCustomerInfo($pdo, $user_id) {
    try {
        $sql = "SELECT u.*, up.* 
                FROM Users u 
                JOIN UserProfile up ON u.user_id = up.user_id 
                WHERE u.user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error in getCustomerInfo: " . $e->getMessage());
        return null;
    }
}
?>