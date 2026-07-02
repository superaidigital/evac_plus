<?php
// File: fix_database.php
// วิธีใช้: อัปโหลดไฟล์นี้ขึ้น Server แล้วเปิดผ่าน Browser (เช่น http://localhost/evac_plus/fix_database.php)
// เสร็จแล้วให้ลบไฟล์นี้ทิ้งเพื่อความปลอดภัย

include 'config/db.php';

echo "<h3>กำลังตรวจสอบและอัปเดตฐานข้อมูล...</h3>";

function addColumnIfNotExists($conn, $table, $column, $definition) {
    $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($check->num_rows == 0) {
        $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
        if ($conn->query($sql)) {
            echo "<p style='color:green;'>[SUCCESS] เพิ่มคอลัมน์ <b>$column</b> ในตาราง $table แล้ว</p>";
        } else {
            echo "<p style='color:red;'>[ERROR] เพิ่มคอลัมน์ $column ไม่สำเร็จ: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color:gray;'>[SKIP] คอลัมน์ <b>$column</b> มีอยู่แล้ว</p>";
    }
}

// 1. เพิ่มคอลัมน์ในตาราง evacuees
addColumnIfNotExists($conn, 'evacuees', 'vulnerable_group', "VARCHAR(255) NULL COMMENT 'กลุ่มเปราะบาง (elderly,child,etc)'");
addColumnIfNotExists($conn, 'evacuees', 'status', "ENUM('active','returned','hospitalized') DEFAULT 'active' COMMENT 'สถานะปัจจุบัน'");
addColumnIfNotExists($conn, 'evacuees', 'family_head_id', "INT(11) NULL COMMENT 'รหัสหัวหน้าครอบครัว'");
addColumnIfNotExists($conn, 'evacuees', 'medical_condition', "TEXT NULL COMMENT 'โรคประจำตัว'"); 
// หมายเหตุ: บางระบบใช้ chronic_disease บางระบบใช้ medical_condition โค้ดนี้รองรับทั้งคู่โดยการเพิ่มตัวที่ขาด

// 2. สร้าง Index เพื่อความเร็ว
$conn->query("CREATE INDEX idx_status ON evacuees(status)");
$conn->query("CREATE INDEX idx_id_card ON evacuees(id_card)");

echo "<hr><h4>ดำเนินการเสร็จสิ้น! คุณสามารถกลับไปใช้งานระบบได้แล้ว</h4>";
echo "<a href='evacuee_list.php'>กลับหน้าหลัก</a>";
?>