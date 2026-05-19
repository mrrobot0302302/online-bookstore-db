USE online_bookstore;

-- Publishers
INSERT INTO Publisher (name, address, phone_number) VALUES
('Pearson', 'London, UK', '111111'),
('OReilly', 'California, USA', '222222'),
('McGraw Hill', 'New York, USA', '333333'),
('Penguin Random House', 'New York, USA', '444444'),
('HarperCollins', 'New York, USA', '555555');

-- Books with varying stock levels
INSERT INTO Book VALUES
('B001', 'Database Systems', 1, 2020, 300, 'Science', 10, 5),
('B002', 'Operating Systems', 2, 2019, 280, 'Science', 8, 4),
('B003', 'World History', 3, 2018, 200, 'History', 12, 6),
('B004', 'Art of Painting', 1, 2017, 150, 'Art', 7, 3),
('B005', 'Geography Basics', 2, 2021, 180, 'Geography', 9, 4),
('B006', 'Religious Studies', 3, 2022, 220, 'Religion', 15, 5),
('B007', 'Advanced Mathematics', 4, 2020, 350, 'Science', 6, 3),
('B008', 'Modern Art', 5, 2019, 190, 'Art', 11, 5);

-- Authors
INSERT INTO Author (name) VALUES
('Elmasri'), ('Navathe'), ('Silberschatz'),
('Galvin'), ('Tanenbaum'), ('John Smith'),
('Jane Doe'), ('Michael Brown'), ('Sarah Johnson'),
('Robert Wilson'), ('Emily Davis');

-- Book Authors
INSERT INTO Book_Authors VALUES
(1, 'B001'), (2, 'B001'),
(3, 'B002'), (4, 'B002'), (5, 'B002'),
(6, 'B003'), (6, 'B004'), (6, 'B005'),
(7, 'B006'), (8, 'B006'),
(9, 'B007'), (10, 'B007'),
(11, 'B008');

-- Users with PLAIN TEXT passwords (for demo)
INSERT INTO Users (username, password, role) VALUES
('admin', 'admin123', 'ADMIN'),
('john_doe', 'customer123', 'CUSTOMER'),
('jane_smith', 'customer123', 'CUSTOMER'),
('mike_brown', 'customer123', 'CUSTOMER'),
('sarah_jones', 'customer123', 'CUSTOMER');

-- User Profiles
INSERT INTO UserProfile (user_id, first_name, last_name, email, phone, address) VALUES
(1, 'System', 'Admin', 'admin@bookstore.com', '01000000000', 'Admin Office'),
(2, 'John', 'Doe', 'john@example.com', '01111111111', '123 Main St, City'),
(3, 'Jane', 'Smith', 'jane@example.com', '02222222222', '456 Oak Ave, Town'),
(4, 'Mike', 'Brown', 'mike@example.com', '03333333333', '789 Pine Rd, Village'),
(5, 'Sarah', 'Jones', 'sarah@example.com', '04444444444', '101 Maple Dr, County');

-- Sample Sales for reporting (various dates)
INSERT INTO Sales (user_id, ISBNID, sale_date, quantity, price_at_sale) VALUES
-- Previous month sales
(2, 'B001', DATE_SUB(CURDATE(), INTERVAL 35 DAY), 2, 300),
(2, 'B002', DATE_SUB(CURDATE(), INTERVAL 32 DAY), 1, 280),
(3, 'B003', DATE_SUB(CURDATE(), INTERVAL 30 DAY), 1, 200),
(3, 'B004', DATE_SUB(CURDATE(), INTERVAL 28 DAY), 3, 150),
(4, 'B005', DATE_SUB(CURDATE(), INTERVAL 25 DAY), 1, 180),
(4, 'B006', DATE_SUB(CURDATE(), INTERVAL 22 DAY), 2, 220),

-- Last 3 months sales
(5, 'B001', DATE_SUB(CURDATE(), INTERVAL 85 DAY), 1, 300),
(2, 'B002', DATE_SUB(CURDATE(), INTERVAL 70 DAY), 2, 280),
(3, 'B003', DATE_SUB(CURDATE(), INTERVAL 60 DAY), 1, 200),
(4, 'B004', DATE_SUB(CURDATE(), INTERVAL 45 DAY), 2, 150),
(5, 'B005', DATE_SUB(CURDATE(), INTERVAL 40 DAY), 1, 180),
(2, 'B006', DATE_SUB(CURDATE(), INTERVAL 30 DAY), 3, 220),
(3, 'B007', DATE_SUB(CURDATE(), INTERVAL 25 DAY), 1, 350),
(4, 'B008', DATE_SUB(CURDATE(), INTERVAL 20 DAY), 2, 190),

-- Recent sales
(2, 'B001', CURDATE(), 1, 300),
(3, 'B002', CURDATE(), 1, 280);

-- Sample Publisher Orders (for testing confirm orders)
INSERT INTO Publisher_Order (ISBNID, PID, order_date, quantity, status) VALUES
('B001', 1, '2025-10-01', 20, 'Confirmed'),
('B002', 2, '2025-10-05', 20, 'Pending'),
('B003', 3, '2025-10-10', 20, 'Confirmed'),
('B004', 1, '2025-10-15', 20, 'Pending'),
('B007', 4, '2025-10-20', 20, 'Pending');