<?php
/**
 * fix_request_db.php
 * เพิ่มคอลัมน์ user_id ที่ขาดหายไปในตาราง shelter_requests
 */
include 'config/db.php';

echo "<h3>กำลังตรวจสอบและแก้ไขตาราง shelter_requests...</h3>";

// ฟังก์ชันเพิ่มคอลัมน์ถ้ายังไม่มี
function addColumn($conn, $table, $col, $def) {
    $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
    if ($check->num_rows == 0) {
        if ($conn->query("ALTER TABLE `$table` ADD COLUMN `$col` $def")) {
            echo "<p style='color:green'>✅ เพิ่มคอลัมน์ <b>$col</b> สำเร็จ</p>";
        } else {
            echo "<p style='color:red'>❌ เพิ่มคอลัมน์ <b>$col</b> ไม่สำเร็จ: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color:gray'>ℹ️ คอลัมน์ <b>$col</b> มีอยู่แล้ว</p>";
    }
}

// เพิ่มคอลัมน์ user_id
addColumn($conn, 'shelter_requests', 'user_id', "INT(11) NULL COMMENT 'ผู้ส่งคำร้อง'");

// เพิ่ม Index ให้ user_id เพื่อความเร็วในการ JOIN
$conn->query("ALTER TABLE `shelter_requests` ADD INDEX `idx_user_id` (`user_id`)");

echo "<hr><a href='request_admin.php'>กลับไปหน้าจัดการคำร้องขอ</a>";
?>