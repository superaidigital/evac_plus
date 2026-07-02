<?php
/**
 * fix_incident_db.php
 * เพิ่มคอลัมน์ location_scope และอื่นๆ ที่อาจขาดหายไปในตาราง incidents
 */
include 'config/db.php';

echo "<h3>กำลังตรวจสอบและแก้ไขตาราง incidents...</h3>";

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

// เพิ่มคอลัมน์ location_scope
addColumn($conn, 'incidents', 'location_scope', "VARCHAR(255) NULL COMMENT 'ขอบเขตพื้นที่ (เช่น อ.เมือง)'");
// เพิ่มคอลัมน์ start_date/end_date เผื่อยังไม่มี
addColumn($conn, 'incidents', 'start_date', "DATE NULL");
addColumn($conn, 'incidents', 'end_date', "DATE NULL");

echo "<hr><a href='incident_manager.php' class='btn btn-primary'>กลับไปหน้าจัดการเหตุการณ์</a>";
?>