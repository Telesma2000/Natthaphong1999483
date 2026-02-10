<?php
require_once 'auth.php';
require_once 'db.php';
checkLogin();
checkAdmin();

$target_id = $_GET['user_id'];
// ดึงข้อมูล User ที่จะถูกประเมิน
$user_sql = "SELECT username FROM users WHERE id = $target_id";
$target_user = $conn->query($user_sql)->fetch_assoc();

// ดึงไฟล์หลักฐานของ User คนนั้นมาดูประกอบ
$evidence_sql = "SELECT * FROM evidence WHERE user_id = $target_id";
$evidence = $conn->query($evidence_sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Evaluate User</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>EVALUATE_MODE</h2>
        <p class="sub-text">Target: <span style="color: var(--primary);"><?php echo $target_user['username']; ?></span></p>

        <div style="margin-bottom: 20px; padding: 15px; background: rgba(0,0,0,0.2); border-radius: 4px;">
            <label class="code-font" style="color: var(--accent);">USER_EVIDENCE:</label>
            <?php if ($evidence->num_rows == 0) echo "<p class='sub-text'>No evidence uploaded.</p>"; ?>
            <?php while($file = $evidence->fetch_assoc()): ?>
                <div>
                    <a href="<?php echo $file['file_path']; ?>" target="_blank" style="color: var(--primary); text-decoration: none;">
                        [OPEN] <?php echo $file['file_name']; ?>
                    </a>
                </div>
            <?php endwhile; ?>
        </div>

        <form action="save_evaluation.php" method="post">
            <input type="hidden" name="user_id" value="<?php echo $target_id; ?>">
            
            <div class="input-group">
                <label>SCORE (0-100):</label>
                <input type="number" name="score" min="0" max="100" required placeholder="Enter score...">
            </div>

            <div class="input-group">
                <label>COMMENT / FEEDBACK:</label>
                <input type="text" name="comment" placeholder="Good job, but..." style="height: 80px;">
            </div>

            <button type="submit" class="btn btn-primary">
                CONFIRM_SCORE
            </button>
            <a href="dashboard_admin.php" class="btn btn-secondary">CANCEL</a>
        </form>
    </div>
</body>
</html>