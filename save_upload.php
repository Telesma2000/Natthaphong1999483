<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file_upload'])) {
    $user_id = $_SESSION['user_id'];
    $file = $_FILES['file_upload'];
    
    // สร้างโฟลเดอร์อัตโนมัติถ้ายังไม่มี
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true); 
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_name = "evidence_" . $user_id . "_" . time() . "." . $ext;
    $target_file = $target_dir . $new_name;

    $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'docx', 'xlsx'];
    if (!in_array(strtolower($ext), $allowed)) {
        echo "<script>alert('อนุญาตเฉพาะไฟล์รูปภาพ, PDF, Word, Excel'); window.history.back();</script>";
        exit;
    }

    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        // --- [จุดแก้ไข]: ใช้โครงสร้าง SQL ตามตารางที่คุณมีเป๊ะๆ ---
        $sql = "INSERT INTO evidence (user_id, file_name, file_path) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $user_id, $file['name'], $target_file);
        
        if ($stmt->execute()) {
            echo "<script>alert('Upload Complete!'); window.location='dashboard_user.php';</script>";
        } else {
            echo "Database Error: " . $conn->error;
        }
    } else {
        echo "<script>alert('Error uploading file.'); window.history.back();</script>";
    }
}
?>