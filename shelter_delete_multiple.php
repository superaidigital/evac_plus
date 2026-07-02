<?php
/**
 * shelter_delete_multiple.php
 * ระบบลบข้อมูลศูนย์พักพิงแบบหลายรายการ
 */

session_start();
require_once 'config/db.php'; 

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    $_SESSION['error'] = "คุณไม่มีสิทธิ์ในการลบข้อมูลนี้";
    header('Location: shelter_list.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ids'])) {
    
    $ids = $_POST['ids'];
    $success_count = 0;
    $error_count = 0;
    
    // เตรียม Statement
    $stmt = $conn->prepare("DELETE FROM shelters WHERE id = ?");
    
    // ตรวจสอบ Foreign Key (Optionally) หรือให้ Database จัดการ
    // วนลูป ID เพื่อลบทีละรายการ
    foreach ($ids as $id) {
        $id = intval($id);
        
        // เช็คก่อนว่ามีคนอยู่ไหม (เพื่อความปลอดภัย)
        $check_sql = "SELECT COUNT(*) as count FROM evacuees WHERE shelter_id = $id";
        $check_res = $conn->query($check_sql);
        $has_people = ($check_res && $check_res->fetch_assoc()['count'] > 0);
        
        if (!$has_people) {
            $stmt->bind_param("i", $id);
            try {
                if ($stmt->execute()) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            } catch (Exception $e) {
                $error_count++;
            }
        } else {
            $error_count++; // ข้ามเพราะมีคนอยู่
        }
    }
    
    $stmt->close();
    
    if ($success_count > 0) {
        $_SESSION['success'] = "ลบข้อมูลสำเร็จ $success_count รายการ" . ($error_count > 0 ? " (ล้มเหลว $error_count รายการ เนื่องจากมีผู้อพยพตกค้าง)" : "");
    } else {
        $_SESSION['error'] = "ไม่สามารถลบข้อมูลได้ (อาจมีผู้อพยพตกค้างอยู่ในศูนย์ที่เลือก)";
    }

} else {
    $_SESSION['error'] = "ไม่ได้เลือกรายการที่ต้องการลบ";
}

header('Location: shelter_list.php');
exit();
?>