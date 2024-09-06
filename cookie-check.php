<?php
if (!isset($_COOKIE['admin_name']) || !isset($_COOKIE['admin_id']) || !isset($_COOKIE['admin_email_id'])) {
    header('Location: signin.php');
    exit();
}