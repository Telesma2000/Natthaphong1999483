<?php
// --- ระบบ Logout แบบซ่อนในตัว ---
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
// เอา checkAdmin(); ออก เพราะหน้านี้เป็นของ Evaluator

// --- [จุดที่แก้ไข 1]: ล็อคให้เข้าได้เฉพาะ role = evaluator เท่านั้น ---
if ($_SESSION['role'] !== 'evaluator') {
    die("Access Denied: คุณไม่มีสิทธิ์เข้าถึงหน้านี้");
}
$evaluator_id = $_SESSION['user_id'];
// -----------------------------------------------------------

// --- [จุดที่แก้ไข 2]: อัปเดต SQL ให้ดึงคะแนนเฉพาะที่ Evaluator คนนี้ประเมิน ---
$sql = "SELECT u.id, u.username, u.email, e.score 
        FROM users u 
        LEFT JOIN evaluations e ON u.id = e.user_id AND e.evaluator_id = $evaluator_id
        WHERE u.role = 'user'";
$result = $conn->query($sql);
// ---------------------------------------------------------------------

// เพิ่มตัวดักจับ Error: ถ้า SQL พัง จะได้รู้ทันทีว่าพังเพราะอะไร
if (!$result) {
    die("<div style='background: white; color: red; padding: 20px; font-family: sans-serif;'>
        <strong>เกิดข้อผิดพลาดในการดึงข้อมูล (SQL Error):</strong><br> " . $conn->error . "
        </div>");
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Evaluator Command Center | HR Portal</title> <link rel="stylesheet" href="style.css">
    <style>
        body {
            height: auto !important;
            overflow-y: auto !important;
            align-items: flex-start !important;
            padding: 40px 20px;
        }

        .container-wide {
            background: rgba(22, 27, 34, 0.95);
            padding: 40px;
            width: 100%;
            max-width: 900px;
            border: 1px solid var(--border);
            border-radius: 6px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            position: relative;
            margin: 0 auto;
        }

        .container-wide::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), #bc8cff);
            border-radius: 6px 6px 0 0;
        }

        .table-wrapper {
            overflow-x: auto;
            margin: 30px 0;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: #0d1117;
        }

        .dev-table {
            width: 100%;
            border-collapse: collapse;
            font-family: 'JetBrains Mono', monospace;
            font-size: 14px;
            text-align: left;
        }

        .dev-table th { color: var(--primary); padding: 15px; border-bottom: 1px solid var(--border); white-space: nowrap; }
        .dev-table td { padding: 15px; border-bottom: 1px solid var(--border); color: var(--text-main); vertical-align: middle; }
        .dev-table tr:hover td { background-color: rgba(88, 166, 255, 0.05); }
        .dev-table tr:last-child td { border-bottom: none; }

        /* สไตล์ป้ายสถานะ (เพิ่มสีเขียวสำหรับประเมินแล้ว) */
        .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; display: inline-block; }
        .status-pending { background: rgba(88,166,255,0.1); color: #58a6ff; border: 1px solid #58a6ff; }
        .status-done { background: rgba(63,185,80,0.1); color: #3fb950; border: 1px solid #3fb950; }
    </style>
</head>
<body>
    <div class="container-wide">
        <div class="code-font" style="color: var(--primary);">> รายชื่อผู้รับการประเมิน</div>
        <h2>แดชบอร์ด <span style="color:var(--primary)">Evaluator</span></h2> <p class="sub-text">จัดการผู้ใช้งานและประเมินผลในระบบ (สิทธิ์กรรมการ)</p>

        <div class="table-wrapper">
            <table class="dev-table">
                <thead>
                    <tr>
                        <th style="width: 10%;">ID</th>
                        <th style="width: 40%;">ข้อมูลผู้ใช้ (User Info)</th>
                        <th style="width: 25%;">สถานะ</th>
                        <th style="width: 25%;">การจัดการ (Action)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                        
                        <?php $is_evaluated = !is_null($row['score']); ?>
                        
                        <tr>
                            <td class="code-font" style="color: var(--accent);">#<?php echo $row['id']; ?></td>
                            <td>
                                <strong style="color: #fff; font-size: 16px;"><?php echo htmlspecialchars($row['username']); ?></strong> <br>
                                <span style="font-size:12px; color:#8b949e; font-family: 'JetBrains Mono', monospace;">
                                    <?php echo htmlspecialchars($row['email']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if($is_evaluated): ?>
                                    <span class="status-badge status-done">[OK] ประเมินแล้ว (<?php echo $row['score']; ?>/100)</span>
                                <?php else: ?>
                                    <span class="status-badge status-pending">[?] รอการประเมิน</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($is_evaluated): ?>
                                    <a href="evaluation.php?user_id=<?php echo $row['id']; ?>" class="btn btn-secondary" style="padding: 8px 15px; font-size: 12px; margin-top:0;">
                                        > แก้ไขผลประเมิน
                                    </a>
                                <?php else: ?>
                                    <a href="evaluation.php?user_id=<?php echo $row['id']; ?>" class="btn btn-primary" style="padding: 8px 15px; font-size: 12px;">
                                        > ประเมินผล
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 30px; color: var(--text-dim);">
                                -- No users found in the system --
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <a href="?action=logout" class="btn btn-secondary" style="max-width: 200px; margin-top: 20px;">
            < ออกจากระบบ
        </a>
    </div>
    <script src="background.js"></script>
</body>
</html>