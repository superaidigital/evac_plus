<?php
/**
 * user_save.php
 * ประมวลผลการ เพิ่ม/ลบ/แก้ไข ผู้ใช้งาน
 */
include 'config/db.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }

// ตรวจสอบสิทธิ์ (ถ้ามีระบบ Login)
// if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$action = $_REQUEST['action'] ?? 'save';

// --- กรณีลบข้อมูล (DELETE) ---
if ($action == 'delete') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    // ป้องกันการลบ Admin หลัก (ID 1)
    if ($id == 1) {
        $_SESSION['swal_icon'] = 'error';
        $_SESSION['swal_title'] = 'ผิดพลาด';
        $_SESSION['swal_text'] = 'ไม่สามารถลบ Super Admin ได้';
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $_SESSION['swal_icon'] = 'success';
            $_SESSION['swal_title'] = 'ลบสำเร็จ';
            $_SESSION['swal_text'] = 'ผู้ใช้งานถูกลบออกจากระบบแล้ว';
        } else {
            $_SESSION['swal_icon'] = 'error';
            $_SESSION['swal_title'] = 'เกิดข้อผิดพลาด';
            $_SESSION['swal_text'] = $stmt->error;
        }
        $stmt->close();
    }
    
    header("Location: user_manager.php");
    exit();
}

// --- กรณีบันทึกข้อมูล (INSERT / UPDATE) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $username = trim($_POST['username']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $role = $_POST['role'];
    $shelter_id = isset($_POST['shelter_id']) ? intval($_POST['shelter_id']) : 0;
    
    // แก้ไข Warning: Undefined array key "status"
    // Checkbox ถ้าไม่ติ๊กจะไม่ส่งค่ามา เลยต้องใช้ isset ตรวจสอบ
    $status = isset($_POST['status']) ? 'active' : 'inactive'; 
    
    $password = $_POST['password'];

    // Validation
    if (empty($username) || empty($first_name)) {
        $_SESSION['swal_icon'] = 'warning';
        $_SESSION['swal_title'] = 'ข้อมูลไม่ครบ';
        $_SESSION['swal_text'] = 'กรุณากรอก Username และชื่อให้ครบถ้วน';
        header("Location: user_form.php?id=" . $id);
        exit();
    }

    try {
        if ($id == 0) {
            // --- เพิ่มใหม่ (INSERT) ---
            
            // เช็ค Username ซ้ำ
            $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $check->bind_param("s", $username);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $_SESSION['swal_icon'] = 'error';
                $_SESSION['swal_title'] = 'Username ซ้ำ';
                $_SESSION['swal_text'] = 'ชื่อผู้ใช้นี้มีในระบบแล้ว กรุณาใช้ชื่ออื่น';
                header("Location: user_form.php");
                exit();
            }

            // Hash Password
            $pwd_hash = password_hash($password, PASSWORD_DEFAULT);

            // ใช้ INSERT แบบระบุ Column ชัดเจน (รองรับโครงสร้างใหม่)
            $sql = "INSERT INTO users (username, password, first_name, last_name, role, shelter_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                // Fallback กรณีตาราง users ยังไม่มี col status หรือ shelter_id (DB เก่า)
                $sql = "INSERT INTO users (username, password, first_name, last_name, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssss", $username, $pwd_hash, $first_name, $last_name, $role);
            } else {
                $stmt->bind_param("sssssis", $username, $pwd_hash, $first_name, $last_name, $role, $shelter_id, $status);
            }

            if ($stmt->execute()) {
                $_SESSION['swal_icon'] = 'success';
                $_SESSION['swal_title'] = 'เพิ่มสำเร็จ';
                $_SESSION['swal_text'] = "ผู้ใช้งาน $username พร้อมใช้งานแล้ว";
            } else {
                throw new Exception($stmt->error);
            }

        } else {
            // --- แก้ไข (UPDATE) ---
            
            if (!empty($password)) {
                // กรณีเปลี่ยนรหัสผ่าน
                $pwd_hash = password_hash($password, PASSWORD_DEFAULT);
                
                $sql = "UPDATE users SET first_name=?, last_name=?, role=?, shelter_id=?, status=?, password=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    // Fallback DB เก่า
                    $sql = "UPDATE users SET first_name=?, last_name=?, role=?, password=? WHERE id=?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssssi", $first_name, $last_name, $role, $pwd_hash, $id);
                } else {
                    $stmt->bind_param("sssisss", $first_name, $last_name, $role, $shelter_id, $status, $pwd_hash, $id);
                }
            } else {
                // กรณีไม่เปลี่ยนรหัสผ่าน (ตัด password ออกจาก query)
                $sql = "UPDATE users SET first_name=?, last_name=?, role=?, shelter_id=?, status=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    // Fallback DB เก่า
                    $sql = "UPDATE users SET first_name=?, last_name=?, role=? WHERE id=?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sssi", $first_name, $last_name, $role, $id);
                } else {
                    $stmt->bind_param("sssisi", $first_name, $last_name, $role, $shelter_id, $status, $id);
                }
            }

            if ($stmt->execute()) {
                $_SESSION['swal_icon'] = 'success';
                $_SESSION['swal_title'] = 'แก้ไขสำเร็จ';
                $_SESSION['swal_text'] = 'ข้อมูลผู้ใช้งานได้รับการอัปเดต';
            } else {
                throw new Exception($stmt->error);
            }
        }
        
    } catch (Exception $e) {
        $_SESSION['swal_icon'] = 'error';
        $_SESSION['swal_title'] = 'เกิดข้อผิดพลาด';
        $_SESSION['swal_text'] = $e->getMessage();
        header("Location: user_form.php?id=" . $id);
        exit();
    }
    
    // บันทึก Session ก่อน Redirect
    session_write_close();
    
    header("Location: user_manager.php");
    exit();
}
?>