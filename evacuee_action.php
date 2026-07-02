<?php
/**
 * evacuee_action.php
 * จัดการเปลี่ยนสถานะผู้ประสบภัย พร้อมบันทึกประวัติ "กลับ ณ ... จาก ..."
 */
session_start();
include 'config/db.php';

// ตรวจสอบสิทธิ์การใช้งาน
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php");
    exit(); 
}

$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$shelter_id = isset($_GET['shelter_id']) ? intval($_GET['shelter_id']) : 0;
$user_id = $_SESSION['user_id'];

if ($id > 0) {
    if ($action == 'checkout') {
        // 1. ดึงข้อมูลที่อยู่ + ชื่อศูนย์พักพิง
        $sql_info = "SELECT e.current_address, e.address, s.name as shelter_name 
                     FROM evacuees e 
                     LEFT JOIN shelters s ON s.id = ? 
                     WHERE e.id = ?";
        $stmt = $conn->prepare($sql_info);
        $stmt->bind_param("ii", $shelter_id, $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        
        // เลือกที่อยู่ (ถ้ามีที่อยู่ปัจจุบันเอาปัจจุบัน ถ้าไม่มีเอาภูมิลำเนา)
        $addr_log = !empty($row['current_address']) ? $row['current_address'] : $row['address'];
        $shelter_name = $row['shelter_name'] ?? 'ศูนย์พักพิง';

        // สร้างข้อความตามรูปแบบที่ต้องการ
        // "กลับ ณ [ที่อยู่] จาก [ชื่อศูนย์]"
        $details = "กลับ ณ " . ($addr_log ?: 'ไม่ระบุ') . " จาก " . $shelter_name;

        // 2. อัปเดตสถานะในตารางหลัก
        $conn->query("UPDATE evacuees SET status = 'returned', check_out_date = NOW() WHERE id = $id");
        
        // 3. บันทึกประวัติ
        $hist_sql = "INSERT INTO evacuee_history (evacuee_id, shelter_id, action, details, user_id, action_date) VALUES (?, ?, 'check_out', ?, ?, NOW())";
        $hist_stmt = $conn->prepare($hist_sql);
        $hist_stmt->bind_param("iisi", $id, $shelter_id, $details, $user_id);
        $hist_stmt->execute();
        
        $_SESSION['swal_icon'] = 'success';
        $_SESSION['swal_title'] = 'จำหน่ายกลับบ้านเรียบร้อย';
    } 
    elseif ($action == 'undo') {
        // คืนค่าสถานะ
        $conn->query("UPDATE evacuees SET status = 'active', check_out_date = NULL WHERE id = $id");
        
        $details = 'ยกเลิกการจำหน่าย (ดึงรายชื่อกลับมา Active)';
        $conn->query("INSERT INTO evacuee_history (evacuee_id, shelter_id, action, details, user_id, action_date) VALUES ($id, $shelter_id, 'undo_checkout', '$details', $user_id, NOW())");
        
        $_SESSION['swal_icon'] = 'info';
        $_SESSION['swal_title'] = 'ยกเลิกการจำหน่ายเรียบร้อย';
    }
}

// ปิด Session ก่อน Redirect
session_write_close();

header("Location: evacuee_list.php?shelter_id=" . $shelter_id);
exit();
?>