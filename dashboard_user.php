<?php
date_default_timezone_set('Asia/Bangkok'); 

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

// --- [จุดแก้ไข]: เพิ่ม / เพื่อระบุว่าหาชื่อไฟล์ ไม่ใช่ชื่อโฟลเดอร์ ---
// 1. ดึงไฟล์ของ Admin
$admin_files_sql = "SELECT * FROM evidence WHERE user_id = $user_id AND file_path LIKE '%/ADMIN_EVID_%' ORDER BY uploaded_at DESC";
$admin_files = $conn->query($admin_files_sql);

// 2. ดึงไฟล์ของ Evaluator
$eval_files_sql = "SELECT * FROM evidence WHERE user_id = $user_id AND file_path LIKE '%/EVAL_EVID_%' ORDER BY uploaded_at DESC";
$eval_files = $conn->query($eval_files_sql);

// 3. ดึงไฟล์ที่ User อัปโหลดเอง
$user_files_sql = "SELECT * FROM evidence WHERE user_id = $user_id AND file_path NOT LIKE '%/ADMIN_EVID_%' AND file_path NOT LIKE '%/EVAL_EVID_%' ORDER BY uploaded_at DESC";
$user_files = $conn->query($user_files_sql);
// ----------------------------------------------------------------

// ดึงคะแนน
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
        .evidence-grid {
            display: flex;
            gap: 20px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .evidence-col {
            flex: 1;
            min-width: 300px;
            background: rgba(0,0,0,0.2);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 15px;
        }
        .file-item {
            background: #0d1117;
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 10px;
            overflow: hidden;
        }
        .img-preview {
            width: 100%;
            height: auto;
            border-radius: 4px;
            margin-top: 5px;
            border: 1px solid #30363d;
            display: block;
        }
        .file-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
            font-size: 12px;
            color: var(--text-dim);
        }
        .download-btn {
            display: inline-block;
            margin-top: 5px;
            padding: 6px 12px;
            background: rgba(88, 166, 255, 0.1);
            color: var(--primary);
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
            border: 1px solid var(--primary);
        }
        .download-btn:hover { background: var(--primary); color: #000; }
    </style>
</head>
<body>
    <div class="container" style="max-width: 900px;">
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
                <label>ส่งงาน (PDF, JPG, PNG | Max 10MB):</label>
                <label for="user_file" class="custom-file-upload">
                    <span class="upload-icon">📂</span> คลิกเพื่อเลือกไฟล์ (Browse File)
                </label>
                <input type="file" name="file_upload" id="user_file" accept=".pdf, .jpg, .jpeg, .png" required onchange="showFileName(this, 'user-file-name')">
                <span id="user-file-name" class="file-name-display">...</span>
            </div>
            
            <button type="submit" class="btn btn-primary" style="margin-top: 15px;">
                อัปโหลดส่งกรรมการ
            </button>
        </form>
        <hr style="border: 0; height: 1px; background: var(--border); margin: 30px 0;">

        <div class="evidence-grid">
            
            <div class="evidence-col">
                <label class="code-font" style="color: var(--primary); display:block; margin-bottom:15px;">ADMIN FEEDBACK</label>
                
                <?php if ($admin_files->num_rows > 0): ?>
                    <?php while($f = $admin_files->fetch_assoc()): 
                        $ext = strtolower(pathinfo($f['file_name'], PATHINFO_EXTENSION));
                        $is_img = in_array($ext, ['jpg', 'jpeg', 'png', 'gif']);
                    ?>
                        <div class="file-item">
                            <div class="file-header">
                                <span><?php echo $f['file_name']; ?></span>
                                <span><?php echo date('d/m/y H:i', strtotime($f['uploaded_at'])); ?></span>
                            </div>
                            <?php if($is_img): ?>
                                <a href="<?php echo $f['file_path']; ?>" target="_blank"><img src="<?php echo $f['file_path']; ?>" class="img-preview"></a>
                            <?php else: ?>
                                <a href="<?php echo $f['file_path']; ?>" target="_blank" class="download-btn">เปิดดูเอกสาร</a>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color: var(--text-dim); font-size: 13px;">- ไม่มีเอกสารจาก Admin -</p>
                <?php endif; ?>
            </div>

            <div class="evidence-col">
                <label class="code-font" style="color: #bc8cff; display:block; margin-bottom:15px;">EVALUATOR FEEDBACK</label>
                
                <?php if ($eval_files->num_rows > 0): ?>
                    <?php while($f = $eval_files->fetch_assoc()): 
                        $ext = strtolower(pathinfo($f['file_name'], PATHINFO_EXTENSION));
                        $is_img = in_array($ext, ['jpg', 'jpeg', 'png', 'gif']);
                    ?>
                        <div class="file-item">
                            <div class="file-header">
                                <span><?php echo $f['file_name']; ?></span>
                                <span><?php echo date('d/m/y H:i', strtotime($f['uploaded_at'])); ?></span>
                            </div>
                            <?php if($is_img): ?>
                                <a href="<?php echo $f['file_path']; ?>" target="_blank"><img src="<?php echo $f['file_path']; ?>" class="img-preview"></a>
                            <?php else: ?>
                                <a href="<?php echo $f['file_path']; ?>" target="_blank" class="download-btn">เปิดดูเอกสาร</a>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color: var(--text-dim); font-size: 13px;">- ไม่มีเอกสารจาก Evaluator -</p>
                <?php endif; ?>
            </div>

        </div>

        <div style="margin-top: 30px;">
            <label class="code-font" style="color: var(--accent); margin-bottom: 10px; display:block;">ประวัติการส่งงานของคุณ:</label>
            <div style="background: rgba(255,255,255,0.02); padding: 15px; border-radius: 6px;">
                <?php if ($user_files->num_rows > 0): ?>
                    <?php while($f = $user_files->fetch_assoc()): ?>
                        <div style="padding: 8px 0; border-bottom: 1px solid var(--border); font-size: 13px; color: var(--text-dim); display:flex; justify-content:space-between;">
                            <span>📄 <?php echo $f['file_name']; ?></span>
                            <span><?php echo date('d/m/Y H:i', strtotime($f['uploaded_at'])); ?></span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color: var(--text-dim); font-size: 13px;">- คุณยังไม่ได้ส่งงาน -</p>
                <?php endif; ?>
            </div>
        </div>
        
        <a href="?action=logout" class="btn btn-secondary" style="margin-top: 30px;">ออกจากระบบ</a>
    </div>

    <script>
        function showFileName(input, displayId) {
            const display = document.getElementById(displayId);
            if (input.files && input.files.length > 0) {
                display.innerText = "Selected: " + input.files[0].name;
                display.classList.add('active');    
            } else {
                display.innerText = "";
                display.classList.remove('active');
            }
        }
    </script>
    <script src="background.js"></script>
</body>
</html>