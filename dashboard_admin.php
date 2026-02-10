<?php
require_once 'auth.php';
require_once 'db.php'; // แก้ path ให้ตรงกับไฟล์จริงของคุณ
checkLogin();
checkAdmin(); // ฟังก์ชันนี้อยู่ใน auth.php เช็ค role='admin'

// ดึงรายชื่อ User ทั้งหมดที่ไม่ใช่ Admin
$sql = "SELECT id, username, email FROM users WHERE role != 'admin'";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Admin Command Center</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* เพิ่ม CSS เฉพาะหน้านี้ */
        .user-list { width: 100%; border-collapse: collapse; margin-top: 20px; color: var(--text-main); }
        .user-list th { text-align: left; border-bottom: 2px solid var(--primary); padding: 10px; color: var(--primary); }
        .user-list td { padding: 10px; border-bottom: 1px solid var(--border); }
        .status-badge { background: #238636; color: white; padding: 2px 8px; border-radius: 10px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container" style="max-width: 800px;">
        <div class="code-font" style="color: var(--primary);">root@admin:~$ view_users</div>
        <h2>ADMIN_DASHBOARD</h2>
        <p class="sub-text">จัดการผู้ใช้งานและประเมินผล</p>

        <table class="user-list">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>TARGET_USER</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td class="code-font">#<?php echo $row['id']; ?></td>
                    <td>
                        <?php echo htmlspecialchars($row['username']); ?> <br>
                        <span style="font-size:12px; color:#8b949e;"><?php echo $row['email']; ?></span>
                    </td>
                    <td>
                        <a href="evaluation.php?user_id=<?php echo $row['id']; ?>" class="btn btn-primary" style="width: auto; display: inline-block; padding: 5px 15px;">
                            > Evaluate
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <a href="logout.php" class="btn btn-secondary" style="margin-top: 30px;">LOGOUT SYSTEM</a>
    </div>
</body>
</html>