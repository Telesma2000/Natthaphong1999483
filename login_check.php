<?php
session_start();
require_once 'includes/db.php'; // เรียกไฟล์ต่อ DB ที่คุณมี

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // ป้องกัน SQL Injection
    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        
        // ตรวจสอบรหัสผ่าน (ที่ Hash ไว้ตอนสมัคร)
        if (password_verify($password, $row['password'])) {
            // Login สำเร็จ -> เก็บ Session
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role'] = $row['role']; // 'admin', 'user', 'evaluator'

            // แยกทางตาม Role
            if ($row['role'] == 'admin') {
                header("Location: dashboard_admin.php");
            } else {
                header("Location: dashboard_user.php");
            }
            exit();
        } else {
            echo "<script>alert('Password ไม่ถูกต้อง'); window.location='login.html';</script>";
        }
    } else {
        echo "<script>alert('ไม่พบชื่อผู้ใช้งานนี้'); window.location='login.html';</script>";
    }
}
?>