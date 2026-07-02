<?php
/**
 * evacuee_save.php
 * ปรับปรุง: บันทึกรายละเอียดการย้ายศูนย์ (ย้ายจาก..ไป.. + จำนวนวัน)
 */
include 'config/db.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = intval($_POST['id']);
    $user_id = $_SESSION['user_id'] ?? 0;
    
    // --- รับค่าจากฟอร์ม ---
    $id_card = trim($_POST['id_card'] ?? '');
    $id_type = $_POST['id_type'] ?? 'thai_id';
    $prefix = $_POST['prefix'];
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $age = intval($_POST['age'] ?? 0);
    $gender = $_POST['gender'] ?? 'male';
    
    // แปลงวันเกิด
    $birth_date_thai = trim($_POST['birth_date'] ?? '');
    $birth_date = NULL;
    if (!empty($birth_date_thai) && strpos($birth_date_thai, '-') !== false) {
        $parts = explode('-', $birth_date_thai);
        if(count($parts) == 3) {
            $y_eng = intval($parts[2]) - 543;
            $birth_date = "$y_eng-{$parts[1]}-{$parts[0]}";
        }
    }

    $shelter_id = intval($_POST['shelter_id'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    $stay_type = $_POST['stay_type'] ?? 'shelter';
    $stay_detail = trim($_POST['stay_detail'] ?? '');
    $chronic_disease = trim($_POST['chronic_disease'] ?? '');
    $medication = trim($_POST['medication'] ?? '');
    $vulnerable_group = isset($_POST['vulnerable_group']) ? implode(',', $_POST['vulnerable_group']) : '';

    // วันที่และเวลา
    $check_in_input = $_POST['check_in_date'] ?? date('Y-m-d\TH:i');
    $registered_at = date('Y-m-d H:i:s', strtotime($check_in_input));
    $check_out_date = ($status != 'active' && $status != 'hospitalized') ? date('Y-m-d H:i:s') : NULL;

    // ที่อยู่รวม
    $addr_no = trim($_POST['addr_no'] ?? ''); $addr_moo = trim($_POST['addr_moo'] ?? '');
    $addr_sub = trim($_POST['addr_sub'] ?? ''); $addr_dis = trim($_POST['addr_dis'] ?? ''); $addr_prov = trim($_POST['addr_prov'] ?? '');
    $parts = [];
    if($addr_no) $parts[] = "บ้านเลขที่ $addr_no"; if($addr_moo) $parts[] = "หมู่ $addr_moo";
    if($addr_sub) $parts[] = "ต.$addr_sub"; if($addr_dis) $parts[] = "อ.$addr_dis"; if($addr_prov) $parts[] = "จ.$addr_prov";
    $address = implode(' ', $parts);

    $curr_no = trim($_POST['curr_addr_no'] ?? ''); $curr_moo = trim($_POST['curr_addr_moo'] ?? '');
    $curr_sub = trim($_POST['curr_addr_sub'] ?? ''); $curr_dis = trim($_POST['curr_addr_dis'] ?? ''); $curr_prov = trim($_POST['curr_addr_prov'] ?? '');
    $c_parts = [];
    if($curr_no) $c_parts[] = "บ้านเลขที่ $curr_no"; if($curr_moo) $c_parts[] = "หมู่ $curr_moo";
    if($curr_sub) $c_parts[] = "ต.$curr_sub"; if($curr_dis) $c_parts[] = "อ.$curr_dis"; if($curr_prov) $c_parts[] = "จ.$curr_prov";
    $current_address = implode(' ', $c_parts);

    // --- เตรียมบันทึกประวัติ (History Logic) ---
    $history_action = '';
    $history_detail = '';
    $save_registered_at = $registered_at; // ค่าที่จะบันทึกลง DB

    if ($id == 0) {
        // INSERT
        $history_action = 'check_in';
        $history_detail = 'ลงทะเบียนเข้าพักใหม่';

        $sql = "INSERT INTO evacuees (id_type, id_card, prefix, first_name, last_name, birth_date, age, gender, phone, 
                addr_no, addr_moo, addr_sub, addr_dis, addr_prov, address,
                curr_addr_no, curr_addr_moo, curr_addr_sub, curr_addr_dis, curr_addr_prov, current_address,
                shelter_id, stay_type, stay_detail, status, check_out_date, chronic_disease, medication, vulnerable_group, registered_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssissssssssssssssissssssss", 
            $id_type, $id_card, $prefix, $first_name, $last_name, $birth_date, $age, $gender, $phone,
            $addr_no, $addr_moo, $addr_sub, $addr_dis, $addr_prov, $address,
            $curr_no, $curr_moo, $curr_sub, $curr_dis, $curr_prov, $current_address,
            $shelter_id, $stay_type, $stay_detail, $status, $check_out_date, $chronic_disease, $medication, $vulnerable_group, $registered_at
        );
    } else {
        // UPDATE
        // ดึงข้อมูลเก่าเพื่อเทียบความเปลี่ยนแปลง
        $old_res = $conn->query("SELECT e.status, e.shelter_id, e.registered_at, s.name as shelter_name 
                                 FROM evacuees e 
                                 LEFT JOIN shelters s ON e.shelter_id = s.id 
                                 WHERE e.id = $id");
        $old_data = $old_res->fetch_assoc();
        
        // 1. ตรวจสอบการย้ายศูนย์
        if ($old_data['shelter_id'] != $shelter_id) {
            $history_action = 'transfer';
            
            // ดึงชื่อศูนย์ใหม่
            $new_s_res = $conn->query("SELECT name FROM shelters WHERE id = $shelter_id");
            $new_s_name = ($new_s_res->num_rows > 0) ? $new_s_res->fetch_assoc()['name'] : 'ไม่ระบุ';
            
            // คำนวณวันที่อยู่ศูนย์เก่า
            $diff = time() - strtotime($old_data['registered_at']);
            $days = floor($diff / (60 * 60 * 24));
            $hours = floor(($diff % (60 * 60 * 24)) / (60 * 60));
            
            $duration_text = "";
            if($days > 0) $duration_text .= "$days วัน ";
            if($hours > 0) $duration_text .= "$hours ชม.";
            if(empty($duration_text)) $duration_text = "น้อยกว่า 1 ชม.";

            $history_detail = "ย้ายจาก " . ($old_data['shelter_name'] ?? 'ไม่ระบุ') . " ไปยัง " . $new_s_name . " (พักมาแล้ว " . $duration_text . ")";
            
            // เมื่อย้ายศูนย์ ให้รีเซ็ตเวลา registered_at เป็นเวลาปัจจุบัน (เริ่มนับ 1 ที่ใหม่)
            $save_registered_at = date('Y-m-d H:i:s'); 

        } 
        // 2. ตรวจสอบการเปลี่ยนสถานะ
        elseif ($old_data['status'] != $status) {
            if ($status == 'returned') { $history_action = 'check_out'; $history_detail = 'จำหน่ายกลับบ้าน'; }
            elseif ($status == 'hospitalized') { $history_action = 'refer'; $history_detail = 'ส่งต่อโรงพยาบาล'; }
            elseif ($status == 'active') { $history_action = 'check_in'; $history_detail = 'กลับเข้าพัก (Re-admit)'; $save_registered_at = date('Y-m-d H:i:s'); }
        } else {
            $history_action = 'update'; // แก้ไขทั่วไป
        }

        $sql = "UPDATE evacuees SET id_type=?, id_card=?, prefix=?, first_name=?, last_name=?, birth_date=?, age=?, gender=?, phone=?, 
                addr_no=?, addr_moo=?, addr_sub=?, addr_dis=?, addr_prov=?, address=?,
                curr_addr_no=?, curr_addr_moo=?, curr_addr_sub=?, curr_addr_dis=?, curr_addr_prov=?, current_address=?,
                shelter_id=?, stay_type=?, stay_detail=?, status=?, check_out_date=?, chronic_disease=?, medication=?, vulnerable_group=?, registered_at=? 
                WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssissssssssssssssissssssssi", 
            $id_type, $id_card, $prefix, $first_name, $last_name, $birth_date, $age, $gender, $phone,
            $addr_no, $addr_moo, $addr_sub, $addr_dis, $addr_prov, $address,
            $curr_no, $curr_moo, $curr_sub, $curr_dis, $curr_prov, $current_address,
            $shelter_id, $stay_type, $stay_detail, $status, $check_out_date, $chronic_disease, $medication, $vulnerable_group, $save_registered_at, $id
        );
    }

    if ($stmt->execute()) {
        $target_id = ($id == 0) ? $stmt->insert_id : $id;

        // บันทึกประวัติ (ถ้าไม่ใช่แค่ update ทั่วไป)
        if (!empty($history_action) && $history_action != 'update') {
            $hist_sql = "INSERT INTO evacuee_history (evacuee_id, shelter_id, action, details, action_date, user_id) VALUES (?, ?, ?, ?, ?, ?)";
            $hist_stmt = $conn->prepare($hist_sql);
            // ใช้เวลาปัจจุบันเป็นเวลาเกิดเหตุการณ์ (Log time)
            $log_time = date('Y-m-d H:i:s');
            $hist_stmt->bind_param("iisssi", $target_id, $shelter_id, $history_action, $history_detail, $log_time, $user_id);
            $hist_stmt->execute();
        }

        $_SESSION['swal_icon'] = 'success';
        
        date_default_timezone_set('Asia/Bangkok');
        $ts = strtotime($save_registered_at);
        $show_time = date('d/m/', $ts) . (date('Y', $ts)+543) . date(' H:i น.', $ts);
        
        $status_map = ['active'=>'เข้าพัก', 'returned'=>'กลับบ้าน', 'hospitalized'=>'ส่ง รพ.'];
        $status_text = $status_map[$status] ?? $status;

        $_SESSION['swal_title'] = ($id==0) ? 'ลงทะเบียนสำเร็จ' : 'บันทึกสำเร็จ';
        $_SESSION['swal_text'] = "สถานะ: $status_text\nเวลา: $show_time";

        session_write_close();
        
        if ($id == 0) header("Location: evacuee_form.php?shelter_id=" . $shelter_id);
        else header("Location: evacuee_list.php?shelter_id=" . $shelter_id);
        
    } else {
        $_SESSION['swal_icon'] = 'error';
        $_SESSION['swal_title'] = 'เกิดข้อผิดพลาด';
        $_SESSION['swal_text'] = $stmt->error;
        session_write_close();
        header("Location: evacuee_form.php?id=" . $id . "&shelter_id=" . $shelter_id);
    }
    
    exit();
}
?>