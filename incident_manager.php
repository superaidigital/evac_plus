<?php
/**
 * incident_manager.php
 * หน้าจัดการเหตุการณ์ภัยพิบัติ (CRUD)
 * ปรับปรุง: แยกโหมด "ดูรายละเอียด" (Read-Only) ออกจาก "แก้ไข"
 * ปรับปรุง: แก้ไขการส่ง JSON Data ให้รองรับอักขระพิเศษ
 * ปรับปรุง: เพิ่มฟีเจอร์ "จบภารกิจ" (Close Mission) พร้อมจำหน่ายคนออกอัตโนมัติ
 */

require_once 'includes/header.php';

// ตรวจสอบสิทธิ์ (เฉพาะ Admin/Staff)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    echo "<script>window.location='index.php';</script>";
    exit();
}

// ---------------------------------------------------------------------------
// HANDLE POST REQUESTS
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    
    // --- 1. บันทึกข้อมูล (Save: Add/Edit) ---
    if ($_POST['action'] == 'save') {
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        
        // ตรวจสอบชื่อซ้ำ (Duplicate Check)
        $check_sql = "SELECT id FROM incidents WHERE name = ? AND id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $name, $id);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            $_SESSION['swal_error'] = "ชื่อเหตุการณ์ '$name' มีอยู่แล้วในระบบ กรุณาใช้ชื่ออื่น";
            echo "<script>window.location='incident_manager.php';</script>";
            exit();
        }
        $check_stmt->close();

        $type = $_POST['type'];
        $severity_level = $_POST['severity_level'] ?? 'medium'; 
        $status = $_POST['status'];
        $location_scope = trim($_POST['location_scope']);
        $start_date = $_POST['start_date'];
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : NULL;
        $description = trim($_POST['description']);

        $has_severity = ($conn->query("SHOW COLUMNS FROM incidents LIKE 'severity_level'")->num_rows > 0);

        if ($id == 0) {
            // INSERT
            if ($has_severity) {
                $sql = "INSERT INTO incidents (name, type, severity_level, status, location_scope, start_date, end_date, description, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssss", $name, $type, $severity_level, $status, $location_scope, $start_date, $end_date, $description);
            } else {
                $sql = "INSERT INTO incidents (name, type, status, location_scope, start_date, end_date, description, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssss", $name, $type, $status, $location_scope, $start_date, $end_date, $description);
            }
        } else {
            // UPDATE
            if ($has_severity) {
                $sql = "UPDATE incidents SET name=?, type=?, severity_level=?, status=?, location_scope=?, start_date=?, end_date=?, description=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssssi", $name, $type, $severity_level, $status, $location_scope, $start_date, $end_date, $description, $id);
            } else {
                $sql = "UPDATE incidents SET name=?, type=?, status=?, location_scope=?, start_date=?, end_date=?, description=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssssi", $name, $type, $status, $location_scope, $start_date, $end_date, $description, $id);
            }
        }

        if ($stmt->execute()) {
            $_SESSION['swal_success'] = "บันทึกข้อมูลสำเร็จ";
        } else {
            $_SESSION['swal_error'] = "เกิดข้อผิดพลาด: " . $stmt->error;
        }
        
        echo "<script>window.location='incident_manager.php';</script>";
        exit();
    } 
    
    // --- 2. จบภารกิจ (Close Mission) ---
    elseif ($_POST['action'] == 'close_mission') {
        $id = intval($_POST['id']);
        $confirm_password = $_POST['confirm_password'];
        $user_id = $_SESSION['user_id'];

        // ตรวจสอบรหัสผ่านผู้ใช้งานปัจจุบัน
        $stmt_user = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt_user->bind_param("i", $user_id);
        $stmt_user->execute();
        $res_user = $stmt_user->get_result();
        
        if ($row_user = $res_user->fetch_assoc()) {
            if (password_verify($confirm_password, $row_user['password'])) {
                // รหัสผ่านถูกต้อง -> ดำเนินการปิดงาน
                
                // 2.1 บันทึกประวัติการจำหน่ายออก (History Log)
                // ตรวจสอบว่ามีตาราง evacuee_history หรือไม่
                $chk_hist = $conn->query("SHOW TABLES LIKE 'evacuee_history'");
                if ($chk_hist->num_rows > 0) {
                    $hist_sql = "INSERT INTO evacuee_history (evacuee_id, shelter_id, action, details, user_id, action_date)
                                 SELECT e.id, e.shelter_id, 'check_out', 'จบภารกิจ (Auto Checkout)', ?, NOW()
                                 FROM evacuees e
                                 JOIN shelters s ON e.shelter_id = s.id
                                 WHERE s.incident_id = ? AND e.status IN ('active', 'hospitalized')";
                    $hist_stmt = $conn->prepare($hist_sql);
                    $hist_stmt->bind_param("ii", $user_id, $id);
                    $hist_stmt->execute();
                }

                // 2.2 จำหน่ายผู้ประสบภัยทั้งหมด (Update Status -> returned)
                $update_evac = "UPDATE evacuees e 
                                JOIN shelters s ON e.shelter_id = s.id 
                                SET e.status = 'returned', e.check_out_date = NOW() 
                                WHERE s.incident_id = ? AND e.status IN ('active', 'hospitalized')";
                $update_stmt = $conn->prepare($update_evac);
                $update_stmt->bind_param("i", $id);
                $update_stmt->execute();
                $affected_people = $update_stmt->affected_rows;

                // 2.3 ปิดเหตุการณ์ (Update Incident Status -> closed)
                $close_sql = "UPDATE incidents SET status = 'closed', end_date = NOW() WHERE id = ?";
                $close_stmt = $conn->prepare($close_sql);
                $close_stmt->bind_param("i", $id);
                
                if ($close_stmt->execute()) {
                    $_SESSION['swal_success'] = "จบภารกิจเรียบร้อย (จำหน่ายผู้ประสบภัยออก $affected_people คน)";
                } else {
                    $_SESSION['swal_error'] = "เกิดข้อผิดพลาดในการอัปเดตสถานะ";
                }

            } else {
                $_SESSION['swal_error'] = "รหัสผ่านไม่ถูกต้อง ไม่สามารถจบภารกิจได้";
            }
        } else {
            $_SESSION['swal_error'] = "ไม่พบข้อมูลผู้ใช้งาน";
        }
        
        echo "<script>window.location='incident_manager.php';</script>";
        exit();
    }
}

