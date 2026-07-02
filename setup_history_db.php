<?php
include 'config/db.php';

echo "<h3>กำลังตั้งค่าระบบประวัติ...</h3>";

// 1. สร้างตารางประวัติการเข้าพัก (เพื่อเก็บ Log การเข้า-ออก)
$sql = "CREATE TABLE IF NOT EXISTS `evacuee_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `evacuee_id` int(11) NOT NULL,
  `shelter_id` int(11) NOT NULL,
  `action` enum('check_in','check_out','transfer','undo_checkout') NOT NULL,
  `action_date` datetime DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL COMMENT 'เจ้าหน้าที่ที่ทำรายการ',
  PRIMARY KEY (`id`),
  KEY `idx_evacuee` (`evacuee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green'>✅ สร้างตาราง evacuee_history สำเร็จ</p>";
} else {
    echo "<p style='color:red'>❌ Error: " . $conn->error . "</p>";
}

echo "<a href='evacuee_list.php'>กลับหน้าหลัก</a>";
?>