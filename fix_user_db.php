<?php
/**
 * fix_user_db.php
 * เพิ่มคอลัมน์ที่ขาดหายไปในตาราง users (status, shelter_id)
 */
include 'config/db.php';

echo "<h3>กำลังตรวจสอบและแก้ไขตาราง Users...</h3>";

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

// เพิ่มคอลัมน์ status
addColumn($conn, 'users', 'status', "ENUM('active','inactive') DEFAULT 'active' COMMENT 'สถานะผู้ใช้'");

// เพิ่มคอลัมน์ shelter_id (เผื่อยังไม่มี)
addColumn($conn, 'users', 'shelter_id', "INT(11) NULL DEFAULT 0 COMMENT 'สังกัดศูนย์พักพิง'");

echo "<hr><a href='user_manager.php'>กลับไปหน้าจัดการผู้ใช้</a>";
?>