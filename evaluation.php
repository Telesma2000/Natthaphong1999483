<?php
require_once 'auth.php';
require_once 'db.php';
checkLogin();

// --- [จุดที่แก้ไข 1]: เช็คว่าต้องเป็น admin หรือ evaluator เท่านั้นถึงจะเข้าได้ ---
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'evaluator') {
    die("Access Denied: คุณไม่มีสิทธิ์เข้าถึงหน้านี้");
}

// กำหนด URL สำหรับปุ่ม CANCEL ด้านล่าง ให้กลับไปถูกหน้าตาม Role
$dashboard_url = ($_SESSION['role'] === 'admin') ? 'dashboard_admin.php' : 'dashboard_evaluator.php';
// ----------------------------------------------------------------------

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
    <title>Evaluate User | HR Portal</title>
    <link rel="stylesheet" href="style.css">
    
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
            max-width: 1100px;
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
            font-size: 13px;
            text-align: left;
        }

        .dev-table th {
            color: var(--primary);
            padding: 15px;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        .dev-table td {
            padding: 15px;
            border-bottom: 1px solid var(--border);
            color: var(--text-main);
            vertical-align: middle;
            line-height: 1.6;
        }

        .dev-table tr:hover td { background-color: rgba(88, 166, 255, 0.05); }
        .dev-table tr:last-child td { border-bottom: none; }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge-functional { color: #58a6ff; border: 1px solid #58a6ff; background: rgba(88,166,255,0.1); }
        .badge-security { color: #f85149; border: 1px solid #f85149; background: rgba(248,81,73,0.1); }
        .badge-nonfunc { color: #3fb950; border: 1px solid #3fb950; background: rgba(63,185,80,0.1); }
        
        .endpoint {
            background: rgba(255,255,255,0.1);
            padding: 2px 6px;
            border-radius: 4px;
            color: #d2a8ff;
        }

        /* ตกแต่งช่องกรอกคะแนนย่อยในตาราง */
        .score-input {
            width: 70px;
            background: #0d1117;
            border: 1px solid var(--border);
            color: #fff;
            padding: 8px;
            border-radius: 4px;
            font-family: 'JetBrains Mono', monospace;
            text-align: center;
            outline: none;
            transition: 0.3s;
        }
        .score-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(88, 166, 255, 0.2); }
        
        /* ซ่อนลูกศรขึ้นลงของ input number */
        .score-input::-webkit-outer-spin-button,
        .score-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        
        /* ตกแต่งช่องอัปโหลดไฟล์ */
        .file-upload-input {
            width: 100%;
            background: #0d1117;
            color: #fff;
            padding: 10px;
            border: 1px dashed var(--border);
            border-radius: 4px;
            font-family: 'JetBrains Mono', monospace;
            cursor: pointer;
        }
        .file-upload-input:hover { border-color: var(--primary); }
    </style>
</head>
<body>
    <div class="container-wide">
        <h2>EVALUATE_MODE</h2>
        <p class="sub-text">Target: <span style="color: var(--primary);"><?php echo $target_user['username']; ?></span></p>

        <div style="margin-bottom: 20px; padding: 15px; background: rgba(0,0,0,0.3); border-radius: 4px; border: 1px dashed var(--border);">
            <label class="code-font" style="color: var(--accent);">USER_EVIDENCE:</label>
            <?php if ($evidence->num_rows == 0) echo "<p class='sub-text' style='margin-top:10px;'>No evidence uploaded.</p>"; ?>
            <?php while($file = $evidence->fetch_assoc()): ?>
                <div style="margin-top: 10px;">
                    <a href="<?php echo $file['file_path']; ?>" target="_blank" style="color: var(--primary); text-decoration: none;">
                        [OPEN] <?php echo $file['file_name']; ?>
                    </a>
                </div>
            <?php endwhile; ?>
        </div>

        <form action="save_evaluation.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="user_id" value="<?php echo $target_id; ?>">
            <input type="hidden" name="score" id="hidden_total_score" value="0">

            <h3 class="code-font" style="color: #fff; margin-top: 40px;">> SYSTEM_TEST_CASES.log (เกณฑ์การให้คะแนน)</h3>
            <div class="table-wrapper">
                <table class="dev-table">
                    <thead>
                        <tr>
                            <th>หมวด</th>
                            <th>Test Case (TC)</th>
                            <th>Input/Step (ขั้นตอนการทดสอบ)</th>
                            <th>Expected (ผลลัพธ์ที่คาดหวัง)</th>
                            <th style="text-align: center;">คะแนน (เต็ม 10)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $test_cases = [
                            ["Functional", "สมัคร/ล็อกอิน (ถ้ามี)", "1) POST /auth/register (optional)<br>2) POST /auth/login<br>บันทึก token เป็น {{jwtToken}}", "Register: 201/200<br>Login: 200 + JSON มี token", "badge-functional"],
                            ["Functional", "Home ถูกบทบาท", "เปิด /home (หรือหน้า mock) หลัง login ด้วยบทบาทต่างๆ", "Admin: การ์ดสรุป...<br>Evaluator: My Assignments", "badge-functional"],
                            ["Functional", "ดูผลของตนเอง", "GET /task1/evaluation-results?user_id=3&assignment_id=10", "200 + rows เฉพาะ assignment 10", "badge-functional"],
                            ["Functional", "มอบหมายซ้ำ", "POST /task4/assignments (แทรกรายการที่มีอยู่แล้ว)", "<span style='color:#f85149;'>409 DUPLICATE_ASSIGNMENT</span>", "badge-functional"],
                            ["Security", "IDOR", "GET /task1/evaluation-results?... (ไม่ใช่เจ้าของ)", "<span style='color:#f85149;'>403 forbidden</span>", "badge-security"],
                            ["Security", "Evidence Rule", "ลบ attachments ของ result_id=101 &rarr; PATCH /task2/...", "<span style='color:#f85149;'>400 EVIDENCE_REQUIRED</span>", "badge-security"],
                            ["Non-functional", "อัปโหลดไฟล์ >10MB", "POST /me/evidence (แบบไฟล์ใหญ่กว่า 10MB)", "<span style='color:#f85149;'>413 Payload Too Large</span>", "badge-nonfunc"],
                            ["Non-functional", "ชนิดไฟล์ต้องห้าม", "POST /me/evidence แบบ .exe", "<span style='color:#f85149;'>415 Unsupported Media Type</span>", "badge-nonfunc"],
                            ["Functional", "Export รายงาน", "GET /reports/export?format=pdf", "200 + ดาวน์โหลดไฟล์ PDF/Excel ได้สำเร็จ", "badge-functional"],
                            ["Security", "SQL Injection Prevention", "POST /auth/login ด้วย Payload: ' OR '1'='1", "<span style='color:#f85149;'>401 Unauthorized</span>", "badge-security"]
                        ];
                        
                        foreach ($test_cases as $index => $tc) {
                            echo "<tr>";
                            echo "<td><span class='badge {$tc[4]}'>{$tc[0]}</span></td>";
                            echo "<td>{$tc[1]}</td>";
                            echo "<td>{$tc[2]}</td>";
                            echo "<td>{$tc[3]}</td>";
                            echo "<td style='text-align: center;'>";
                            // Input สำหรับกรอกคะแนน สั่งให้เรียกฟังก์ชันคำนวณทุกครั้งที่พิมพ์
                            echo "<input type='number' name='item_scores[]' class='score-input' min='0' max='10' value='0' oninput='calculateTotal()' required>";
                            echo "</td>";
                            echo "</tr>";
                        }
                        ?>
                        
                        <tr style="background: rgba(88, 166, 255, 0.1);">
                            <td colspan="4" style="text-align: right; font-weight: bold; color: #fff; font-size: 16px;">TOTAL SCORE:</td>
                            <td style="text-align: center; color: var(--accent); font-weight: bold; font-size: 18px;" id="total-display">
                                0 / 100
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div style="max-width: 600px; margin-top: 30px;">
                
                <div class="input-group">
                    <label>EVIDENCE (แนบไฟล์เอกสารการประเมินกลับให้ User):</label>
                    <input type="file" name="admin_evidence_file" class="file-upload-input" accept=".pdf,.jpg,.jpeg,.png,.docx,.xlsx">
                </div>

                <div class="input-group">
                    <label>COMMENT / FEEDBACK:</label>
                    <textarea name="comment" placeholder="Good job, but..." style="width: 100%; height: 80px; background: #0d1117; border: 1px solid var(--border); color: #fff; padding: 12px; border-radius: 4px; font-family: 'JetBrains Mono', monospace; outline: none;"></textarea>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary" style="flex: 2;">
                        > SUBMIT_EVALUATION
                    </button>
                    <a href="<?php echo $dashboard_url; ?>" class="btn btn-secondary" style="flex: 1; margin-top: 0; display: flex; justify-content: center; align-items: center;">
                        CANCEL
                    </a>    
                </div>
            </div>
        </form>
    </div>

    <script>
        function calculateTotal() {
            let inputs = document.querySelectorAll('.score-input');
            let total = 0;
            
            inputs.forEach(input => {
                let val = parseInt(input.value) || 0;
                // ดักจับไม่ให้พิมพ์เกิน 10 หรือติดลบ
                if (val > 10) { val = 10; input.value = 10; }
                if (val < 0) { val = 0; input.value = 0; }
                total += val;
            });
            
            // อัปเดตตัวเลขบนหน้าจอ
            document.getElementById('total-display').innerText = total + " / 100";
            // อัปเดตตัวแปร hidden เพื่อส่งไปให้ backend (save_evaluation.php)
            document.getElementById('hidden_total_score').value = total;
        }
    </script>
</body>
</html>