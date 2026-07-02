<?php
/**
 * setup_announcement_db_update.php
 * เพิ่มตารางสำหรับเก็บไฟล์แนบและลิ้งค์ของประกาศ
 */
include 'config/db.php';

echo "<h3>กำลังอัปเดตฐานข้อมูลระบบประกาศ...</h3>";

// สร้างตารางเก็บไฟล์แนบ
$sql = "CREATE TABLE IF NOT EXISTS `announcement_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `announcement_id` int(11) NOT NULL,
  `file_type` enum('image','file','link') NOT NULL COMMENT 'ประเภท: รูป, ไฟล์, ลิ้งค์',
  `file_path` text DEFAULT NULL COMMENT 'ที่อยู่ไฟล์ หรือ URL',
  `file_name` varchar(255) DEFAULT NULL COMMENT 'ชื่อไฟล์ที่แสดง',
  `uploaded_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_announcement` (`announcement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green'>✅ สร้างตาราง announcement_attachments สำเร็จ</p>";
} else {
    echo "<p style='color:red'>❌ Error: " . $conn->error . "</p>";
}

// สร้างโฟลเดอร์เก็บไฟล์ถ้ายังไม่มี
$upload_dir = 'uploads/announcements/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
    echo "<p style='color:green'>✅ สร้างโฟลเดอร์ uploads/announcements/ สำเร็จ</p>";
}

echo "<hr><a href='announcement_manager.php' class='btn btn-primary'>ไปที่หน้าจัดการประกาศ</a>";
?>