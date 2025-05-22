-- SQL queries for Grocery Store Management System

-- Add a new product
INSERT INTO Products (Name, Category, Price, Stock) VALUES ('Apple', 'Fruits', 0.50, 100);

-- Update stock for a product
UPDATE Products SET Stock = Stock - 10 WHERE ProductID = 1;

-- Record a sale
INSERT INTO Sales (ProductID, CustomerID, Quantity, SaleDate) VALUES (1, 1, 10, '2025-05-08');

-- Get all products
SELECT * FROM Products;

-- Get sales report
SELECT Sales.SaleID, Products.Name AS ProductName, Customers.Name AS CustomerName, Sales.Quantity, Sales.SaleDate
FROM Sales
JOIN Products ON Sales.ProductID = Products.ProductID
JOIN Customers ON Sales.CustomerID = Customers.CustomerID;