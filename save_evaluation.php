<?php
require_once 'auth.php';
require_once 'db.php';
checkLogin();

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'evaluator') {
    die("Access Denied: คุณไม่มีสิทธิ์เข้าถึงหน้านี้");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $score = $_POST['score'];
    $comment = $_POST['comment'];
    $evaluator_id = $_SESSION['user_id'];
    $current_role = $_SESSION['role']; // ดึง Role ปัจจุบัน

    // 1. บันทึกคะแนน (Code เดิม)
    $check_sql = "SELECT id FROM evaluations WHERE user_id = ? AND evaluator_id = ?";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->bind_param("ii", $user_id, $evaluator_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $eval_row = $result_check->fetch_assoc();
        $eval_id = $eval_row['id'];
        $sql = "UPDATE evaluations SET score = ?, comments = ?, evaluator_id = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("dsii", $score, $comment, $evaluator_id, $eval_id);
        $stmt->execute();
    } else {
        $sql = "INSERT INTO evaluations (user_id, evaluator_id, score, comments) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iids", $user_id, $evaluator_id, $score, $comment);
        $stmt->execute();
        $eval_id = $stmt->insert_id;
    }

    // 2. จัดการอัปโหลดไฟล์ (แก้ไขใหม่)
    if (isset($_FILES['admin_evidence_file']) && $_FILES['admin_evidence_file']['error'] == 0) {
        
        $file = $_FILES['admin_evidence_file'];
        $file_name = $file['name'];
        $file_tmp = $file['tmp_name'];
        $file_size = $file['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // --- [Config ใหม่] ---
        $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png', 'docx', 'xlsx'];
        $max_size = 10 * 1024 * 1024; // 10 MB (แก้ไขจาก 10GB)

        if (!in_array($file_ext, $allowed_ext)) {
            echo "<script>alert('นามสกุลไฟล์ไม่ถูกต้อง'); window.history.back();</script>"; exit;
        }
        if ($file_size > $max_size) {
            echo "<script>alert('ไฟล์มีขนาดใหญ่เกิน 10MB'); window.history.back();</script>"; exit;
        }

        if (!file_exists('uploads/admin_evidence')) {
            mkdir('uploads/admin_evidence', 0777, true);
        }

        // --- [จุดสำคัญ]: ตั้งชื่อไฟล์แยกตาม Role (ADMIN_ หรือ EVAL_) ---
        $prefix = ($current_role == 'admin') ? "ADMIN_EVID_" : "EVAL_EVID_";
        $new_file_name = $prefix . $user_id . "_" . uniqid() . "." . $file_ext;
        $dest_path = "uploads/admin_evidence/" . $new_file_name;

        if (move_uploaded_file($file_tmp, $dest_path)) {
            $sql_evid = "INSERT INTO evidence (user_id, file_name, file_path) VALUES (?, ?, ?)";
            $stmt_evid = $conn->prepare($sql_evid);
            if ($stmt_evid) {
                $stmt_evid->bind_param("iss", $user_id, $file_name, $dest_path);
                $stmt_evid->execute();
            }
        }
    }

    $redirect_url = ($current_role === 'admin') ? 'dashboard_admin.php' : 'dashboard_evaluator.php';
    echo "<script>alert('บันทึกผลการประเมินสำเร็จ!'); window.location.href = '$redirect_url';</script>";
    exit();
}
?>