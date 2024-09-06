<?php
include '../cookie-check.php';
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = $_POST['product_id'];
    $requested_quantity = $_POST['quantity'];

    $stmt = $pdo->prepare("SELECT product_stock FROM product_mst WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    $response = [];

    if ($product) {
        $available_stock = $product['product_stock'];

        if ($requested_quantity > $available_stock) {
            $response['valid'] = false;
            $response['message'] = "Requested quantity exceeds available stock ($available_stock units available)";
        } else {
            $response['valid'] = true;
        }
    } else {
        $response['valid'] = false;
        $response['message'] = "Invalid product selected";
    }
    echo json_encode($response);
}
