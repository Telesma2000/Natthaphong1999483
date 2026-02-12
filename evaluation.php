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
        
        /* ไม่จำเป็นต้องใช้ .file-upload-input เดิมแล้ว เพราะเรามี class ใหม่ใน style.css */
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

            <h3 class="code-font" style="color: #fff; margin-top: 40px;">> EVALUATION_CRITERIA (เกณฑ์การให้คะแนน)</h3>
            <div class="table-wrapper">
                <table class="dev-table">
                    <thead>
                        <tr>
                            <th>หมวด</th>
                            <th>หัวข้อ (Topic)</th>
                            <th>สิ่งที่ต้องทำ (Action)</th> <th>ผลลัพธ์ที่ควรได้ (Result)</th> <th style="text-align: center;">คะแนน (เต็ม 10)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // --- [จุดแก้ไข]: เปลี่ยนข้อความให้เป็นภาษาที่เข้าใจง่าย ---
                        $test_cases = [
                            ["Functional", "ระบบสมาชิก", "ทดสอบสมัครสมาชิกและ Login", "เข้าสู่ระบบสำเร็จ / ข้อมูลถูกต้อง", "badge-functional"],
                            ["Functional", "การแสดงผลหน้าแรก", "เข้าหน้าแรกด้วยสิทธิ์ Admin/Evaluator/User", "เห็นเมนูถูกต้องตามสิทธิ์ตัวเอง", "badge-functional"],
                            ["Functional", "ดูคะแนนตัวเอง", "User เข้าไปดูผลคะแนน", "เห็นเฉพาะคะแนนของตัวเองเท่านั้น", "badge-functional"],
                            ["Functional", "การบันทึกข้อมูล", "ลองกรอกข้อมูลหรือกดบันทึกซ้ำๆ", "ระบบป้องกันการบันทึกซ้ำ", "badge-functional"],
                            ["Security", "แอบดูข้อมูลคนอื่น", "ลองเปลี่ยน ID ใน URL เพื่อดูข้อมูลคนอื่น", "ระบบบล็อค (Access Denied)", "badge-security"],
                            ["Security", "การจัดการไฟล์", "ลองลบไฟล์สำคัญออกจากระบบ", "ลบไม่ได้ / ระบบแจ้งเตือน", "badge-security"],
                            ["Non-functional", "ขนาดไฟล์", "อัปโหลดไฟล์ขนาดใหญ่เกิน 10MB", "แจ้งเตือน 'ไฟล์ใหญ่เกินไป'", "badge-nonfunc"],
                            ["Non-functional", "ประเภทไฟล์", "อัปโหลดไฟล์แปลกๆ เช่น .exe", "แจ้งเตือน 'นามสกุลไฟล์ไม่ถูกต้อง'", "badge-nonfunc"],
                            ["Functional", "ดาวน์โหลดรายงาน", "ทดลองกดปุ่มดาวน์โหลด/เปิดไฟล์", "ไฟล์เปิดได้ถูกต้อง ไม่เสียหาย", "badge-functional"],
                            ["Security", "ความปลอดภัย Login", "ลองใส่รหัสแปลกๆ (' OR 1=1) หน้า Login", "เข้าสู่ระบบไม่ได้ / ระบบปลอดภัย", "badge-security"]
                        ];
                        // --------------------------------------------------------
                        
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
                    
                    <label for="admin_file" class="custom-file-upload">
                        <span class="upload-icon">📎</span> แนบไฟล์ผลการประเมิน (Attach File)
                    </label>
                    
                    <input type="file" name="admin_evidence_file" id="admin_file" accept=".pdf,.jpg,.jpeg,.png,.docx,.xlsx" onchange="showFileName(this, 'admin-file-name')">
                    
                    <span id="admin-file-name" class="file-name-display">...</span>
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

        // --- [จุดที่แก้ไข]: Script สำหรับโชว์ชื่อไฟล์ ---
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
        // ---------------------------------------------
    </script>
    <script src="background.js"></script>
</body>
</html>