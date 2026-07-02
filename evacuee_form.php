<?php
/**
 * evacuee_form.php
 * ฟอร์มลงทะเบียนผู้ประสบภัย (Smart Form)
 * Update: เพิ่มช่องค้นหาชื่อศูนย์พักพิง (Filter) สำหรับ Admin
 */
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once 'config/db.php';
include 'includes/header.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$mode = $id > 0 ? 'edit' : 'add';

// --- ตรวจสอบสิทธิ์ ---
$role = $_SESSION['role'] ?? 'guest';
$user_shelter_id = $_SESSION['shelter_id'] ?? 0;

// ตั้งค่า Timezone
date_default_timezone_set('Asia/Bangkok');

// --- 1. เตรียมข้อมูล Master Data ---

// 1.1 ดึงเหตุการณ์ที่ยังไม่จบภารกิจ (Active Only)
$incidents = [];
$inc_sql = "SELECT id, name FROM incidents WHERE status = 'active' ORDER BY created_at DESC";
$inc_res = $conn->query($inc_sql);
while($row = $inc_res->fetch_assoc()) {
    $incidents[] = $row;
}

// 1.2 ดึงศูนย์พักพิง (Shelters) พร้อม incident_id เพื่อใช้กรอง
$shelters_data = [];
$staff_incident_id = 0; // สำหรับเก็บ incident_id ของ staff

if ($role === 'admin') {
    // Admin: ดึงทั้งหมดมาเก็บไว้ใน JS เพื่อทำ Client-side Filtering
    $sh_sql = "SELECT id, name, incident_id FROM shelters ORDER BY name ASC";
    $sh_res = $conn->query($sh_sql);
    while($row = $sh_res->fetch_assoc()) {
        $shelters_data[] = $row;
    }
} else {
    // Staff: ดึงเฉพาะศูนย์ของตัวเอง
    $sh_sql = "SELECT s.id, s.name, s.incident_id, i.name as incident_name 
               FROM shelters s 
               LEFT JOIN incidents i ON s.incident_id = i.id 
               WHERE s.id = ?";
    $stmt = $conn->prepare($sh_sql);
    $stmt->bind_param("i", $user_shelter_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $shelters_data[] = $row;
        $staff_incident_id = $row['incident_id']; // จำค่าไว้ใช้เป็น Default
        
        // ถ้าเหตุการณ์ของ Staff ปิดไปแล้ว แต่ต้องทำงานต่อ
        $found = false;
        foreach($incidents as $inc) { if($inc['id'] == $staff_incident_id) $found = true; }
        if(!$found && $row['incident_id']) {
            $incidents[] = ['id' => $row['incident_id'], 'name' => $row['incident_name'] . ' (Closed)'];
        }
    }
}

// --- 2. เตรียมข้อมูล Default หรือ ข้อมูลเดิม (Edit Mode) ---
$data = [
    'id_type' => 'thai_id',
    'prefix' => 'นาย', 'first_name' => '', 'last_name' => '', 'id_card' => '',
    'birth_date' => '', 'birth_date_thai' => '', 'age' => '', 'gender' => 'male', 'phone' => '',
    // กำหนดค่าเริ่มต้นตามสิทธิ์
    'shelter_id' => ($role !== 'admin') ? $user_shelter_id : 0,
    'incident_id' => ($role !== 'admin') ? $staff_incident_id : 0,
    'stay_type' => 'shelter', 'stay_detail' => '',
    'status' => 'active',
    'chronic_disease' => '', 'medication' => '', 'vulnerable_group' => [],
    'addr_no'=>'','addr_moo'=>'','addr_sub'=>'','addr_dis'=>'','addr_prov'=>'',
    'curr_addr_no'=>'','curr_addr_moo'=>'','curr_addr_sub'=>'','curr_addr_dis'=>'','curr_addr_prov'=>'',
    'check_in_date' => date('Y-m-d\TH:i') 
];

function dateToThai($date) {
    if(empty($date) || $date == '0000-00-00') return '';
    $ts = strtotime($date);
    $y = date('Y', $ts) + 543;
    return date('d-m-', $ts) . $y;
}

