<?php
/**
 * evacuee_delete.php
 * ลบข้อมูลผู้ประสบภัย โดยตรวจสอบสิทธิ์และ ID
 */
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    // ดึง shelter_id ก่อนลบ เพื่อ redirect กลับไปถูกที่
    $check = $conn->query("SELECT shelter_id FROM evacuees WHERE id = $id");
    $shelter_id = ($check->num_rows > 0) ? $check->fetch_assoc()['shelter_id'] : 0;

    $stmt = $conn->prepare("DELETE FROM evacuees WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "ลบข้อมูลเรียบร้อยแล้ว";
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการลบ: " . $conn->error;
    }
    
    $stmt->close();
    header("Location: evacuee_list.php?shelter_id=" . $shelter_id);
} else {
    header("Location: evacuee_list.php");
}
exit();
?>