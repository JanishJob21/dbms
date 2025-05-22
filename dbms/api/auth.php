<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../config.php';

$response = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (isset($data['action'])) {
        switch ($data['action']) {
            case 'login':
                if (isset($data['email']) && isset($data['password'])) {
                    $email = mysqli_real_escape_string($conn, $data['email']);
                    $password = $data['password'];
                    
                    $sql = "SELECT * FROM Customers WHERE Email = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "s", $email);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    
                    if ($row = mysqli_fetch_assoc($result)) {
                        if (password_verify($password, $row['Password'])) {
                            $response['status'] = 'success';
                            $response['message'] = 'Login successful';
                            $response['user'] = array(
                                'id' => $row['CustomerID'],
                                'name' => $row['Name'],
                                'email' => $row['Email']
                            );
                        } else {
                            $response['status'] = 'error';
                            $response['message'] = 'Invalid password';
                        }
                    } else {
                        $response['status'] = 'error';
                        $response['message'] = 'User not found';
                    }
                }
                break;
                
            case 'signup':
                if (isset($data['name']) && isset($data['email']) && isset($data['password'])) {
                    $name = mysqli_real_escape_string($conn, $data['name']);
                    $email = mysqli_real_escape_string($conn, $data['email']);
                    $password = password_hash($data['password'], PASSWORD_DEFAULT);
                    $phone = isset($data['phone']) ? mysqli_real_escape_string($conn, $data['phone']) : '';
                    
                    // Check if email already exists
                    $check_sql = "SELECT * FROM Customers WHERE Email = ?";
                    $check_stmt = mysqli_prepare($conn, $check_sql);
                    mysqli_stmt_bind_param($check_stmt, "s", $email);
                    mysqli_stmt_execute($check_stmt);
                    $check_result = mysqli_stmt_get_result($check_stmt);
                    
                    if (mysqli_num_rows($check_result) > 0) {
                        $response['status'] = 'error';
                        $response['message'] = 'Email already exists';
                    } else {
                        $sql = "INSERT INTO Customers (Name, Email, Password, Phone) VALUES (?, ?, ?, ?)";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $password, $phone);
                        
                        if (mysqli_stmt_execute($stmt)) {
                            $response['status'] = 'success';
                            $response['message'] = 'Registration successful';
                            $response['user'] = array(
                                'id' => mysqli_insert_id($conn),
                                'name' => $name,
                                'email' => $email
                            );
                        } else {
                            $response['status'] = 'error';
                            $response['message'] = 'Registration failed';
                        }
                    }
                }
                break;
        }
    }
}

echo json_encode($response);
mysqli_close($conn);
?> 