// จัดการลบข้อมูล (GET)
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    
    // ตรวจสอบก่อนลบว่ามีข้อมูลค้างอยู่ไหม (Optional)
    // ... logic check ...

    $stmt = $conn->prepare("DELETE FROM incidents WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'ลบข้อมูลสำเร็จ',
                timer: 1500,
                showConfirmButton: false
            }).then(() => { window.location='incident_manager.php'; });
        </script>";
    }
}

// ดึงข้อมูลเหตุการณ์ทั้งหมด
$incidents_data = [];
$sql = "SELECT * FROM incidents ORDER BY field(status, 'active', 'closed'), created_at DESC"; // Active ขึ้นก่อน
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $incidents_data[] = $row;
    }
}
?>

<div class="container-fluid">
    <!-- Page Header & Tools -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-house-tsunami text-danger me-2"></i>จัดการเหตุการณ์ภัยพิบัติ</h4>
            <p class="text-muted small mb-0">สร้างและแก้ไขสถานการณ์ภัยพิบัติเพื่อแจ้งเตือนประชาชน</p>
        </div>
        
        <div class="d-flex gap-2">
            <!-- View Toggle Buttons -->
            <div class="btn-group shadow-sm" role="group" aria-label="View Mode">
                <button type="button" class="btn btn-outline-secondary active" id="btn-view-card" onclick="switchView('card')" title="มุมมองการ์ด">
                    <i class="fas fa-th-large"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary" id="btn-view-table" onclick="switchView('table')" title="มุมมองตาราง">
                    <i class="fas fa-list"></i>
                </button>
            </div>

            <!-- Add Button (Trigger Modal) -->
            <button type="button" class="btn btn-danger shadow-sm" onclick="openModal()">
                <i class="fas fa-plus-circle me-2"></i>สร้างเหตุการณ์ใหม่
            </button>
        </div>
    </div>

    <!-- 1. CARD VIEW (Default) -->
    <div id="view-card" class="view-container">
        <div class="row g-4">
            <?php if (count($incidents_data) > 0): ?>
                <?php foreach ($incidents_data as $row): ?>
                    <?php 
                        $severity_level = $row['severity_level'] ?? 'medium'; 
                        $severity_color = 'bg-secondary';
                        $severity_text = 'ทั่วไป';
                        
                        if ($severity_level == 'high') { $severity_color = 'bg-danger'; $severity_text = 'รุนแรง-วิกฤต'; }
                        elseif ($severity_level == 'medium') { $severity_color = 'bg-warning text-dark'; $severity_text = 'ปานกลาง-เฝ้าระวัง'; }
                        elseif ($severity_level == 'low') { $severity_color = 'bg-success'; $severity_text = 'เล็กน้อย-ปกติ'; }

                        $is_active = ($row['status'] == 'active');
                        $status_badge = $is_active
                            ? '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>กำลังดำเนินการ</span>' 
                            : '<span class="badge bg-secondary"><i class="fas fa-archive me-1"></i>จบภารกิจแล้ว</span>';
                        
                        $row_json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm border-0 hover-card">
                            <div class="card-header bg-white border-bottom-0 pt-3 d-flex justify-content-between align-items-start">
                                <span class="badge rounded-pill <?php echo $severity_color; ?>"><?php echo $severity_text; ?></span>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-link text-muted" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                        <!-- Edit Button -->
                                        <li><a class="dropdown-item" href="#" onclick='editIncident(<?php echo $row_json; ?>)'><i class="fas fa-edit me-2 text-warning"></i>แก้ไข</a></li>
                                        
                                        <!-- Close Mission Button (Only Active) -->
                                        <?php if ($is_active): ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-success fw-bold" href="#" onclick="promptCloseMission(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['name']); ?>')">
                                                <i class="fas fa-check-double me-2"></i>จบภารกิจ
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                        
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="#" onclick="confirmDelete(<?php echo $row['id']; ?>)"><i class="fas fa-trash-alt me-2"></i>ลบ</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title fw-bold text-dark mb-2"><?php echo htmlspecialchars($row['name']); ?></h5>
                                <p class="card-text text-muted small mb-3 text-truncate-2">
                                    <?php echo htmlspecialchars($row['description']); ?>
                                </p>
                                
                                <div class="d-flex align-items-center text-muted small mb-2">
                                    <i class="fas fa-map-marker-alt me-2 text-danger" style="width: 20px; text-align:center;"></i>
                                    <span><?php echo htmlspecialchars($row['location'] ?? $row['location_scope'] ?? 'ไม่ระบุพิกัด'); ?></span>
                                </div>
                                <div class="d-flex align-items-center text-muted small">
                                    <i class="fas fa-calendar-alt me-2 text-primary" style="width: 20px; text-align:center;"></i>
                                    <span><?php echo date('d/m/Y', strtotime($row['start_date'])); ?></span>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-top-0 pb-3 d-flex justify-content-between align-items-center">
                                <?php echo $status_badge; ?>
                                <!-- View Details Button -->
                                <button class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick='viewIncident(<?php echo $row_json; ?>)'>รายละเอียด</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <div class="text-muted opacity-50 mb-3"><i class="fas fa-folder-open fa-4x"></i></div>
                    <h5>ยังไม่มีข้อมูลเหตุการณ์</h5>
                    <p class="text-muted">คลิกปุ่ม "สร้างเหตุการณ์ใหม่" เพื่อเริ่มรายงานสถานการณ์</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 2. TABLE VIEW -->
    <div id="view-table" class="view-container" style="display: none;">
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-secondary">
                            <tr>
                                <th class="ps-4">ชื่อเหตุการณ์</th>
                                <th>สถานที่</th>
                                <th class="text-center">ความรุนแรง</th>
                                <th class="text-center">สถานะ</th>
                                <th>วันที่แจ้ง</th>
                                <th class="text-end pe-4">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($incidents_data) > 0): ?>
                                <?php foreach ($incidents_data as $row): ?>
                                    <?php 
                                        $severity_level = $row['severity_level'] ?? 'medium'; 
                                        $severity_color = 'bg-secondary'; $severity_text = 'ทั่วไป';
                                        if ($severity_level == 'high') { $severity_color = 'bg-danger'; $severity_text = 'รุนแรง'; }
                                        elseif ($severity_level == 'medium') { $severity_color = 'bg-warning text-dark'; $severity_text = 'ปานกลาง'; }
                                        elseif ($severity_level == 'low') { $severity_color = 'bg-success'; $severity_text = 'เล็กน้อย'; }
                                        
                                        $row_json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                                        $is_active = ($row['status'] == 'active');
                                    ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['name']); ?></div>
                                            <div class="text-muted small text-truncate" style="max-width: 250px;"><?php echo htmlspecialchars($row['description']); ?></div>
                                        </td>
                                        <td><small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($row['location'] ?? $row['location_scope'] ?? '-'); ?></small></td>
                                        <td class="text-center">
                                            <span class="badge rounded-pill <?php echo $severity_color; ?>"><?php echo $severity_text; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($is_active): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">ดำเนินการ</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill">จบภารกิจ</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted small"><?php echo date('d/m/Y', strtotime($row['start_date'])); ?></td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group">
                                                <!-- Close Mission (Table) -->
                                                <?php if ($is_active): ?>
                                                <button class="btn btn-sm btn-outline-success border-0" onclick="promptCloseMission(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['name']); ?>')" title="จบภารกิจ">
                                                    <i class="fas fa-check-double"></i>
                                                </button>
                                                <?php endif; ?>
                                                
                                                <button class="btn btn-sm btn-outline-secondary border-0" onclick='editIncident(<?php echo $row_json; ?>)' title="แก้ไข">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger border-0" onclick="confirmDelete(<?php echo $row['id']; ?>)" title="ลบ">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">ไม่พบข้อมูล</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Modal 1: Add/Edit/View Incident -->
