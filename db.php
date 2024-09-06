<?php
$servername = "localhost";
$username = "root";
$password = "";

try {
  $pdo = new PDO("mysql:host=$servername;dbname=asignment", $username, $password);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//   echo "Connected successfully";
} catch(PDOException $e) {
  die("Database connection failed: " . $e->getMessage());
}