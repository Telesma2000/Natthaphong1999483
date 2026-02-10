<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file_upload'])) {
    $user_id = $_SESSION['user_id'];
    $file = $_FILES['file_upload'];
    
    // ตั้งชื่อไฟล์ใหม่ป้องกันชื่อซ้ำ
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_name = "evidence_" . $user_id . "_" . time() . "." . $ext;
    $target_dir = "uploads/"; // อย่าลืมสร้างโฟลเดอร์ uploads ไว้ที่เดียวกับไฟล์ php
    $target_file = $target_dir . $new_name;

    // เช็คประเภทไฟล์ (ตัวอย่างยอมให้แค่ jpg, png, pdf)
    $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
    if (!in_array(strtolower($ext), $allowed)) {
        echo "<script>alert('อนุญาตเฉพาะไฟล์รูปภาพและ PDF'); window.history.back();</script>";
        exit;
    }

    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        $sql = "INSERT INTO evidence (user_id, file_name, file_path) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $user_id, $file['name'], $target_file);
        $stmt->execute();
        
        echo "<script>alert('Upload Complete!'); window.location='dashboard_user.php';</script>";
    } else {
        echo "Error uploading file.";
    }
}
?>