<div class="modal fade" id="incidentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitle"><i class="fas fa-house-tsunami me-2"></i>ข้อมูลเหตุการณ์</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="incidentForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="incId" value="0">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">ชื่อเหตุการณ์ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="incName" required placeholder="เช่น น้ำท่วมใหญ่ปี 67">
                    </div>
                    
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">ประเภทภัย</label>
                            <select class="form-select" name="type" id="incType">
                                <option value="flood">อุทกภัย (น้ำท่วม)</option>
                                <option value="storm">วาตภัย (พายุ)</option>
                                <option value="fire">อัคคีภัย (ไฟไหม้)</option>
                                <option value="landslide">ดินโคลนถล่ม</option>
                                <option value="earthquake">แผ่นดินไหว</option>
                                <option value="epidemic">โรคระบาด</option>
                                <option value="other">อื่นๆ</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">ระดับความรุนแรง</label>
                            <select class="form-select" name="severity_level" id="incSeverity">
                                <option value="low">🟢 เล็กน้อย (Low)</option>
                                <option value="medium" selected>🟡 ปานกลาง (Medium)</option>
                                <option value="high">🔴 รุนแรง (High)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">สถานะ</label>
                        <select class="form-select" name="status" id="incStatus">
                            <option value="active">🔔 กำลังเกิดเหตุ (Active)</option>
                            <option value="closed">🏁 จบภารกิจแล้ว (Closed)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">พื้นที่ได้รับผลกระทบ</label>
                        <input type="text" class="form-control" name="location_scope" id="incLoc" placeholder="เช่น อ.เมือง, ต.แม่สาย">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">วันที่เริ่ม</label>
                            <input type="date" class="form-control" name="start_date" id="incStart" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">วันที่สิ้นสุด (ถ้ามี)</label>
                            <input type="date" class="form-control" name="end_date" id="incEnd">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">รายละเอียดเพิ่มเติม</label>
                        <textarea class="form-control" name="description" id="incDesc" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                    <!-- ปุ่มบันทึก -->
                    <button type="submit" class="btn btn-primary px-4 fw-bold" id="btnSave"><i class="fas fa-save me-1"></i> บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Close Mission Confirmation -->
