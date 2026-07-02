<?php
/**
 * shelter_save.php
 * บันทึกข้อมูลศูนย์พักพิง (Auto-detect & Save All Fields)
 * ปรับปรุง: ไม่นำอำเภอ/จังหวัด ไปรวมในช่องที่อยู่ (บันทึกแยกคอลัมน์เท่านั้น)
 * ปรับปรุง: แก้ไขปัญหา Foreign Key constraint fail สำหรับ incident_id
 */

session_start();
require_once 'config/db.php'; 

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. รับค่า
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $name = trim($_POST['name']);
    $capacity = intval($_POST['capacity']);
    $status = $_POST['status'];
    $contact_person = trim($_POST['contact_person']);
    
    // จัดการ incident_id: ถ้าเป็น 0 หรือว่าง ให้เป็น NULL
    // แก้ไข: ตรวจสอบค่าให้ถูกต้องเพื่อป้องกัน Foreign Key Constraint Error
    $incident_id = null;
    if (isset($_POST['incident_id']) && $_POST['incident_id'] !== '' && $_POST['incident_id'] != '0') {
        $incident_id = intval($_POST['incident_id']);
    }
    
    // เบอร์โทร
    $phone = '';
    if (isset($_POST['phone'])) $phone = trim($_POST['phone']);
    elseif (isset($_POST['contact_phone'])) $phone = trim($_POST['contact_phone']);
    
    // รับค่าที่อยู่แยกส่วน
    $location_input = trim($_POST['location'] ?? $_POST['address'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $province = trim($_POST['province'] ?? '');
    
    // พิกัด
    $latitude = !empty($_POST['latitude']) ? $_POST['latitude'] : 0.0;
    $longitude = !empty($_POST['longitude']) ? $_POST['longitude'] : 0.0;

    // ------------------------------------------------------------------
    // 2. ตรวจสอบ Column ที่มีอยู่จริง (เพื่อให้บันทึกได้ครบถ้วน)
    // ------------------------------------------------------------------
    
    // เช็ค Address/Location
    $col_address = 'address';
    if ($conn->query("SHOW COLUMNS FROM shelters LIKE 'address'")->num_rows == 0) {
        if ($conn->query("SHOW COLUMNS FROM shelters LIKE 'location'")->num_rows > 0) {
            $col_address = 'location';
        }
    }

    // เช็ค Phone
    $col_phone = 'phone';
    if ($conn->query("SHOW COLUMNS FROM shelters LIKE 'phone'")->num_rows == 0) {
        if ($conn->query("SHOW COLUMNS FROM shelters LIKE 'contact_phone'")->num_rows > 0) {
            $col_phone = 'contact_phone';
        }
    }

    // เช็ค District, Province, Incident_ID
    $has_district = ($conn->query("SHOW COLUMNS FROM shelters LIKE 'district'")->num_rows > 0);
    $has_province = ($conn->query("SHOW COLUMNS FROM shelters LIKE 'province'")->num_rows > 0);
    $has_incident = ($conn->query("SHOW COLUMNS FROM shelters LIKE 'incident_id'")->num_rows > 0);

    // *** แก้ไข: ใช้ค่าที่อยู่ตามที่กรอกมาโดยตรง ไม่ต้องเอาอำเภอ/จังหวัดมาต่อท้าย ***
    $save_address = $location_input;

    // ------------------------------------------------------------------
    // 3. สร้าง SQL Dynamic
    // ------------------------------------------------------------------
    
    $sql_fields = "";
    $types = "ssddisss"; // Types พื้นฐาน: name, address, lat, lng, cap, stat, person, phone
    $params = array($name, $save_address, $latitude, $longitude, $capacity, $status, $contact_person, $phone);

    // เพิ่มฟิลด์เสริมถ้ามีคอลัมน์ในฐานข้อมูล
    if ($has_district) {
        $sql_fields .= ", district=?";
        $types .= "s";
        $params[] = $district;
    }
    if ($has_province) {
        $sql_fields .= ", province=?";
        $types .= "s";
        $params[] = $province;
    }
    if ($has_incident) {
        $sql_fields .= ", incident_id=?";
        // ถ้า incident_id เป็น NULL ต้องระวังเรื่อง Type ใน bind_param แต่มักจะจัดการได้หากตัวแปรเป็น null
        // อย่างไรก็ตาม เพื่อความชัวร์ ถ้าเป็น null เราจะยังคงใช้ 'i' แต่ค่าจะเป็น null
        $types .= "i";
        $params[] = $incident_id;
    }

    if ($id > 0) {
        // --- UPDATE ---
        // สร้าง String สำหรับ SET เช่น ", district=?, province=?"
        $update_set = str_replace(",", ", ", $sql_fields); 
        
        $sql = "UPDATE shelters SET 
                name=?, $col_address=?, latitude=?, longitude=?, capacity=?, 
                status=?, contact_person=?, $col_phone=? $update_set 
                WHERE id=?";
        
        $types .= "i"; // สำหรับ WHERE id
        $params[] = $id;

    } else {
        // --- INSERT ---
        // สร้าง Field List และ Value Placeholders ใหม่
        $field_list = "name, $col_address, latitude, longitude, capacity, status, contact_person, $col_phone";
        $value_marks = "?, ?, ?, ?, ?, ?, ?, ?";
        
        if ($has_district) { $field_list .= ", district"; $value_marks .= ", ?"; }
        if ($has_province) { $field_list .= ", province"; $value_marks .= ", ?"; }
        if ($has_incident) { $field_list .= ", incident_id"; $value_marks .= ", ?"; }

        $sql = "INSERT INTO shelters ($field_list) VALUES ($value_marks)";
    }

    // 4. Execute
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param($types, ...$params);
    
    try {
        if ($stmt->execute()) {
            $_SESSION['success'] = ($id > 0) ? "แก้ไขข้อมูลเรียบร้อยแล้ว" : "เพิ่มข้อมูลเรียบร้อยแล้ว";
        } else {
            $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $stmt->error;
        }
    } catch (mysqli_sql_exception $e) {
        // จัดการ Error ให้ชัดเจนขึ้น
        if ($e->getCode() == 1452) { // Foreign Key constraint fails
             $_SESSION['error'] = "เกิดข้อผิดพลาด: ภารกิจที่เลือกไม่ถูกต้อง หรือไม่มีอยู่ในระบบ (FK Error)";
        } else {
             $_SESSION['error'] = "เกิดข้อผิดพลาดฐานข้อมูล: " . $e->getMessage();
        }
    }
    
    $stmt->close();
    header('Location: shelter_list.php');
    exit();

} else {
    header('Location: shelter_list.php');
    exit();
}
?>