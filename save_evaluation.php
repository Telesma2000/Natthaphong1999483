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

    $check_sql = "SELECT id FROM evaluations WHERE user_id = ? AND evaluator_id = ?";
    $stmt_check = $conn->prepare($check_sql);
    if (!$stmt_check) die("SQL Error (ตอนเช็คข้อมูล): " . $conn->error);
    $stmt_check->bind_param("ii", $user_id, $evaluator_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $eval_row = $result_check->fetch_assoc();
        $eval_id = $eval_row['id'];
        
        $sql = "UPDATE evaluations SET score = ?, comments = ?, evaluator_id = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) die("SQL Error: " . $conn->error);
        $stmt->bind_param("dsii", $score, $comment, $evaluator_id, $eval_id);
        $stmt->execute();
    } else {
        $sql = "INSERT INTO evaluations (user_id, evaluator_id, score, comments) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) die("SQL Error: " . $conn->error);
        $stmt->bind_param("iids", $user_id, $evaluator_id, $score, $comment);
        $stmt->execute();
        $eval_id = $stmt->insert_id;
    }

    // 2. จัดการอัปโหลดไฟล์หลักฐานของ Admin
    if (isset($_FILES['admin_evidence_file']) && $_FILES['admin_evidence_file']['error'] == 0) {
        $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png', 'docx', 'xlsx'];
        $file_name = $_FILES['admin_evidence_file']['name']; // ชื่อไฟล์ต้นฉบับ
        $file_tmp = $_FILES['admin_evidence_file']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (in_array($file_ext, $allowed_ext)) {
            if (!file_exists('uploads/admin_evidence')) {
                mkdir('uploads/admin_evidence', 0777, true);
            }

            // ระบบตั้งชื่อไฟล์ใหม่ให้มีคำว่า ADMIN_EVID
            $new_file_name = "ADMIN_EVID_" . $eval_id . "_" . uniqid() . "." . $file_ext;
            $dest_path = "uploads/admin_evidence/" . $new_file_name;

            if (move_uploaded_file($file_tmp, $dest_path)) {
                // --- [จุดแก้ไข]: ใช้โครงสร้าง SQL ตามตารางที่คุณมีเป๊ะๆ ---
                $sql_evid = "INSERT INTO evidence (user_id, file_name, file_path) VALUES (?, ?, ?)";
                $stmt_evid = $conn->prepare($sql_evid);
                if ($stmt_evid) {
                    $stmt_evid->bind_param("iss", $user_id, $file_name, $dest_path);
                    $stmt_evid->execute();
                }
            }
        }
    }

    $redirect_url = ($_SESSION['role'] === 'admin') ? 'dashboard_admin.php' : 'dashboard_evaluator.php';
    echo "<script>
        alert('บันทึกผลการประเมินสำเร็จ!');
        window.location.href = '$redirect_url';
    </script>";
    exit();
}
?>