<div class="modal fade" id="closeMissionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-check-double me-2"></i>ยืนยันการจบภารกิจ</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="close_mission">
                    <input type="hidden" name="id" id="closeId">
                    
                    <div class="text-center mb-4">
                        <div class="display-4 text-success mb-2"><i class="fas fa-clipboard-check"></i></div>
                        <h5 id="closeName" class="fw-bold text-dark"></h5>
                        <p class="text-muted">คุณต้องการจบภารกิจนี้ใช่หรือไม่?</p>
                    </div>

                    <div class="alert alert-warning border-warning">
                        <div class="d-flex">
                            <i class="fas fa-exclamation-triangle text-warning me-3 fa-2x"></i>
                            <div>
                                <strong>คำเตือน:</strong> การจบภารกิจจะเปลี่ยนสถานะเป็น <b>Closed</b> <br>
                                และระบบจะทำการ <b>จำหน่ายผู้ประสบภัย (Check-out)</b> ทุกคนออกจากศูนย์โดยอัตโนมัติ
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">กรุณากรอกรหัสผ่านเพื่อยืนยัน <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="confirm_password" required placeholder="รหัสผ่านของคุณ">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-success fw-bold px-4">ยืนยันจบภารกิจ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Styles -->
<style>
    .text-truncate-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .hover-card { transition: transform 0.2s, box-shadow 0.2s; }
    .hover-card:hover { transform: translateY(-3px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
</style>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    var modalInstance = null;
    var closeMissionModal = null;

    $(document).ready(function() {
        modalInstance = new bootstrap.Modal(document.getElementById('incidentModal'));
        closeMissionModal = new bootstrap.Modal(document.getElementById('closeMissionModal'));

        // View Mode Persistence
        const savedMode = localStorage.getItem('incident_view_mode');
        if (savedMode === 'table') { switchView('table'); } else { switchView('card'); }

        // Session Alerts
        <?php if(isset($_SESSION['swal_success'])): ?>
            Swal.fire({ icon: 'success', title: 'สำเร็จ!', text: '<?php echo $_SESSION['swal_success']; ?>', timer: 2500, showConfirmButton: false });
            <?php unset($_SESSION['swal_success']); ?>
        <?php endif; ?>

        <?php if(isset($_SESSION['swal_error'])): ?>
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด!', text: '<?php echo $_SESSION['swal_error']; ?>', confirmButtonText: 'ตกลง' });
            <?php unset($_SESSION['swal_error']); ?>
        <?php endif; ?>
    });

    // --- Modal Functions ---
    function populateForm(data) {
        $('#incId').val(data.id);
        $('#incName').val(data.name);
        $('#incType').val(data.type);
        $('#incSeverity').val(data.severity_level || 'medium');
        $('#incStatus').val(data.status);
        $('#incLoc').val(data.location_scope || data.location || '');
        $('#incStart').val(data.start_date);
        $('#incEnd').val(data.end_date);
        $('#incDesc').val(data.description);
    }

    function openModal() {
        $('#incId').val('0'); $('#incName').val(''); $('#incType').val('flood');
        $('#incSeverity').val('medium'); $('#incStatus').val('active'); $('#incLoc').val('');
        $('#incStart').val(new Date().toISOString().slice(0, 10)); $('#incEnd').val(''); $('#incDesc').val('');
        
        $('#incidentForm :input').prop('disabled', false);
        $('#btnSave').show();
        $('#modalTitle').html('<i class="fas fa-plus-circle me-2"></i>สร้างเหตุการณ์ใหม่');
        modalInstance.show();
    }

    function editIncident(data) {
        populateForm(data);
        $('#incidentForm :input').prop('disabled', false);
        $('#btnSave').show();
        $('#modalTitle').html('<i class="fas fa-edit me-2"></i>แก้ไขข้อมูลเหตุการณ์');
        modalInstance.show();
    }

    function viewIncident(data) {
        populateForm(data);
        $('#incidentForm :input').prop('disabled', true);
        $('.btn-secondary[data-bs-dismiss="modal"]').prop('disabled', false);
        $('#btnSave').hide();
        $('#modalTitle').html('<i class="fas fa-info-circle me-2"></i>รายละเอียดเหตุการณ์');
        modalInstance.show();
    }

    // --- Close Mission Function ---
    function promptCloseMission(id, name) {
        $('#closeId').val(id);
        $('#closeName').text(name);
        closeMissionModal.show();
    }

    // --- View Switcher ---
    function switchView(mode) {
        document.getElementById('view-card').style.display = 'none';
        document.getElementById('view-table').style.display = 'none';
        
        document.getElementById('btn-view-card').classList.remove('active', 'btn-secondary');
        document.getElementById('btn-view-card').classList.add('btn-outline-secondary');
        
        document.getElementById('btn-view-table').classList.remove('active', 'btn-secondary');
        document.getElementById('btn-view-table').classList.add('btn-outline-secondary');

        if (mode === 'card') {
            document.getElementById('view-card').style.display = 'block';
            document.getElementById('btn-view-card').classList.add('active', 'btn-secondary');
            document.getElementById('btn-view-card').classList.remove('btn-outline-secondary');
            localStorage.setItem('incident_view_mode', 'card');
        } else {
            document.getElementById('view-table').style.display = 'block';
            document.getElementById('btn-view-table').classList.add('active', 'btn-secondary');
            document.getElementById('btn-view-table').classList.remove('btn-outline-secondary');
            localStorage.setItem('incident_view_mode', 'table');
        }
    }

    function confirmDelete(id) {
        Swal.fire({
            title: 'ยืนยันการลบ?',
            text: "ข้อมูลเหตุการณ์นี้จะถูกลบถาวร!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ลบข้อมูล',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `incident_manager.php?delete_id=${id}`;
            }
        })
    }
</script>

<?php require_once 'includes/footer.php'; ?>