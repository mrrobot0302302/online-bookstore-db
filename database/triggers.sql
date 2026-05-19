USE online_bookstore;

DELIMITER $$

-- 1. Prevent negative stock (Requirement 2c)
CREATE TRIGGER prevent_negative_stock
BEFORE UPDATE ON Book
FOR EACH ROW
BEGIN
    IF NEW.quantity < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Stock quantity cannot be negative';
    END IF;
END$$

-- 2. Auto reorder when stock drops below threshold (Requirement 3)
CREATE TRIGGER auto_reorder_book
AFTER UPDATE ON Book
FOR EACH ROW
BEGIN
    IF OLD.quantity >= OLD.threshold
       AND NEW.quantity < OLD.threshold THEN
        INSERT INTO Publisher_Order
        (ISBNID, PID, order_date, quantity, status)
        VALUES
        (NEW.ISBNID, NEW.PID, CURDATE(), 20, 'Pending');
    END IF;
END$$

-- 3. Confirm order adds stock (Requirement 4)
CREATE TRIGGER confirm_order_add_stock
BEFORE UPDATE ON Publisher_Order
FOR EACH ROW
BEGIN
    IF OLD.status = 'Pending'
       AND NEW.status = 'Confirmed' THEN
        UPDATE Book
        SET quantity = quantity + OLD.quantity
        WHERE ISBNID = OLD.ISBNID;
    END IF;
END$$

-- 4. Handle customer checkout - Update stock and create sales record (Requirement Part 2.4)
CREATE TRIGGER process_checkout
AFTER INSERT ON Sales
FOR EACH ROW
BEGIN
    -- Deduct purchased quantity from stock
    UPDATE Book
    SET quantity = quantity - NEW.quantity
    WHERE ISBNID = NEW.ISBNID;
    
    -- Clear this book from customer's cart
    DELETE FROM Cart 
    WHERE customer_id = NEW.user_id 
    AND ISBNID = NEW.ISBNID;
END$$

-- 5. Clear cart on user logout (simulated by deleting cart items) (Requirement Part 2.6)
-- Note: In a real app, this would be triggered by logout action
-- This provides a way to manually clear cart via SQL if needed
CREATE TRIGGER clear_cart_on_user_delete
BEFORE DELETE ON Users
FOR EACH ROW
BEGIN
    DELETE FROM Cart WHERE customer_id = OLD.user_id;
END$$

-- 6. Prevent selling more than available stock
CREATE TRIGGER prevent_overselling
BEFORE INSERT ON Sales
FOR EACH ROW
BEGIN
    DECLARE current_stock INT;
    
    -- Get current stock for the book
    SELECT quantity INTO current_stock
    FROM Book
    WHERE ISBNID = NEW.ISBNID;
    
    -- Check if requested quantity is available
    IF NEW.quantity > current_stock THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Insufficient stock available';
    END IF;
END$$

DELIMITER ;