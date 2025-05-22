<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../config.php';

$response = array();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get all products
    $sql = "SELECT * FROM Products";
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        $products = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
        $response['status'] = 'success';
        $response['products'] = $products;
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Failed to fetch products';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (isset($data['action'])) {
        switch ($data['action']) {
            case 'add_to_cart':
                if (isset($data['customer_id']) && isset($data['product_id']) && isset($data['quantity'])) {
                    $customer_id = mysqli_real_escape_string($conn, $data['customer_id']);
                    $product_id = mysqli_real_escape_string($conn, $data['product_id']);
                    $quantity = mysqli_real_escape_string($conn, $data['quantity']);
                    
                    // Check if product exists in cart
                    $check_sql = "SELECT * FROM Cart WHERE CustomerID = ? AND ProductID = ?";
                    $check_stmt = mysqli_prepare($conn, $check_sql);
                    mysqli_stmt_bind_param($check_stmt, "ii", $customer_id, $product_id);
                    mysqli_stmt_execute($check_stmt);
                    $check_result = mysqli_stmt_get_result($check_stmt);
                    
                    if (mysqli_num_rows($check_result) > 0) {
                        // Update quantity
                        $update_sql = "UPDATE Cart SET Quantity = Quantity + ? WHERE CustomerID = ? AND ProductID = ?";
                        $update_stmt = mysqli_prepare($conn, $update_sql);
                        mysqli_stmt_bind_param($update_stmt, "iii", $quantity, $customer_id, $product_id);
                        
                        if (mysqli_stmt_execute($update_stmt)) {
                            $response['status'] = 'success';
                            $response['message'] = 'Cart updated successfully';
                        } else {
                            $response['status'] = 'error';
                            $response['message'] = 'Failed to update cart';
                        }
                    } else {
                        // Add new item to cart
                        $insert_sql = "INSERT INTO Cart (CustomerID, ProductID, Quantity) VALUES (?, ?, ?)";
                        $insert_stmt = mysqli_prepare($conn, $insert_sql);
                        mysqli_stmt_bind_param($insert_stmt, "iii", $customer_id, $product_id, $quantity);
                        
                        if (mysqli_stmt_execute($insert_stmt)) {
                            $response['status'] = 'success';
                            $response['message'] = 'Item added to cart';
                        } else {
                            $response['status'] = 'error';
                            $response['message'] = 'Failed to add item to cart';
                        }
                    }
                }
                break;
                
            case 'get_cart':
                if (isset($data['customer_id'])) {
                    $customer_id = mysqli_real_escape_string($conn, $data['customer_id']);
                    
                    $sql = "SELECT c.*, p.Name, p.Price 
                           FROM Cart c 
                           JOIN Products p ON c.ProductID = p.ProductID 
                           WHERE c.CustomerID = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "i", $customer_id);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    
                    $cart_items = array();
                    while ($row = mysqli_fetch_assoc($result)) {
                        $cart_items[] = $row;
                    }
                    
                    $response['status'] = 'success';
                    $response['cart'] = $cart_items;
                }
                break;
                
            case 'update_cart':
                if (isset($data['customer_id']) && isset($data['product_id']) && isset($data['quantity'])) {
                    $customer_id = mysqli_real_escape_string($conn, $data['customer_id']);
                    $product_id = mysqli_real_escape_string($conn, $data['product_id']);
                    $quantity = mysqli_real_escape_string($conn, $data['quantity']);
                    
                    if ($quantity > 0) {
                        $sql = "UPDATE Cart SET Quantity = ? WHERE CustomerID = ? AND ProductID = ?";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "iii", $quantity, $customer_id, $product_id);
                        
                        if (mysqli_stmt_execute($stmt)) {
                            $response['status'] = 'success';
                            $response['message'] = 'Cart updated successfully';
                        } else {
                            $response['status'] = 'error';
                            $response['message'] = 'Failed to update cart';
                        }
                    } else {
                        // Remove item from cart if quantity is 0
                        $sql = "DELETE FROM Cart WHERE CustomerID = ? AND ProductID = ?";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "ii", $customer_id, $product_id);
                        
                        if (mysqli_stmt_execute($stmt)) {
                            $response['status'] = 'success';
                            $response['message'] = 'Item removed from cart';
                        } else {
                            $response['status'] = 'error';
                            $response['message'] = 'Failed to remove item from cart';
                        }
                    }
                }
                break;
                
            case 'clear_cart':
                if (isset($data['customer_id'])) {
                    $customer_id = mysqli_real_escape_string($conn, $data['customer_id']);
                    
                    $sql = "DELETE FROM Cart WHERE CustomerID = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "i", $customer_id);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $response['status'] = 'success';
                        $response['message'] = 'Cart cleared successfully';
                    } else {
                        $response['status'] = 'error';
                        $response['message'] = 'Failed to clear cart';
                    }
                }
                break;
        }
    }
}

echo json_encode($response);
mysqli_close($conn);
?> 