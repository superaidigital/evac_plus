<?php
/**
 * fix_request_db_item.php
 * เพิ่มคอลัมน์ item_name และแก้ไขประเภทข้อมูลให้ถูกต้อง
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

// 1. เพิ่มคอลัมน์ที่ขาด
addColumn($conn, 'shelter_requests', 'item_name', "VARCHAR(255) NULL COMMENT 'ชื่อรายการที่ขอ'");
addColumn($conn, 'shelter_requests', 'quantity', "INT(11) DEFAULT 1 COMMENT 'จำนวน'");
addColumn($conn, 'shelter_requests', 'unit', "VARCHAR(50) NULL COMMENT 'หน่วยนับ'");
addColumn($conn, 'shelter_requests', 'urgency', "ENUM('low','medium','high','critical') DEFAULT 'medium' COMMENT 'ความเร่งด่วน'");
addColumn($conn, 'shelter_requests', 'description', "TEXT NULL COMMENT 'รายละเอียดเพิ่มเติม'");
addColumn($conn, 'shelter_requests', 'status', "ENUM('pending','approved','rejected','completed') DEFAULT 'pending' COMMENT 'สถานะ'");

// 2. แก้ไขข้อมูลเก่าที่ผิดรูปแบบ (เช่น "50 แพ็ค" -> "50") เพื่อแก้ปัญหา TypeError: number_format
// ดึงข้อมูลมา clean ก่อน
$fix_q = $conn->query("SELECT id, quantity FROM shelter_requests");
$count_fixed = 0;
while($row = $fix_q->fetch_assoc()) {
    $qty = $row['quantity'];
    // ถ้าไม่ใช่ตัวเลขล้วน (เช่นมีตัวหนังสือปน)
    if (!is_numeric($qty)) {
        // ดึงเฉพาะตัวเลขออกมา
        $clean_qty = (int)filter_var($qty, FILTER_SANITIZE_NUMBER_INT);
        if ($clean_qty == 0 && $qty != 0) $clean_qty = 1; // Default fallback
        
        $conn->query("UPDATE shelter_requests SET quantity = '$clean_qty' WHERE id = {$row['id']}");
        $count_fixed++;
    }
}
if ($count_fixed > 0) {
    echo "<p style='color:blue'>🔧 แก้ไขข้อมูล Quantity ที่ผิดรูปแบบจำนวน $count_fixed รายการ</p>";
}

// 3. บังคับเปลี่ยน Type ของ quantity ให้เป็น INT (เพื่อป้องกันปัญหาในอนาคต)
if ($conn->query("ALTER TABLE `shelter_requests` MODIFY `quantity` INT(11) DEFAULT 1")) {
    echo "<p style='color:green'>✅ ปรับปรุงโครงสร้างคอลัมน์ quantity เป็น INT เรียบร้อย</p>";
} else {
    echo "<p style='color:orange'>⚠️ ไม่สามารถปรับโครงสร้าง quantity ได้ (อาจมีข้อมูลที่ไม่ใช่ตัวเลขหลงเหลือ)</p>";
}

echo "<hr><a href='request_admin.php' class='btn btn-primary'>กลับไปหน้าจัดการคำร้องขอ</a>";
?>