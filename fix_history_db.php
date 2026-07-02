<?php
/**
 * fix_history_db.php
 * เพิ่มคอลัมน์ details ที่ขาดหายไปในตาราง evacuee_history
 */
include 'config/db.php';

echo "<h3>กำลังตรวจสอบและแก้ไขตาราง evacuee_history...</h3>";

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

// เพิ่มคอลัมน์ details
addColumn($conn, 'evacuee_history', 'details', "TEXT NULL COMMENT 'รายละเอียดเพิ่มเติม'");

echo "<hr><a href='evacuee_list.php' class='btn btn-primary'>กลับหน้าหลัก</a>";
?>