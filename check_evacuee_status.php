<?php
/**
 * check_evacuee_status.php
 * API สำหรับตรวจสอบสถานะผู้ประสบภัยแบบ Real-time
 * เงื่อนไข: 1 คน ต้อง Active ได้แค่ 1 ที่เท่านั้น
 */
include 'config/db.php';
header('Content-Type: application/json');

$id_card = isset($_GET['id_card']) ? trim($_GET['id_card']) : '';
$current_shelter_id = isset($_GET['current_shelter_id']) ? intval($_GET['current_shelter_id']) : 0;

if (empty($id_card)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid ID Card']);
    exit;
}

// ค้นหาข้อมูลล่าสุดของเลขบัตรนี้ โดยดูเฉพาะรายการล่าสุด (เรียงตามเวลาลงทะเบียนล่าสุด)
$sql = "SELECT e.*, s.name as shelter_name 
        FROM evacuees e 
        LEFT JOIN shelters s ON e.shelter_id = s.id 
        WHERE e.id_card = ? 
        ORDER BY e.registered_at DESC LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_card);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    
    // ตรวจสอบสถานะปัจจุบัน
    // ถือว่า Active ถ้าสถานะเป็น 'active' (พักอยู่) หรือ 'hospitalized' (ส่งโรงพยาบาล)
    $isActive = ($row['status'] == 'active' || $row['status'] == 'hospitalized');
    
    // ตรวจสอบว่าเป็นศูนย์เดียวกันหรือไม่
    $isSameShelter = ($row['shelter_id'] == $current_shelter_id);

    $response = [
        'status' => 'found',
        'data' => $row,
        'is_active' => $isActive,
        'current_shelter_name' => $row['shelter_name'] ?? 'ไม่ระบุศูนย์'
    ];

    if ($isActive) {
        if ($isSameShelter) {
            // กรณี 1: อยู่ศูนย์นี้แล้ว และยังไม่ออก (ห้ามลงซ้ำ)
            $response['case'] = 'duplicate_here'; 
        } else {
            // กรณี 2: อยู่ศูนย์อื่น และยังไม่ออก (ต้องย้ายศูนย์ หรือแจ้งจำหน่ายที่เก่าก่อน)
            $response['case'] = 'active_elsewhere'; 
        }
    } else {
        // กรณี 3: เคยมา แต่สถานะเป็น returned (กลับบ้านแล้ว) ถือว่าจบเหตุการณ์เก่า
        // อนุญาตให้ลงทะเบียนใหม่ได้ โดยดึงข้อมูลเก่ามา Auto-fill
        $response['case'] = 'returned_user'; 
    }

    echo json_encode($response);

} else {
    // กรณี 4: ไม่เคยมีข้อมูลในระบบเลย (ลงทะเบียนใหม่)
    echo json_encode(['status' => 'not_found', 'case' => 'new_user']);
}
?>