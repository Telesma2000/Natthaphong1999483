<?php
// --- เพิ่มโค้ดส่วนนี้เพื่อจัดการการออกจากระบบ (Logout) และเด้งไปหน้า index.html ---
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_start();
    session_unset();
    session_destroy();
    header("Location: index.html");
    exit();
}
// ------------------------------------------------------------------------

require_once 'auth.php';
require_once 'db.php';
checkLogin();

$user_id = $_SESSION['user_id'];

// ดึงไฟล์ที่เคยอัปโหลด
$file_sql = "SELECT * FROM evidence WHERE user_id = $user_id ORDER BY uploaded_at DESC";
$files = $conn->query($file_sql);

// --- [จุดที่แก้ไขที่ 1]: ดึงคะแนนแยกระหว่าง Admin และ Evaluator ---
$score_sql = "SELECT e.score, e.comments, u.role as evaluator_role 
              FROM evaluations e 
              JOIN users u ON e.evaluator_id = u.id 
              WHERE e.user_id = $user_id";
$score_result = $conn->query($score_sql);

$admin_score = null;
$evaluator_score = null;

while($row = $score_result->fetch_assoc()) {
    if ($row['evaluator_role'] == 'admin') {
        $admin_score = $row;
    } elseif ($row['evaluator_role'] == 'evaluator') {
        $evaluator_score = $row;
    }
}
// --------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>User Portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="max-width: 650px;">
        <div class="code-font" style="color: var(--accent);">สถานะ การประเมิน</div>
        <h2>ผลประเมิน</h2>

        <div style="display: flex; gap: 15px; margin-bottom: 20px;">
            <div style="flex: 1; background: rgba(255,255,255,0.05); padding: 15px; border-radius: 4px; border: 1px dashed var(--border);">
                <label class="code-font" style="color: var(--primary); font-size: 13px;">> ADMIN_SCORE</label>
                <?php if ($admin_score): ?>
                    <h1 style="font-size: 36px; margin: 10px 0; color: #fff;"><?php echo $admin_score['score']; ?>/100</h1>
                    <p class="sub-text" style="font-size: 14px;">"<?php echo htmlspecialchars($admin_score['comments']); ?>"</p>
                <?php else: ?>
                    <p style="color: var(--text-dim); margin-top: 10px; font-size: 14px;">กำลังรอผลการประเมิน...</p>
                <?php endif; ?>
            </div>

            <div style="flex: 1; background: rgba(255,255,255,0.05); padding: 15px; border-radius: 4px; border: 1px dashed var(--border);">
                <label class="code-font" style="color: #bc8cff; font-size: 13px;">> EVALUATOR_SCORE</label>
                <?php if ($evaluator_score): ?>
                    <h1 style="font-size: 36px; margin: 10px 0; color: #fff;"><?php echo $evaluator_score['score']; ?>/100</h1>
                    <p class="sub-text" style="font-size: 14px;">"<?php echo htmlspecialchars($evaluator_score['comments']); ?>"</p>
                <?php else: ?>
                    <p style="color: var(--text-dim); margin-top: 10px; font-size: 14px;">กำลังรอผลการประเมิน...</p>
                <?php endif; ?>
            </div>
        </div>
        <form action="save_upload.php" method="post" enctype="multipart/form-data">
            <div class="input-group">
                <label for="file">อัปโหลดไฟล์ (PDF/IMG):</label>
                <input type="file" name="file_upload" id="file" required style="color: white;">
            </div>
            <button type="submit" class="btn btn-primary">
                อัปโหลด
            </button>
        </form>

        <?php if ($files->num_rows > 0): ?>
            <div style="margin-top: 30px;">
                <label class="code-font" style="color: var(--primary);">UPLOADED_FILES:</label>
                <ul style="list-style: none; padding: 0; margin-top: 10px;">
                    <?php while($f = $files->fetch_assoc()): ?>
                        <li style="border-bottom: 1px solid var(--border); padding: 8px 0; color: var(--text-dim); font-size: 14px;">
                            📄 <?php echo $f['file_name']; ?> 
                            <span style="float: right;"><?php echo date('d/m/Y', strtotime($f['uploaded_at'])); ?></span>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <a href="?action=logout" class="btn btn-secondary" style="margin-top: 20px;">ออกจากระบบ</a>
    </div>
</body>
</html>