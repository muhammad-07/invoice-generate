<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM admin WHERE admin_email_id = :email AND admin_password = :password");
    $stmt->execute(['email' => $email, 'password' => $password]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
        setcookie('admin_name', $admin['admin_name'], time() + (86400 * 30), "/");
        setcookie('admin_id', $admin['admin_id'], time() + (86400 * 30), "/");
        setcookie('admin_email_id', $admin['admin_email_id'], time() + (86400 * 30), "/");
        header('Location: generate-invoice.php');
    } else {
        echo 'Invalid credentials!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>CHPL Assignment</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</head>

<body>
    <div class="container mt-5 pt-5">
    
    <div class="row">
        <div class="col-md-4 col-md-offset-4">
        <h2>CHPL Login</h2>
        <hr/>
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        </div>
        </div>
    </div>
</body>

</html>