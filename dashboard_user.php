<?php
require_once 'auth.php';
require_once 'db.php';
checkLogin();

$user_id = $_SESSION['user_id'];

// ดึงไฟล์ที่เคยอัปโหลด
$file_sql = "SELECT * FROM evidence WHERE user_id = $user_id ORDER BY uploaded_at DESC";
$files = $conn->query($file_sql);

// ดึงคะแนนล่าสุด (ถ้ามี)
$score_sql = "SELECT score, comments FROM evaluations WHERE user_id = $user_id ORDER BY evaluated_at DESC LIMIT 1";
$score_result = $conn->query($score_sql);
$my_score = $score_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>User Portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="code-font" style="color: var(--accent);">user@node:~$ status_check</div>
        <h2>MY_PROFILE</h2>

        <div style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px dashed var(--border);">
            <label class="code-font" style="color: var(--primary);">LATEST_EVALUATION:</label>
            <?php if ($my_score): ?>
                <h1 style="font-size: 48px; margin: 10px 0; color: #fff;"><?php echo $my_score['score']; ?>/100</h1>
                <p class="sub-text">"<?php echo htmlspecialchars($my_score['comments']); ?>"</p>
            <?php else: ?>
                <p style="color: var(--text-dim); margin-top: 10px;">Waiting for assessment...</p>
            <?php endif; ?>
        </div>

        <form action="save_upload.php" method="post" enctype="multipart/form-data">
            <div class="input-group">
                <label for="file">UPLOAD_EVIDENCE (PDF/IMG):</label>
                <input type="file" name="file_upload" id="file" required style="color: white;">
            </div>
            <button type="submit" class="btn btn-primary">
                > UPLOAD_FILE
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
        
        <a href="logout.php" class="btn btn-secondary" style="margin-top: 20px;">LOGOUT</a>
    </div>
</body>
</html>