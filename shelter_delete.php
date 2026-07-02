<?php
/**
 * shelter_delete.php
 * ระบบลบข้อมูลศูนย์พักพิง
 */

session_start();
require_once 'config/db.php'; 

// 1. ตรวจสอบสิทธิ์การใช้งาน (Login และ Role)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// อนุญาตเฉพาะ Admin หรือ Staff เท่านั้น
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    $_SESSION['error'] = "คุณไม่มีสิทธิ์ในการลบข้อมูลนี้";
    header('Location: shelter_list.php');
    exit();
}

// 2. ตรวจสอบ Parameter ID
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);

    // (Optional) ตรวจสอบก่อนว่ามีผู้อพยพในศูนย์นี้หรือไม่
    // เพื่อแจ้งเตือนที่ชัดเจนกว่า Error ของ Database
    $check_sql = "SELECT COUNT(*) as count FROM evacuees WHERE shelter_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    
    if ($check_stmt) {
        $check_stmt->bind_param("i", $id);
        $check_stmt->execute();
        $count_result = $check_stmt->get_result()->fetch_assoc();
        $check_stmt->close();

        if ($count_result['count'] > 0) {
            $_SESSION['error'] = "ไม่สามารถลบศูนย์พักพิงนี้ได้ เนื่องจากยังมีผู้อพยพลงทะเบียนอยู่จำนวน " . number_format($count_result['count']) . " คน";
            header('Location: shelter_list.php');
            exit();
        }
    }

    // 3. ดำเนินการลบ
    $sql = "DELETE FROM shelters WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $id);
        
        try {
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $_SESSION['success'] = "ลบข้อมูลศูนย์พักพิงเรียบร้อยแล้ว";
                } else {
                    $_SESSION['error'] = "ไม่พบข้อมูลที่ต้องการลบ หรือข้อมูลอาจถูกลบไปแล้ว";
                }
            } else {
                throw new Exception($stmt->error);
            }
        } catch (Exception $e) {
            // ดักจับ Error กรณีติด Constraint อื่นๆ (เช่น Foreign Key)
            if ($conn->errno == 1451) {
                $_SESSION['error'] = "ไม่สามารถลบได้ เนื่องจากมีการใช้งานข้อมูลศูนย์นี้ในส่วนอื่นของระบบ (เช่น ประวัติการเบิกจ่าย)";
            } else {
                $_SESSION['error'] = "เกิดข้อผิดพลาดในการลบ: " . $e->getMessage();
            }
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล";
    }

} else {
    $_SESSION['error'] = "ระบุรหัสข้อมูลไม่ถูกต้อง";
}

// 4. ส่งกลับหน้ารายการ
header('Location: shelter_list.php');
exit();
?>