<?php
// ไฟล์ src/db.php สำหรับ Docker
$servername = "db-server";  // ต้องใช้ชื่อนี้เท่านั้น (ตาม service ใน docker-compose)
$username = "root";         
$password = "rootpassword"; // ต้องตรงกับ MYSQL_ROOT_PASSWORD ใน yml
$dbname = "my_website";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8");
?>