if ($mode == 'edit') {
    // Join Shelters เพื่อเอา incident_id มาด้วย
    $stmt = $conn->prepare("SELECT e.*, s.incident_id 
                            FROM evacuees e 
                            LEFT JOIN shelters s ON e.shelter_id = s.id 
                            WHERE e.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $data = array_merge($data, $row); 
        $data['birth_date_thai'] = dateToThai($row['birth_date']);
        $data['vulnerable_group'] = !empty($row['vulnerable_group']) ? explode(',', $row['vulnerable_group']) : [];
        if(empty($data['id_type'])) $data['id_type'] = 'thai_id'; 
        
        // ใช้ incident_id จากฐานข้อมูล (ถ้ามี)
        if(isset($row['incident_id'])) {
             $data['incident_id'] = $row['incident_id'];
        }

        if(!empty($row['registered_at'])) {
            $data['check_in_date'] = date('Y-m-d\TH:i', strtotime($row['registered_at']));
        }
    }
}
?>

<!-- Dependencies -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<link rel="stylesheet" href="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dist/jquery.Thailand.min.css">

<style>
    /* Custom UI */
    .main-content { margin-left: 0 !important; width: 100% !important; padding: 20px; background-color: #f0f2f5; }
    .card { border-radius: 10px; border: none; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 20px; }
    .card-header { font-weight: 600; font-size: 1rem; border-top-left-radius: 10px !important; border-top-right-radius: 10px !important; padding: 12px 20px; }
    
    .form-label { font-size: 0.85rem; font-weight: 600; margin-bottom: 0.3rem; color: #495057; }
    .form-control, .form-select { font-size: 0.9rem; padding: 0.5rem 0.75rem; border-radius: 6px; }
    .required-mark { color: #dc3545; font-weight: bold; }
    
    .section-divider { border-bottom: 1px dashed #dee2e6; margin: 20px 0; position: relative; }
    .section-label { position: absolute; top: -12px; left: 0; background: #fff; padding-right: 10px; font-size: 0.8rem; color: #6c757d; font-weight: bold; }

    .vulnerable-box { background-color: #fff3cd; border: 1px solid #ffeeba; padding: 15px; border-radius: 8px; }
    
    /* Auto Complete Dropdown */
    .tt-menu { width: 100%; max-height: 250px; overflow-y: auto; background: #fff; border: 1px solid #ccc; border-radius: 4px; box-shadow: 0 5px 10px rgba(0,0,0,.2); margin-top: 5px; z-index: 1050; }
    .tt-suggestion { padding: 8px 15px; cursor: pointer; font-size: 0.9rem; }
    .tt-suggestion:hover { background-color: #f0f2f5; color: #0d6efd; }

    .input-icon-right { position: relative; }
    .input-loading { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); display: none; }
</style>

<div class="main-content">
    <div class="container-fluid">
        
        <form action="evacuee_save.php" method="POST" class="needs-validation" novalidate id="evacForm" onsubmit="return validateForm()">
            <!-- Hidden Fields -->
            <input type="hidden" name="id" id="evac_id" value="<?php echo $id; ?>">
            <input type="hidden" name="is_transfer" id="is_transfer" value="0"> 
            
            <?php if ($role !== 'admin'): ?>
                <!-- Staff: ส่งค่าเดิมที่ถูก Disable กลับไปด้วย -->
                <input type="hidden" name="incident_id" value="<?php echo $data['incident_id']; ?>">
                <input type="hidden" name="shelter_id" value="<?php echo $data['shelter_id']; ?>">
            <?php endif; ?>

            <!-- Action Header -->
            <div class="d-flex justify-content-between align-items-center mb-3 bg-white p-3 rounded shadow-sm">
                <div>
                    <h5 class="mb-0 text-primary fw-bold">
                        <i class="fas <?php echo $mode=='edit'?'fa-user-edit':'fa-user-plus'; ?> me-2"></i>
                        <?php echo $mode == 'edit' ? 'แก้ไขข้อมูลผู้ประสบภัย' : 'ลงทะเบียนผู้ประสบภัยรายใหม่'; ?>
                    </h5>
                    <small class="text-muted">เจ้าหน้าที่: <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo ucfirst($role); ?>)</small>
                </div>
                <div>
                    <a href="evacuee_list.php?shelter_id=<?php echo $data['shelter_id']; ?>" class="btn btn-light btn-sm border fw-bold px-3 me-2">ยกเลิก</a>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold px-4 shadow-sm"><i class="fas fa-save me-1"></i> บันทึกข้อมูล</button>
                </div>
            </div>

            <div class="row g-3">
                
                <!-- COLUMN LEFT: Personal Data & Address -->
                <div class="col-lg-7">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <i class="fas fa-id-card me-2"></i> ข้อมูลส่วนตัว & ภูมิลำเนา
                        </div>
                        <div class="card-body">
                            
                            <!-- Personal Info -->
                            <div class="row g-2 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">ประเภทเอกสาร</label>
                                    <select class="form-select form-select-sm" name="id_type" id="id_type" onchange="toggleIdValidation()">
                                        <option value="thai_id" <?php echo $data['id_type']=='thai_id'?'selected':''; ?>>บัตรประชาชน</option>
                                        <option value="passport" <?php echo $data['id_type']=='passport'?'selected':''; ?>>หนังสือเดินทาง</option>
                                        <option value="no_doc" <?php echo $data['id_type']=='no_doc'?'selected':''; ?>>ไม่มีเอกสาร</option>
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">เลขประจำตัว <span class="required-mark">*</span></label>
                                    <div class="input-icon-right">
                                        <input type="text" class="form-control form-control-sm fw-bold text-primary" name="id_card" id="id_card" 
                                               value="<?php echo htmlspecialchars($data['id_card']); ?>" required 
                                               <?php echo $mode=='edit'?'readonly':''; ?> placeholder="กรอกเลขบัตรเพื่อตรวจสอบ...">
                                        <div class="spinner-border text-primary spinner-border-sm input-loading" role="status" id="id_loading"></div>
                                    </div>
                                    <small id="id_check_msg" class="text-muted" style="font-size: 11px;"></small>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label">คำนำหน้า</label>
                                    <select class="form-select form-select-sm" name="prefix" id="prefix" required>
                                        <option value="">ระบุ</option>
                                        <?php foreach(['นาย','นาง','นางสาว','ด.ช.','ด.ญ.'] as $p) echo "<option value='$p' ".($data['prefix']==$p?'selected':'').">$p</option>"; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">ชื่อจริง <span class="required-mark">*</span></label>
                                    <input type="text" class="form-control form-control-sm" name="first_name" id="first_name" value="<?php echo htmlspecialchars($data['first_name']); ?>" required>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">นามสกุล <span class="required-mark">*</span></label>
                                    <input type="text" class="form-control form-control-sm" name="last_name" id="last_name" value="<?php echo htmlspecialchars($data['last_name']); ?>" required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">วันเกิด (พ.ศ.)</label>
                                    <input type="text" class="form-control form-control-sm" name="birth_date" id="birth_date_thai" 
                                           value="<?php echo $data['birth_date_thai']; ?>" placeholder="เช่น 30-12-2530" maxlength="10" 
                                           onkeyup="autoSlash(this)" onblur="calculateAgeFromThaiDate()">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">อายุ</label>
                                    <input type="number" class="form-control form-control-sm bg-light text-center" name="age" id="age" value="<?php echo $data['age']; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">เพศ</label>
                                    <select class="form-select form-select-sm" name="gender" id="gender">
                                        <option value="male" <?php echo $data['gender']=='male'?'selected':''; ?>>ชาย</option>
                                        <option value="female" <?php echo $data['gender']=='female'?'selected':''; ?>>หญิง</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">เบอร์โทร</label>
                                    <input type="tel" class="form-control form-control-sm" name="phone" id="phone" value="<?php echo htmlspecialchars($data['phone']); ?>">
                                </div>
                            </div>
                            
                            <div class="section-divider"><span class="section-label">ที่อยู่ตามภูมิลำเนา (Auto Search)</span></div>
                            <div class="row g-2">
                                <div class="col-3"><input type="text" class="form-control form-control-sm" name="addr_no" id="addr_no" placeholder="บ้านเลขที่" value="<?php echo htmlspecialchars($data['addr_no']); ?>" required></div>
                                <div class="col-3"><input type="text" class="form-control form-control-sm" name="addr_moo" id="addr_moo" placeholder="หมู่ที่" value="<?php echo htmlspecialchars($data['addr_moo']); ?>"></div>
                                <div class="col-6"><input type="text" class="form-control form-control-sm" name="addr_sub" id="addr_sub" placeholder="ตำบล/แขวง" value="<?php echo htmlspecialchars($data['addr_sub']); ?>" required autocomplete="off"></div>
                                <div class="col-6"><input type="text" class="form-control form-control-sm" name="addr_dis" id="addr_dis" placeholder="อำเภอ/เขต" value="<?php echo htmlspecialchars($data['addr_dis']); ?>" required autocomplete="off"></div>
                                <div class="col-6"><input type="text" class="form-control form-control-sm" name="addr_prov" id="addr_prov" placeholder="จังหวัด" value="<?php echo htmlspecialchars($data['addr_prov']); ?>" required autocomplete="off"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COLUMN RIGHT: Shelter & Health -->
                <div class="col-lg-5">
                    <div class="card h-100">
                        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-campground me-2"></i> การพักพิง & สุขภาพ</span>
                            <button type="button" class="btn btn-sm btn-light text-success py-0 px-2 fw-bold" onclick="copyAddress()">
                                <i class="fas fa-copy"></i> ใช้ที่อยู่เดิม
                            </button>
                        </div>
                        <div class="card-body">
                            
                            <!-- Current Address -->
                            <label class="form-label text-secondary small mb-1">ที่อยู่ปัจจุบัน (ที่ติดต่อได้)</label>
                            <div class="row g-2 mb-3">
                                <div class="col-3"><input type="text" class="form-control form-control-sm" name="curr_addr_no" id="curr_addr_no" placeholder="บ้านเลขที่" value="<?php echo htmlspecialchars($data['curr_addr_no']); ?>"></div>
                                <div class="col-3"><input type="text" class="form-control form-control-sm" name="curr_addr_moo" id="curr_addr_moo" placeholder="หมู่ที่" value="<?php echo htmlspecialchars($data['curr_addr_moo']); ?>"></div>
                                <div class="col-6"><input type="text" class="form-control form-control-sm" name="curr_addr_sub" id="curr_addr_sub" placeholder="ตำบล" value="<?php echo htmlspecialchars($data['curr_addr_sub']); ?>" autocomplete="off"></div>
                                <div class="col-6"><input type="text" class="form-control form-control-sm" name="curr_addr_dis" id="curr_addr_dis" placeholder="อำเภอ" value="<?php echo htmlspecialchars($data['curr_addr_dis']); ?>" autocomplete="off"></div>
                                <div class="col-6"><input type="text" class="form-control form-control-sm" name="curr_addr_prov" id="curr_addr_prov" placeholder="จังหวัด" value="<?php echo htmlspecialchars($data['curr_addr_prov']); ?>" autocomplete="off"></div>
                            </div>

                            <div class="section-divider"><span class="section-label">ข้อมูลการเข้าพัก</span></div>

                            <!-- *** ส่วนเลือกเหตุการณ์และศูนย์พักพิง (Cascading Dropdown) *** -->
                            <div class="mb-2">
                                <label class="form-label">เลือกเหตุการณ์ <span class="required-mark">*</span></label>
                                <select class="form-select form-select-sm" name="incident_id" id="incident_id" onchange="filterShelters()" <?php echo ($role!=='admin')?'disabled style="background-color: #e9ecef;"':''; ?>>
                                    <option value="">-- เลือกเหตุการณ์ --</option>
                                    <?php foreach($incidents as $inc): ?>
                                        <option value="<?php echo $inc['id']; ?>" <?php echo ($data['incident_id'] == $inc['id']) ? 'selected' : ''; ?>>
                                            <?php echo $inc['name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-2">
                                <label class="form-label">ศูนย์พักพิง <span class="required-mark">*</span></label>
                                <!-- ช่องค้นหาศูนย์ (เฉพาะ Admin) -->
                                <?php if($role === 'admin'): ?>
                                    <input type="text" id="shelter_search" class="form-control form-control-sm mb-1" placeholder="🔍 พิมพ์เพื่อค้นหาศูนย์...">
                                <?php endif; ?>

                                <select class="form-select form-select-sm" name="shelter_id" id="shelter_id" required <?php echo ($role!=='admin')?'disabled style="background-color: #e9ecef;"':''; ?>>
                                    <option value="">-- เลือกศูนย์ --</option>
                                    <!-- Options will be populated by JS -->
                                </select>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label">เวลาเข้าพัก</label>
                                    <input type="datetime-local" class="form-control form-control-sm" name="check_in_date" value="<?php echo $data['check_in_date']; ?>" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">สถานะ</label>
                                    <select class="form-select form-select-sm" name="status" id="status">
                                        <option value="active" <?php echo $data['status']=='active'?'selected':''; ?>>🟢 พักอาศัยอยู่</option>
                                        <option value="hospitalized" <?php echo $data['status']=='hospitalized'?'selected':''; ?>>🔴 ส่งโรงพยาบาล</option>
                                        <option value="returned" <?php echo $data['status']=='returned'?'selected':''; ?>>⚪ กลับบ้านแล้ว</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-2">
                                <div class="btn-group w-100 btn-group-sm" role="group">
                                    <input type="radio" class="btn-check" name="stay_type" id="stay_shelter" value="shelter" <?php echo $data['stay_type']=='shelter'?'checked':''; ?> onchange="toggleStayDetail()">
                                    <label class="btn btn-outline-secondary" for="stay_shelter">ในศูนย์</label>
                                    <input type="radio" class="btn-check" name="stay_type" id="stay_outside" value="outside" <?php echo $data['stay_type']=='outside'?'checked':''; ?> onchange="toggleStayDetail()">
                                    <label class="btn btn-outline-secondary" for="stay_outside">นอกศูนย์</label>
                                </div>
                            </div>
                            <div class="mb-2" id="stay_detail_wrapper" style="display: none;">
                                <input type="text" class="form-control form-control-sm" name="stay_detail" id="stay_detail" placeholder="ระบุสถานที่ (เช่น บ้านญาติ, วัด)..." value="<?php echo htmlspecialchars($data['stay_detail']); ?>">
                            </div>

                            <div class="section-divider"><span class="section-label">สุขภาพ & ความเปราะบาง</span></div>

                            <div class="vulnerable-box mb-3">
                                <div class="row g-2">
                                    <div class="col-4"><div class="form-check"><input class="form-check-input v-group" type="checkbox" name="vulnerable_group[]" value="elderly" id="v_elderly" <?php echo in_array('elderly',$data['vulnerable_group'])?'checked':''; ?>><label class="form-check-label small">ผู้สูงอายุ</label></div></div>
                                    <div class="col-4"><div class="form-check"><input class="form-check-input v-group" type="checkbox" name="vulnerable_group[]" value="child" id="v_child" <?php echo in_array('child',$data['vulnerable_group'])?'checked':''; ?>><label class="form-check-label small">เด็กเล็ก</label></div></div>
                                    <div class="col-4"><div class="form-check"><input class="form-check-input v-group" type="checkbox" name="vulnerable_group[]" value="disabled" id="v_disabled" <?php echo in_array('disabled',$data['vulnerable_group'])?'checked':''; ?>><label class="form-check-label small">ผู้พิการ</label></div></div>
                                    <div class="col-4"><div class="form-check"><input class="form-check-input v-group" type="checkbox" name="vulnerable_group[]" value="bedridden" id="v_bedridden" <?php echo in_array('bedridden',$data['vulnerable_group'])?'checked':''; ?>><label class="form-check-label small text-danger fw-bold">ติดเตียง</label></div></div>
                                    <div class="col-4"><div class="form-check"><input class="form-check-input v-group" type="checkbox" name="vulnerable_group[]" value="pregnant" id="v_pregnant" <?php echo in_array('pregnant',$data['vulnerable_group'])?'checked':''; ?>><label class="form-check-label small">ตั้งครรภ์</label></div></div>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-6"><input type="text" class="form-control form-control-sm" name="chronic_disease" id="chronic_disease" placeholder="โรคประจำตัว" value="<?php echo htmlspecialchars($data['chronic_disease']); ?>"></div>
                                <div class="col-6"><input type="text" class="form-control form-control-sm border-danger" name="medication" id="medication" placeholder="ยาที่แพ้/ต้องการ" value="<?php echo htmlspecialchars($data['medication']); ?>"></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div> <!-- End Row -->
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- JQuery Thailand Dependencies -->
<script type="text/javascript" src="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dependencies/JQL.min.js"></script>
<script type="text/javascript" src="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dependencies/typeahead.bundle.js"></script>
<script type="text/javascript" src="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dist/jquery.Thailand.min.js"></script>

<script>
    // Prepare Data for JS filtering
    var allShelters = <?php echo json_encode($shelters_data); ?>;
    var userRole = '<?php echo $role; ?>';
    var selectedShelterId = '<?php echo $data['shelter_id']; ?>';

    $(document).ready(function() {
        toggleStayDetail();
        toggleIdValidation();
        filterShelters(); // Initial Filter

        // 1. Auto-Complete Address
        $.Thailand({ $district: $('#addr_sub'), $amphoe: $('#addr_dis'), $province: $('#addr_prov') });
        $.Thailand({ $district: $('#curr_addr_sub'), $amphoe: $('#curr_addr_dis'), $province: $('#curr_addr_prov') });

        // 2. Shelter Search Filter (For Admin) - เพิ่มฟีเจอร์ค้นหา
        $('#shelter_search').on('input', function() {
            filterShelters();
        });

        // 3. Auto Check & Fill (เมื่อกรอกเลขบัตร)
        $('#id_card').on('blur', function() {
            var id = $(this).val();
            // ถ้า Staff ดึงค่าจาก hidden หรือ select ที่ disable (ซึ่ง value ยังอยู่)
            var currentShelter = $('#shelter_id').val() || '<?php echo $user_shelter_id; ?>';
            var mode = '<?php echo $mode; ?>';

            if(id.length >= 10 && mode === 'add') { 
                $('#id_loading').show();
                $('#id_check_msg').text('กำลังตรวจสอบ...');
                
                $.ajax({
                    url: 'check_evacuee_status.php',
                    type: 'GET',
                    data: { id_card: id, current_shelter_id: currentShelter },
                    dataType: 'json',
                    success: function(res) {
                        $('#id_loading').hide();
                        if (res.status === 'found') {
                            fillFormData(res.data);
                            if (res.case === 'duplicate_here') {
                                $('#id_check_msg').html('<span class="text-danger"><i class="fas fa-exclamation-circle"></i> มีชื่อในระบบแล้ว (Active)</span>');
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'มีชื่อในระบบแล้ว',
                                    text: 'บุคคลนี้พักอยู่ที่ศูนย์นี้แล้ว สถานะ: ' + res.data.status,
                                    confirmButtonText: 'แก้ไขข้อมูล'
                                });
                                $('#evac_id').val(res.data.id);
                            } else if (res.case === 'active_elsewhere') {
                                $('#id_check_msg').html('<span class="text-warning"><i class="fas fa-exclamation-triangle"></i> อยู่ที่ ' + res.current_shelter_name + '</span>');
                                Swal.fire({
                                    icon: 'question',
                                    title: 'พักอยู่ที่ศูนย์อื่น',
                                    html: 'ปัจจุบันพักอยู่ที่ <b>' + res.current_shelter_name + '</b><br>ต้องการย้ายมาศูนย์นี้ใช่หรือไม่?',
                                    showCancelButton: true,
                                    confirmButtonText: 'ยืนยันย้าย',
                                    cancelButtonText: 'ยกเลิก'
                                }).then((r) => {
                                    if(r.isConfirmed) { $('#is_transfer').val('1'); $('#evac_id').val(res.data.id); } 
                                    else { document.getElementById("evacForm").reset(); }
                                });
                            } else {
                                $('#id_check_msg').html('<span class="text-success">ดึงข้อมูลเก่า</span>');
                                $('#evac_id').val(res.data.id);
                                $('#status').val('active');
                            }
                        } else {
                            $('#id_check_msg').html('<span class="text-success">รายใหม่</span>');
                        }
                    },
                    error: function() { $('#id_loading').hide(); }
                });
            }
        });

        // Popup Logic
        <?php if(isset($_SESSION['swal_icon'])): ?>
            Swal.fire({
                icon: <?php echo json_encode($_SESSION['swal_icon']); ?>,
                title: <?php echo json_encode($_SESSION['swal_title']); ?>,
                text: <?php echo json_encode($_SESSION['swal_text']); ?>,
                timer: 4000,
                showConfirmButton: true
            });
            <?php unset($_SESSION['swal_icon'], $_SESSION['swal_title'], $_SESSION['swal_text']); ?>
        <?php endif; ?>
    });

    // Function to filter shelters based on selected Incident and Search Text
    function filterShelters() {
        var incidentId = $('#incident_id').val();
        var shelterSelect = $('#shelter_id');
        var searchText = $('#shelter_search').val() ? $('#shelter_search').val().toLowerCase() : '';
        
        shelterSelect.empty();
        
        // ถ้าเป็น Admin และยังไม่เลือกเหตุการณ์
        if (userRole === 'admin' && !incidentId) {
             shelterSelect.append('<option value="">-- กรุณาเลือกเหตุการณ์ก่อน --</option>');
             return;
        }

        shelterSelect.append('<option value="">-- เลือกศูนย์ --</option>');
        
        // กรองข้อมูลจาก array ที่เตรียมไว้ (Client-side)
        var filtered = allShelters.filter(function(s) {
            // เงื่อนไข 1: ต้องตรงกับเหตุการณ์
            var matchIncident = (s.incident_id == incidentId);
            
            // เงื่อนไข 2: ค้นหาชื่อศูนย์ (ถ้ามีการพิมพ์ค้นหา)
            var matchSearch = true;
            if(searchText && userRole === 'admin') {
                matchSearch = s.name.toLowerCase().indexOf(searchText) > -1;
            }
            
            return matchIncident && matchSearch;
        });

        filtered.forEach(function(s) {
            var selected = (s.id == selectedShelterId) ? 'selected' : '';
            shelterSelect.append('<option value="' + s.id + '" ' + selected + '>' + s.name + '</option>');
        });

        // ถ้ามีเลือกไว้แต่ไม่อยู่ใน list (เช่น เปลี่ยนเหตุการณ์) ให้ reset
        if (shelterSelect.find('option[selected]').length === 0) {
             // shelterSelect.val(''); 
        }
    }

    function fillFormData(d) {
        $('#prefix').val(d.prefix); $('#first_name').val(d.first_name); $('#last_name').val(d.last_name);
        if(d.birth_date && d.birth_date != '0000-00-00') {
            var p = d.birth_date.split('-');
            $('#birth_date_thai').val(p[2] + '-' + p[1] + '-' + (parseInt(p[0]) + 543));
        }
        $('#age').val(d.age); $('#gender').val(d.gender); $('#phone').val(d.phone);
        $('#addr_no').val(d.addr_no); $('#addr_moo').val(d.addr_moo); $('#addr_sub').val(d.addr_sub); $('#addr_dis').val(d.addr_dis); $('#addr_prov').val(d.addr_prov);
        $('#chronic_disease').val(d.chronic_disease); $('#medication').val(d.medication);
        if(d.vulnerable_group) {
            d.vulnerable_group.split(',').forEach(g => $('input[value="'+g+'"]').prop('checked', true));
        }
    }

    function autoSlash(ele) {
        var val = ele.value.replace(/\D/g, '').substring(0,8);
        var newVal = '';
        if(val.length > 4) newVal = val.substr(0, 2) + '-' + val.substr(2, 2) + '-' + val.substr(4, 4);
        else if(val.length > 2) newVal = val.substr(0, 2) + '-' + val.substr(2);
        else newVal = val;
        ele.value = newVal;
    }

    function calculateAgeFromThaiDate() {
        var dateStr = $('#birth_date_thai').val();
        if(dateStr.length === 10) {
            var p = dateStr.split('-');
            var engYear = parseInt(p[2]) - 543;
            var today = new Date();
            var age = today.getFullYear() - engYear;
            if (today.getMonth() + 1 < parseInt(p[1]) || (today.getMonth() + 1 == parseInt(p[1]) && today.getDate() < parseInt(p[0]))) age--;
            $('#age').val(age);
            $('#v_elderly').prop('checked', age >= 60);
            $('#v_child').prop('checked', age <= 10);
        }
    }

    function toggleIdValidation() {
        var type = $('#id_type').val();
        if(type === 'thai_id') $('#id_card').attr('maxlength', '13').attr('pattern', '\\d{13}');
        else $('#id_card').removeAttr('maxlength').removeAttr('pattern');
    }

    function validateForm() { return true; } 

    function toggleStayDetail() {
        if ($('#stay_outside').is(':checked')) { $('#stay_detail_wrapper').slideDown(); $('#stay_detail').prop('required', true); }
        else { $('#stay_detail_wrapper').slideUp(); $('#stay_detail').prop('required', false); }
    }

    function copyAddress() {
        $('#curr_addr_no').val($('#addr_no').val()); $('#curr_addr_moo').val($('#addr_moo').val());
        $('#curr_addr_sub').val($('#addr_sub').val()); $('#curr_addr_dis').val($('#addr_dis').val()); $('#curr_addr_prov').val($('#addr_prov').val());
    }
</script>
<?php include 'includes/footer.php'; ?>