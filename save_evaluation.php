<?php
require_once 'auth.php';
require_once 'db.php';
checkLogin();

// --- [จุดที่แก้ไข 1]: อนุญาตให้ทั้ง admin และ evaluator บันทึกคะแนนได้ ---
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'evaluator') {
    die("Access Denied: คุณไม่มีสิทธิ์เข้าถึงหน้านี้");
}
// ----------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $score = $_POST['score']; // รับคะแนนจากฟอร์ม
    $comment = $_POST['comment'];
    $evaluator_id = $_SESSION['user_id'];

    // --- [จุดที่แก้ไข 2]: เช็คว่า "คนประเมินคนนี้" เคยประเมิน User คนนี้ไปแล้วหรือยัง (เพื่อแยกคะแนนกัน) ---
    $check_sql = "SELECT id FROM evaluations WHERE user_id = ? AND evaluator_id = ?";
    $stmt_check = $conn->prepare($check_sql);
    if (!$stmt_check) die("SQL Error (ตอนเช็คข้อมูล): " . $conn->error);
    $stmt_check->bind_param("ii", $user_id, $evaluator_id);
    // ------------------------------------------------------------------------------------------
    
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        // --- กรณีเคยประเมินแล้ว: ทำการ UPDATE ข้อมูล ---
        $eval_row = $result_check->fetch_assoc();
        $eval_id = $eval_row['id'];
        
        // ใช้คำว่า score ตามฐานข้อมูลของคุณ
        $sql = "UPDATE evaluations SET score = ?, comments = ?, evaluator_id = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        
        // ดัก Error ป้องกันจอล่ม
        if (!$stmt) die("SQL Error (ตอนอัปเดต): กรูณาเช็คว่าตาราง evaluations มีคอลัมน์ score หรือไม่? ข้อผิดพลาดคือ: " . $conn->error);
        
        $stmt->bind_param("dsii", $score, $comment, $evaluator_id, $eval_id);
        $stmt->execute();
    } else {
        // --- กรณีประเมินครั้งแรก: ทำการ INSERT ข้อมูล ---
        $sql = "INSERT INTO evaluations (user_id, evaluator_id, score, comments) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        // ดัก Error ป้องกันจอล่ม
        if (!$stmt) die("SQL Error (ตอนเพิ่มข้อมูล): กรุณาเช็คตาราง evaluations! ข้อผิดพลาดคือ: " . $conn->error);
        
        $stmt->bind_param("iids", $user_id, $evaluator_id, $score, $comment);
        $stmt->execute();
        $eval_id = $stmt->insert_id; // เก็บ ID ของการประเมินรอบนี้ไว้
    }

    // 2. จัดการอัปโหลดไฟล์หลักฐานของ Admin (ถ้าแนบมา)
    if (isset($_FILES['admin_evidence_file']) && $_FILES['admin_evidence_file']['error'] == 0) {
        $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png', 'docx', 'xlsx'];
        $file_name = $_FILES['admin_evidence_file']['name'];
        $file_tmp = $_FILES['admin_evidence_file']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (in_array($file_ext, $allowed_ext)) {
            if (!file_exists('uploads/admin_evidence')) {
                mkdir('uploads/admin_evidence', 0777, true); // สร้างโฟลเดอร์อัตโนมัติ
            }

            $new_file_name = "ADMIN_EVID_" . $eval_id . "_" . uniqid() . "." . $file_ext;
            $dest_path = "uploads/admin_evidence/" . $new_file_name;

            if (move_uploaded_file($file_tmp, $dest_path)) {
                // บันทึก Path ไฟล์ลงฐานข้อมูล (เพิ่ม user_id ลงไปให้ตรงกับโค้ดหน้า evaluation ของคุณ)
                $sql_evid = "INSERT INTO evidence (evaluation_id, user_id, file_name, file_path) VALUES (?, ?, ?, ?)";
                $stmt_evid = $conn->prepare($sql_evid);
                if ($stmt_evid) {
                    $stmt_evid->bind_param("iiss", $eval_id, $user_id, $file_name, $dest_path);
                    $stmt_evid->execute();
                }
            }
        }
    }

    // --- [จุดที่แก้ไข 3]: แจ้งเตือนและเด้งกลับหน้า Dashboard ให้ตรงกับ Role ของคนประเมิน ---
    $redirect_url = ($_SESSION['role'] === 'admin') ? 'dashboard_admin.php' : 'dashboard_evaluator.php';
    echo "<script>
        alert('บันทึกผลการประเมินสำเร็จ!');
        window.location.href = '$redirect_url';
    </script>";
    exit();
    // ----------------------------------------------------------------------------
}
?>