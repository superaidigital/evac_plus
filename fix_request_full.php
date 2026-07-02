<?php
/**
 * fix_request_full.php
 * ซ่อมแซมตาราง shelter_requests ให้ครบถ้วน (เพิ่ม updated_at และอื่นๆ)
 */
include 'config/db.php';

echo "<h3>กำลังซ่อมแซมตาราง shelter_requests...</h3>";

function addCol($conn, $table, $col, $def) {
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

// 1. เพิ่มคอลัมน์ที่จำเป็นทั้งหมด
addCol($conn, 'shelter_requests', 'user_id', "INT(11) NULL COMMENT 'ผู้ส่งคำร้อง'");
addCol($conn, 'shelter_requests', 'item_name', "VARCHAR(255) NULL COMMENT 'รายการ'");
addCol($conn, 'shelter_requests', 'quantity', "INT(11) DEFAULT 1 COMMENT 'จำนวน'");
addCol($conn, 'shelter_requests', 'unit', "VARCHAR(50) NULL COMMENT 'หน่วย'");
addCol($conn, 'shelter_requests', 'urgency', "ENUM('low','medium','high','critical') DEFAULT 'medium'");
addCol($conn, 'shelter_requests', 'description', "TEXT NULL");
addCol($conn, 'shelter_requests', 'status', "ENUM('pending','approved','rejected','completed') DEFAULT 'pending'");
addCol($conn, 'shelter_requests', 'updated_at', "DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'เวลาแก้ไขล่าสุด'");

// 2. แปลงข้อมูล quantity ที่ไม่ใช่ตัวเลขให้เป็นตัวเลข (แก้ปัญหา number_format error)
$conn->query("UPDATE shelter_requests SET quantity = CAST(quantity AS UNSIGNED) WHERE quantity NOT REGEXP '^[0-9]+$'");
$conn->query("ALTER TABLE shelter_requests MODIFY quantity INT(11) DEFAULT 1");

echo "<hr><a href='request_admin.php' class='btn btn-primary'>กลับไปหน้าจัดการคำร้องขอ</a>";
?>