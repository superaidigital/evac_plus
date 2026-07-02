<?php
/**
 * setup_evacuee_db.php
 * สคริปต์รีเซ็ตตาราง evacuees ให้รองรับฟีเจอร์ Smart Form + ที่อยู่ละเอียด + ประเภทบัตร + รายละเอียดที่พัก + วันที่ออก
 */
include 'config/db.php';

// ปิด Foreign Key Check
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

$table_name = "evacuees";
$drop_sql = "DROP TABLE IF EXISTS `$table_name`";

$create_sql = "CREATE TABLE `$table_name` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_type` enum('thai_id','passport','no_doc') DEFAULT 'thai_id' COMMENT 'ประเภทบัตร',
  `id_card` varchar(20) NOT NULL COMMENT 'เลขบัตรประชาชน',
  `prefix` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `age` int(3) DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  
  -- ที่อยู่ภูมิลำเนา (ละเอียด)
  `addr_no` varchar(50) DEFAULT NULL,
  `addr_moo` varchar(50) DEFAULT NULL,
  `addr_sub` varchar(100) DEFAULT NULL,
  `addr_dis` varchar(100) DEFAULT NULL,
  `addr_prov` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL COMMENT 'ที่อยู่รวม (Auto generated)',

  -- ที่อยู่ปัจจุบัน (ละเอียด)
  `curr_addr_no` varchar(50) DEFAULT NULL,
  `curr_addr_moo` varchar(50) DEFAULT NULL,
  `curr_addr_sub` varchar(100) DEFAULT NULL,
  `curr_addr_dis` varchar(100) DEFAULT NULL,
  `curr_addr_prov` varchar(100) DEFAULT NULL,
  `current_address` text DEFAULT NULL COMMENT 'ที่อยู่ปัจจุบันรวม (Auto generated)',

  `shelter_id` int(11) NOT NULL,
  `stay_type` enum('shelter','outside') DEFAULT 'shelter' COMMENT 'ประเภทการพัก',
  `stay_detail` text DEFAULT NULL COMMENT 'รายละเอียดที่พัก (กรณีพักนอกศูนย์)',
  `status` enum('active','returned','hospitalized') DEFAULT 'active',
  `chronic_disease` text DEFAULT NULL,
  `medication` text DEFAULT NULL,
  `vulnerable_group` varchar(255) DEFAULT NULL COMMENT 'กลุ่มเปราะบาง CSV',
  `family_head_id` int(11) DEFAULT NULL,
  `registered_at` datetime DEFAULT current_timestamp(),
  `check_out_date` datetime DEFAULT NULL COMMENT 'วันที่ออกจากศูนย์',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_shelter` (`shelter_id`),
  KEY `idx_id_card` (`id_card`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Reset Database</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light text-center py-5">
    <div class="container">
        <div class="card shadow-sm">
            <div class="card-body">
                <h3>สถานะการติดตั้งฐานข้อมูล (Fix Unknown Column)</h3>
                <hr>
                <?php
                if ($conn->query($drop_sql) === TRUE) echo "<p class='text-warning'>Dropped old table.</p>";
                if ($conn->query($create_sql) === TRUE) {
                    echo "<div class='alert alert-success'>✅ สร้างตารางใหม่สำเร็จ! (เพิ่ม check_out_date)</div>";
                } else {
                    echo "<div class='alert alert-danger'>❌ Error: " . $conn->error . "</div>";
                }
                $conn->query("SET FOREIGN_KEY_CHECKS = 1");
                ?>
                <a href="evacuee_list.php" class="btn btn-primary mt-3">ไปที่หน้ารายชื่อ</a>
            </div>
        </div>
    </div>
</body>
</html>