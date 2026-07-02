<?php
include 'config/db.php';

echo "<h3>กำลังตั้งค่าตารางประวัติ (Evacuee History)...</h3>";

$sql = "CREATE TABLE IF NOT EXISTS `evacuee_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `evacuee_id` int(11) NOT NULL COMMENT 'รหัสผู้ประสบภัย',
  `shelter_id` int(11) NOT NULL COMMENT 'รหัสศูนย์ที่เกี่ยวข้อง',
  `action` enum('check_in','check_out','transfer','update','refer') NOT NULL COMMENT 'กิจกรรม',
  `details` text DEFAULT NULL COMMENT 'รายละเอียดเพิ่มเติม',
  `action_date` datetime DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL COMMENT 'ผู้ทำรายการ',
  PRIMARY KEY (`id`),
  KEY `idx_evacuee` (`evacuee_id`),
  KEY `idx_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if ($conn->query($sql) === TRUE) {
    echo "<div style='color:green; padding:10px; border:1px solid green; background:#eaffea;'>
            ✅ สร้างตาราง <b>evacuee_history</b> เรียบร้อยแล้ว
          </div>";
} else {
    echo "<div style='color:red;'>❌ Error: " . $conn->error . "</div>";
}

echo "<br><a href='evacuee_list.php'>กลับหน้าหลัก</a>";
?>