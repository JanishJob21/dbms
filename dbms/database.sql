-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS grocery_store;
USE grocery_store;

-- Create Customers table
CREATE TABLE IF NOT EXISTS Customers (
    CustomerID INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(100) NOT NULL,
    Email VARCHAR(100) UNIQUE NOT NULL,
    Password VARCHAR(255) NOT NULL,
    Phone VARCHAR(15),
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Products table
CREATE TABLE IF NOT EXISTS Products (
    ProductID INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(100) NOT NULL,
    Category VARCHAR(50) NOT NULL,
    Price DECIMAL(10, 2) NOT NULL,
    Stock INT NOT NULL,
    Image VARCHAR(255),
    Description TEXT,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Cart table
CREATE TABLE IF NOT EXISTS Cart (
    CartID INT PRIMARY KEY AUTO_INCREMENT,
    CustomerID INT NOT NULL,
    ProductID INT NOT NULL,
    Quantity INT NOT NULL,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (CustomerID) REFERENCES Customers(CustomerID),
    FOREIGN KEY (ProductID) REFERENCES Products(ProductID)
);

-- Create Orders table
CREATE TABLE IF NOT EXISTS Orders (
    OrderID INT PRIMARY KEY AUTO_INCREMENT,
    CustomerID INT NOT NULL,
    TotalAmount DECIMAL(10, 2) NOT NULL,
    Status VARCHAR(20) NOT NULL DEFAULT 'pending',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (CustomerID) REFERENCES Customers(CustomerID)
);

-- Create OrderItems table
CREATE TABLE IF NOT EXISTS OrderItems (
    OrderItemID INT PRIMARY KEY AUTO_INCREMENT,
    OrderID INT NOT NULL,
    ProductID INT NOT NULL,
    Quantity INT NOT NULL,
    Price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (OrderID) REFERENCES Orders(OrderID),
    FOREIGN KEY (ProductID) REFERENCES Products(ProductID)
);

-- Insert sample products
INSERT INTO Products (Name, Category, Price, Stock, Description) VALUES
('Apples', 'Fruits', 2.99, 100, 'Fresh and juicy apples'),
('Bananas', 'Fruits', 1.99, 150, 'Sweet and ripe bananas'),
('Carrots', 'Vegetables', 1.49, 200, 'Fresh and crunchy carrots'),
('Milk', 'Dairy', 3.49, 50, 'Pure and fresh milk'),
('Bread', 'Bakery', 2.49, 75, 'Freshly baked bread'),
('Eggs', 'Dairy', 4.99, 100, 'Farm fresh eggs'),
('Potatoes', 'Vegetables', 1.99, 150, 'Fresh potatoes'),
('Chicken', 'Meat', 8.99, 30, 'Fresh chicken'),
('Rice', 'Grains', 5.99, 100, 'Premium quality rice'),
('Pasta', 'Grains', 2.99, 80, 'Italian style pasta'); 