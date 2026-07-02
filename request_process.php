<?php
/**
 * request_process.php
 * ประมวลผลสถานะคำร้องขอ และส่งกลับรายละเอียดสถานะ+เวลา
 */
include 'config/db.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

$valid_actions = ['approved', 'rejected', 'completed', 'pending'];

if ($id > 0 && in_array($action, $valid_actions)) {
    
    // ตรวจสอบคอลัมน์ updated_at
    $check_col = $conn->query("SHOW COLUMNS FROM shelter_requests LIKE 'updated_at'");
    $has_updated_at = ($check_col && $check_col->num_rows > 0);

    if ($has_updated_at) {
        $sql = "UPDATE shelter_requests SET status = ?, updated_at = NOW() WHERE id = ?";
    } else {
        $sql = "UPDATE shelter_requests SET status = ? WHERE id = ?";
    }

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("si", $action, $id);
        
        if ($stmt->execute()) {
            // ตั้งค่า Timezone และจัดรูปแบบเวลา
            date_default_timezone_set('Asia/Bangkok');
            $time_str = date('d/m/Y H:i น.');

            // แปลงสถานะเป็นข้อความไทย
            $status_text = $action;
            if ($action == 'approved') $status_text = 'อนุมัติแล้ว (Approved)';
            elseif ($action == 'rejected') $status_text = 'ปฏิเสธ (Rejected)';
            elseif ($action == 'completed') $status_text = 'เสร็จสิ้น (Completed)';

            $_SESSION['swal_icon'] = 'success';
            $_SESSION['swal_title'] = 'ดำเนินการสำเร็จ';
            // ข้อความที่จะแสดงใน Popup (รองรับ \n)
            $_SESSION['swal_text'] = "สถานะใหม่: $status_text\nเวลาที่บันทึก: $time_str";
            
        } else {
            $_SESSION['swal_icon'] = 'error';
            $_SESSION['swal_title'] = 'เกิดข้อผิดพลาด';
            $_SESSION['swal_text'] = $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['swal_icon'] = 'error';
        $_SESSION['swal_title'] = 'Database Error';
        $_SESSION['swal_text'] = $conn->error;
    }
    
} else {
    $_SESSION['swal_icon'] = 'error';
    $_SESSION['swal_title'] = 'ข้อมูลไม่ถูกต้อง';
    $_SESSION['swal_text'] = 'ไม่พบรหัสคำร้องหรือคำสั่งไม่ถูกต้อง';
}

// บันทึก Session ทันทีป้องกันการหาย
session_write_close();

// Redirect กลับไปหน้าจัดการ
header("Location: request_admin.php");
exit();
?>