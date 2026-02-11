<?php
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_start();
    session_unset();
    session_destroy();
    header("Location: index.html");
    exit();
}

require_once 'auth.php';
require_once 'db.php';
checkLogin();

$user_id = $_SESSION['user_id'];

// --- [จุดแก้ไข]: ดึงข้อมูลโดยแยกตามชื่อ Path ในฐานข้อมูลของคุณ ---
// 1. ไฟล์ที่ User อัปโหลดเอง (ชื่อ Path จะไม่มีคำว่า ADMIN_EVID)
$user_files_sql = "SELECT * FROM evidence WHERE user_id = $user_id AND file_path NOT LIKE '%ADMIN_EVID_%' ORDER BY uploaded_at DESC";
$user_files = $conn->query($user_files_sql);

// 2. ไฟล์ที่ Admin/Evaluator แนบมา (ชื่อ Path จะมีคำว่า ADMIN_EVID)
$admin_files_sql = "SELECT * FROM evidence WHERE user_id = $user_id AND file_path LIKE '%ADMIN_EVID_%' ORDER BY uploaded_at DESC";
$admin_files = $conn->query($admin_files_sql);
// -----------------------------------------------------------

// ดึงคะแนนแยกระหว่าง Admin และ Evaluator
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
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>User Portal | HR System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .file-list-container {
            background: #0d1117; 
            border: 1px solid var(--border); 
            border-radius: 4px; 
            padding: 10px;
            margin-top: 10px;
        }
        .file-item {
            border-bottom: 1px solid var(--border); 
            padding: 10px 0; 
            font-size: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .file-item:last-child { border-bottom: none; }
        .file-link { color: var(--primary); text-decoration: none; font-weight: bold; }
        .file-link:hover { text-decoration: underline; }
        .file-date { color: var(--text-dim); font-size: 12px; font-family: 'JetBrains Mono', monospace; }
    </style>
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
                    <p class="sub-text" style="font-size: 14px; margin-bottom: 0;">"<?php echo htmlspecialchars($admin_score['comments']); ?>"</p>
                <?php else: ?>
                    <p style="color: var(--text-dim); margin-top: 10px; font-size: 14px; margin-bottom: 0;">กำลังรอผลการประเมิน...</p>
                <?php endif; ?>
            </div>

            <div style="flex: 1; background: rgba(255,255,255,0.05); padding: 15px; border-radius: 4px; border: 1px dashed var(--border);">
                <label class="code-font" style="color: #bc8cff; font-size: 13px;">> EVALUATOR_SCORE</label>
                <?php if ($evaluator_score): ?>
                    <h1 style="font-size: 36px; margin: 10px 0; color: #fff;"><?php echo $evaluator_score['score']; ?>/100</h1>
                    <p class="sub-text" style="font-size: 14px; margin-bottom: 0;">"<?php echo htmlspecialchars($evaluator_score['comments']); ?>"</p>
                <?php else: ?>
                    <p style="color: var(--text-dim); margin-top: 10px; font-size: 14px; margin-bottom: 0;">กำลังรอผลการประเมิน...</p>
                <?php endif; ?>
            </div>
        </div>

        <form action="save_upload.php" method="post" enctype="multipart/form-data">
            <div class="input-group">
                <label for="file">แนบไฟล์หลักฐาน (PDF/IMG/DOCX/XLSX):</label>
                <input type="file" name="file_upload" id="file" required style="color: white; background: #0d1117; padding: 10px; border: 1px dashed var(--border); border-radius: 4px; width: 100%; box-sizing: border-box;">
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top: 10px;">
                อัปโหลดเอกสาร
            </button>
        </form>

        <hr style="border: 0; height: 1px; background: var(--border); margin: 30px 0;">

        <?php if ($admin_files->num_rows > 0): ?>
            <div style="margin-bottom: 25px;">
                <label class="code-font" style="color: var(--accent);">📥 FEEDBACK_DOCUMENTS (เอกสารตอบกลับจากกรรมการ):</label>
                <div class="file-list-container">
                    <?php while($f = $admin_files->fetch_assoc()): ?>
                        <div class="file-item">
                            <a href="<?php echo $f['file_path']; ?>" target="_blank" class="file-link" style="color: var(--accent);">
                                📎 <?php echo $f['file_name']; ?> 
                            </a>
                            <span class="file-date"><?php echo date('d/m/Y H:i', strtotime($f['uploaded_at'])); ?></span>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($user_files->num_rows > 0): ?>
            <div>
                <label class="code-font" style="color: var(--primary);">📤 MY_UPLOADED_FILES (เอกสารที่คุณส่ง):</label>
                <div class="file-list-container">
                    <?php while($f = $user_files->fetch_assoc()): ?>
                        <div class="file-item">
                            <a href="<?php echo $f['file_path']; ?>" target="_blank" class="file-link">
                                📄 <?php echo $f['file_name']; ?> 
                            </a>
                            <span class="file-date"><?php echo date('d/m/Y H:i', strtotime($f['uploaded_at'])); ?></span>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <a href="?action=logout" class="btn btn-secondary" style="margin-top: 30px;">ออกจากระบบ</a>
    </div>
    <script src="background.js"></script>
</body>
</html>