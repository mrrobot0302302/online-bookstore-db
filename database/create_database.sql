-- Complete Database Schema
DROP DATABASE IF EXISTS online_bookstore;
CREATE DATABASE online_bookstore
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE online_bookstore;

-- Users table (Plain text passwords for demo)
CREATE TABLE Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(50) NOT NULL,
    role ENUM('ADMIN','CUSTOMER') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User Profile
CREATE TABLE UserProfile (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15),
    address VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
        ON DELETE CASCADE
);

-- Publisher
CREATE TABLE Publisher (
    PID INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address VARCHAR(255),
    phone_number VARCHAR(20)
);

-- Book
CREATE TABLE Book (
    ISBNID VARCHAR(20) PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    PID INT NOT NULL,
    publication_year INT CHECK (publication_year >= 1900),
    price DECIMAL(10,2) CHECK (price > 0),
    category ENUM('Science','Art','Religion','History','Geography'),
    quantity INT DEFAULT 0,
    threshold INT DEFAULT 5,
    FOREIGN KEY (PID) REFERENCES Publisher(PID)
);

-- Author
CREATE TABLE Author (
    AID INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

-- Book Authors (Many-to-Many)
CREATE TABLE Book_Authors (
    AID INT,
    ISBNID VARCHAR(20),
    PRIMARY KEY (AID, ISBNID),
    FOREIGN KEY (AID) REFERENCES Author(AID),
    FOREIGN KEY (ISBNID) REFERENCES Book(ISBNID)
);

-- Publisher Orders
CREATE TABLE Publisher_Order (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    ISBNID VARCHAR(20),
    PID INT,
    order_date DATE,
    quantity INT,
    status ENUM('Pending','Confirmed') DEFAULT 'Pending',
    FOREIGN KEY (ISBNID) REFERENCES Book(ISBNID),
    FOREIGN KEY (PID) REFERENCES Publisher(PID)
);

-- Sales
CREATE TABLE Sales (
    sale_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    ISBNID VARCHAR(20),
    sale_date DATE,
    quantity INT,
    price_at_sale DECIMAL(10,2),
    FOREIGN KEY (user_id) REFERENCES Users(user_id),
    FOREIGN KEY (ISBNID) REFERENCES Book(ISBNID)
);

-- Cart
CREATE TABLE Cart (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    ISBNID VARCHAR(20),
    quantity INT DEFAULT 1,
    UNIQUE (customer_id, ISBNID),
    FOREIGN KEY (customer_id) REFERENCES Users(user_id),
    FOREIGN KEY (ISBNID) REFERENCES Book(ISBNID)
);