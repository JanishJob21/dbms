<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../config.php';

$response = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (isset($data['action'])) {
        switch ($data['action']) {
            case 'create_order':
                if (isset($data['customer_id']) && isset($data['cart_items'])) {
                    $customer_id = mysqli_real_escape_string($conn, $data['customer_id']);
                    $cart_items = $data['cart_items'];
                    $total_amount = 0;

                    // Start transaction
                    mysqli_begin_transaction($conn);

                    try {
                        // Create order
                        $order_sql = "INSERT INTO Orders (CustomerID, TotalAmount, Status) VALUES (?, ?, 'pending')";
                        $order_stmt = mysqli_prepare($conn, $order_sql);
                        mysqli_stmt_bind_param($order_stmt, "id", $customer_id, $total_amount);
                        mysqli_stmt_execute($order_stmt);
                        $order_id = mysqli_insert_id($conn);

                        // Process each cart item
                        foreach ($cart_items as $item) {
                            $product_id = mysqli_real_escape_string($conn, $item['ProductID']);
                            $quantity = mysqli_real_escape_string($conn, $item['Quantity']);
                            $price = mysqli_real_escape_string($conn, $item['Price']);

                            // Add to order items
                            $item_sql = "INSERT INTO OrderItems (OrderID, ProductID, Quantity, Price) VALUES (?, ?, ?, ?)";
                            $item_stmt = mysqli_prepare($conn, $item_sql);
                            mysqli_stmt_bind_param($item_stmt, "iiid", $order_id, $product_id, $quantity, $price);
                            mysqli_stmt_execute($item_stmt);

                            // Update product stock
                            $update_stock_sql = "UPDATE Products SET Stock = Stock - ? WHERE ProductID = ?";
                            $update_stock_stmt = mysqli_prepare($conn, $update_stock_sql);
                            mysqli_stmt_bind_param($update_stock_stmt, "ii", $quantity, $product_id);
                            mysqli_stmt_execute($update_stock_stmt);

                            $total_amount += ($price * $quantity);
                        }

                        // Update order total
                        $update_total_sql = "UPDATE Orders SET TotalAmount = ? WHERE OrderID = ?";
                        $update_total_stmt = mysqli_prepare($conn, $update_total_sql);
                        mysqli_stmt_bind_param($update_total_stmt, "di", $total_amount, $order_id);
                        mysqli_stmt_execute($update_total_stmt);

                        // Clear cart
                        $clear_cart_sql = "DELETE FROM Cart WHERE CustomerID = ?";
                        $clear_cart_stmt = mysqli_prepare($conn, $clear_cart_sql);
                        mysqli_stmt_bind_param($clear_cart_stmt, "i", $customer_id);
                        mysqli_stmt_execute($clear_cart_stmt);

                        // Commit transaction
                        mysqli_commit($conn);

                        $response['status'] = 'success';
                        $response['message'] = 'Order created successfully';
                        $response['order_id'] = $order_id;
                        $response['total_amount'] = $total_amount;
                    } catch (Exception $e) {
                        // Rollback transaction on error
                        mysqli_rollback($conn);
                        $response['status'] = 'error';
                        $response['message'] = 'Failed to create order: ' . $e->getMessage();
                    }
                }
                break;

            case 'get_orders':
                if (isset($data['customer_id'])) {
                    $customer_id = mysqli_real_escape_string($conn, $data['customer_id']);
                    
                    $sql = "SELECT o.*, 
                           GROUP_CONCAT(CONCAT(p.Name, ' (', oi.Quantity, ')') SEPARATOR ', ') as items
                           FROM Orders o
                           JOIN OrderItems oi ON o.OrderID = oi.OrderID
                           JOIN Products p ON oi.ProductID = p.ProductID
                           WHERE o.CustomerID = ?
                           GROUP BY o.OrderID
                           ORDER BY o.CreatedAt DESC";
                    
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "i", $customer_id);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    
                    $orders = array();
                    while ($row = mysqli_fetch_assoc($result)) {
                        $orders[] = $row;
                    }
                    
                    $response['status'] = 'success';
                    $response['orders'] = $orders;
                }
                break;
        }
    }
}

echo json_encode($response);
mysqli_close($conn);
?> 