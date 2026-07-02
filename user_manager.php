<?php
/**
 * user_manager.php
 * ระบบจัดการผู้ใช้งานแบบ All-in-One
 * Update: เพิ่มตัวกรองค้นหา + ล็อคการลบหากมีประวัติกิจกรรม
 */
session_start();
require_once 'config/db.php';

// ---------------------------------------------------------------------------
// 1. ส่วนจัดการข้อมูล (Backend Logic)
// ---------------------------------------------------------------------------

// 1.1 ลบข้อมูล
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // 1. ป้องกันการลบ Super Admin
    if ($id == 1) {
        $_SESSION['swal_icon'] = 'error';
        $_SESSION['swal_title'] = 'ผิดพลาด';
        $_SESSION['swal_text'] = 'ไม่สามารถลบ Super Admin ได้';
    } 
    else {
        // 2. ตรวจสอบว่ามีประวัติการทำงานหรือไม่ (Data Integrity Check)
        $has_history = false;
        
        // เช็คในประวัติผู้อพยพ
        $chk_hist = $conn->query("SELECT id FROM evacuee_history WHERE user_id = $id LIMIT 1");
        if ($chk_hist && $chk_hist->num_rows > 0) $has_history = true;

        // เช็คในคำร้องขอ (ถ้ามีตาราง)
        if (!$has_history) {
            $chk_req = $conn->query("SHOW TABLES LIKE 'shelter_requests'");
            if ($chk_req && $chk_req->num_rows > 0) {
                // ต้องเช็คก่อนว่ามีคอลัมน์ user_id ไหม (ป้องกัน error)
                $chk_col = $conn->query("SHOW COLUMNS FROM shelter_requests LIKE 'user_id'");
                if ($chk_col && $chk_col->num_rows > 0) {
                    $chk_req_data = $conn->query("SELECT id FROM shelter_requests WHERE user_id = $id LIMIT 1");
                    if ($chk_req_data && $chk_req_data->num_rows > 0) $has_history = true;
                }
            }
        }

        if ($has_history) {
            $_SESSION['swal_icon'] = 'warning';
            $_SESSION['swal_title'] = 'ไม่สามารถลบได้';
            $_SESSION['swal_text'] = 'ผู้ใช้งานนี้มีประวัติการทำรายการในระบบ (เช่น รับลงทะเบียน, แจ้งคำขอ) กรุณาใช้วิธี "ระงับการใช้งาน" แทนการลบ เพื่อรักษาหลักฐานข้อมูล';
        } else {
            // ไม่มีประวัติ -> ลบได้
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['swal_icon'] = 'success';
                $_SESSION['swal_title'] = 'ลบสำเร็จ';
                $_SESSION['swal_text'] = 'ลบผู้ใช้งานเรียบร้อยแล้ว';
            } else {
                $_SESSION['swal_icon'] = 'error';
                $_SESSION['swal_title'] = 'เกิดข้อผิดพลาด';
                $_SESSION['swal_text'] = $stmt->error;
            }
        }
    }
    header("Location: user_manager.php");
    exit();
}

// 1.2 บันทึกข้อมูล (เพิ่ม/แก้ไข)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_user'])) {
    $id = intval($_POST['id']);
    $username = trim($_POST['username']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $role = $_POST['role'];
    $shelter_id = intval($_POST['shelter_id']);
    $status = isset($_POST['status']) ? 'active' : 'inactive'; 
    $password = $_POST['password'];

    // Validation
    if (empty($username) || empty($first_name)) {
        $_SESSION['swal_icon'] = 'warning';
        $_SESSION['swal_title'] = 'ข้อมูลไม่ครบ';
        $_SESSION['swal_text'] = 'กรุณากรอก Username และชื่อให้ครบถ้วน';
        header("Location: user_manager.php");
        exit();
    }

    try {
        if ($id == 0) {
            // --- INSERT ---
            $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $check->bind_param("s", $username);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                throw new Exception("ชื่อผู้ใช้ '$username' มีในระบบแล้ว");
            }

            $pwd_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // เช็คว่ามีคอลัมน์ status/shelter_id หรือไม่ (เพื่อความปลอดภัย)
            $sql = "INSERT INTO users (username, password, first_name, last_name, role, shelter_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                 // Fallback query
                 $sql = "INSERT INTO users (username, password, first_name, last_name, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
                 $stmt = $conn->prepare($sql);
                 $stmt->bind_param("sssss", $username, $pwd_hash, $first_name, $last_name, $role);
            } else {
                 $stmt->bind_param("sssssis", $username, $pwd_hash, $first_name, $last_name, $role, $shelter_id, $status);
            }
            
            if ($stmt->execute()) {
                $_SESSION['swal_icon'] = 'success';
                $_SESSION['swal_title'] = 'เพิ่มสำเร็จ';
                $_SESSION['swal_text'] = "ผู้ใช้งาน $username ถูกเพิ่มเรียบร้อยแล้ว";
            } else {
                throw new Exception($stmt->error);
            }

        } else {
            // --- UPDATE ---
            if (!empty($password)) {
                $pwd_hash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET first_name=?, last_name=?, role=?, shelter_id=?, status=?, password=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                if(!$stmt) {
                    $sql = "UPDATE users SET first_name=?, last_name=?, role=?, password=? WHERE id=?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssssi", $first_name, $last_name, $role, $pwd_hash, $id);
                } else {
                    $stmt->bind_param("sssisss", $first_name, $last_name, $role, $shelter_id, $status, $pwd_hash, $id);
                }
            } else {
                $sql = "UPDATE users SET first_name=?, last_name=?, role=?, shelter_id=?, status=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                if(!$stmt) {
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
    }

    header("Location: user_manager.php");
    exit();
}

// ---------------------------------------------------------------------------
// 2. ส่วนแสดงผล (Frontend)
// ---------------------------------------------------------------------------
include 'includes/header.php';

// ดึงข้อมูลศูนย์พักพิง (สำหรับ Dropdown)
$shelters = $conn->query("SELECT id, name FROM shelters ORDER BY name ASC");
$shelter_options = [];
while($s = $shelters->fetch_assoc()) { $shelter_options[] = $s; }

// --- รับค่าตัวกรอง ---
$filter_role = isset($_GET['role']) ? $_GET['role'] : '';
$filter_shelter = isset($_GET['shelter_id']) ? $_GET['shelter_id'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// สร้าง Query พร้อมตัวกรอง
$sql = "SELECT u.*, s.name as shelter_name FROM users u LEFT JOIN shelters s ON u.shelter_id = s.id WHERE 1=1";

if (!empty($filter_role)) {
    $sql .= " AND u.role = '" . $conn->real_escape_string($filter_role) . "'";
}
if (!empty($filter_shelter)) {
    $sql .= " AND u.shelter_id = " . intval($filter_shelter);
}
if (!empty($search)) {
    $sql .= " AND (u.username LIKE '%$search%' OR u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%')";
}

$sql .= " ORDER BY u.created_at DESC";
$result = $conn->query($sql);

function getRoleBadge($role) {
    switch ($role) {
        case 'admin': return '<span class="badge bg-danger">ผู้ดูแลระบบ</span>';
        case 'staff': return '<span class="badge bg-primary">เจ้าหน้าที่</span>';
        case 'volunteer': return '<span class="badge bg-info">อาสาสมัคร</span>';
        default: return '<span class="badge bg-secondary">'.$role.'</span>';
    }
}
?>

<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
    .main-content { margin-left: 0 !important; padding: 20px !important; width: 100% !important; }
    .footer { left: 0 !important; width: 100% !important; }
    #vertical-menu-btn { display: none !important; }
    
    .avatar-circle {
        width: 40px; height: 40px; background-color: #0d6efd; color: white;
        border-radius: 50%; display: flex; align-items: center; justify-content: center;
        font-weight: bold; font-size: 18px; text-transform: uppercase;
    }
    .modal-header { background: #0d6efd; color: white; }
    .btn-close-white { filter: invert(1) grayscale(100%) brightness(200%); }
</style>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- Title & Add Button -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-0 text-primary fw-bold"><i class="fas fa-users-cog me-2"></i>จัดการผู้ใช้งานระบบ</h4>
                    <span class="text-muted">เพิ่ม ลบ แก้ไข ผู้ใช้งานในหน้าเดียว</span>
                </div>
                <button type="button" class="btn btn-primary shadow-sm" onclick="openModal()">
                    <i class="fas fa-plus-circle me-1"></i> เพิ่มผู้ใช้งานใหม่
                </button>
            </div>

            <!-- Filter & Search Box -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small text-muted">ตำแหน่ง</label>
                            <select name="role" class="form-select" onchange="this.form.submit()">
                                <option value="">-- ทั้งหมด --</option>
                                <option value="admin" <?php echo $filter_role=='admin'?'selected':''; ?>>Admin</option>
                                <option value="staff" <?php echo $filter_role=='staff'?'selected':''; ?>>Staff</option>
                                <option value="volunteer" <?php echo $filter_role=='volunteer'?'selected':''; ?>>Volunteer</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted">ศูนย์พักพิง</label>
                            <select name="shelter_id" class="form-select" onchange="this.form.submit()">
                                <option value="">-- ทั้งหมด --</option>
                                <?php foreach ($shelter_options as $s): ?>
                                    <option value="<?php echo $s['id']; ?>" <?php echo $filter_shelter==$s['id']?'selected':''; ?>>
                                        <?php echo $s['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted">ค้นหา</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" class="form-control border-start-0" name="search" placeholder="ชื่อ, Username..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-dark w-100"><i class="fas fa-search"></i></button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">ผู้ใช้งาน</th>
                                    <th>สิทธิ์ (Role)</th>
                                    <th>สังกัดศูนย์</th>
                                    <th>สถานะ</th>
                                    <th>วันที่สร้าง</th>
                                    <th class="text-end pe-4">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-circle me-3">
                                                        <?php echo substr($row['username'], 0, 1); ?>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0 fw-bold text-dark">
                                                            <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                                                        </h6>
                                                        <small class="text-muted">@<?php echo htmlspecialchars($row['username']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo getRoleBadge($row['role']); ?></td>
                                            <td>
                                                <?php if (!empty($row['shelter_name'])): ?>
                                                    <span class="text-primary"><i class="fas fa-campground me-1"></i> <?php echo $row['shelter_name']; ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">- ไม่สังกัด -</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $st = $row['status'] ?? 'active';
                                                if ($st == 'active'): 
                                                ?>
                                                    <span class="badge bg-soft-success text-success"><i class="fas fa-check-circle me-1"></i> ใช้งาน</span>
                                                <?php else: ?>
                                                    <span class="badge bg-soft-secondary text-secondary"><i class="fas fa-ban me-1"></i> ระงับ</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><small class="text-muted"><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></small></td>
                                            <td class="text-end pe-4">
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-outline-warning" 
                                                            onclick='editUser(<?php echo json_encode($row); ?>)' title="แก้ไข">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    
                                                    <?php if ($row['username'] !== 'admin'): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                onclick="confirmDelete(<?php echo $row['id']; ?>)" title="ลบ">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center py-5 text-muted">ไม่พบข้อมูลผู้ใช้งาน</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modal Form -->
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalTitle">เพิ่มผู้ใช้งาน</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="user_manager.php">
                <div class="modal-body">
                    <input type="hidden" name="save_user" value="1">
                    <input type="hidden" name="id" id="userId" value="0">

                    <div class="mb-3">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="username" id="username" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">ชื่อจริง <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="first_name" id="firstName" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="last_name" id="lastName" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">รหัสผ่าน</label>
                        <input type="password" class="form-control" name="password" id="password" placeholder="กำหนดรหัสผ่าน">
                        <small class="text-muted d-none" id="passwordHint">* เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่านเดิม</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">สิทธิ์การใช้งาน (Role)</label>
                        <select class="form-select" name="role" id="role">
                            <option value="staff">Staff (เจ้าหน้าที่ทั่วไป)</option>
                            <option value="admin">Admin (ผู้ดูแลระบบ)</option>
                            <option value="volunteer">Volunteer (อาสาสมัคร)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">สังกัดศูนย์พักพิง</label>
                        <select class="form-select" name="shelter_id" id="shelterId">
                            <option value="0">-- ไม่สังกัด / ดูทั้งหมด --</option>
                            <?php foreach ($shelter_options as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo $s['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-check form-switch p-3 bg-light rounded">
                        <input class="form-check-input ms-0 me-2" type="checkbox" name="status" value="active" id="statusSwitch" checked>
                        <label class="form-check-label fw-bold" for="statusSwitch">เปิดใช้งานบัญชี (Active)</label>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    var userModal;
    document.addEventListener('DOMContentLoaded', function() {
        userModal = new bootstrap.Modal(document.getElementById('userModal'));

        // แสดง Popup จาก Session
        <?php if(isset($_SESSION['swal_icon'])): ?>
            Swal.fire({
                icon: <?php echo json_encode($_SESSION['swal_icon']); ?>,
                title: <?php echo json_encode($_SESSION['swal_title']); ?>,
                text: <?php echo json_encode($_SESSION['swal_text']); ?>,
                timer: 3000,
                showConfirmButton: true
            });
            <?php unset($_SESSION['swal_icon'], $_SESSION['swal_title'], $_SESSION['swal_text']); ?>
        <?php endif; ?>
    });

    // เปิด Modal เพิ่มใหม่
    function openModal() {
        document.getElementById('userId').value = '0';
        document.getElementById('username').value = '';
        document.getElementById('username').readOnly = false;
        document.getElementById('firstName').value = '';
        document.getElementById('lastName').value = '';
        document.getElementById('password').value = '';
        document.getElementById('password').placeholder = 'กำหนดรหัสผ่าน';
        document.getElementById('password').required = true;
        document.getElementById('passwordHint').classList.add('d-none');
        document.getElementById('role').value = 'staff';
        document.getElementById('shelterId').value = '0';
        document.getElementById('statusSwitch').checked = true;
        
        document.getElementById('modalTitle').innerText = 'เพิ่มผู้ใช้งานใหม่';
        userModal.show();
    }

    // เปิด Modal แก้ไข
    function editUser(user) {
        document.getElementById('userId').value = user.id;
        document.getElementById('username').value = user.username;
        document.getElementById('username').readOnly = true; // ห้ามแก้ Username
        document.getElementById('firstName').value = user.first_name;
        document.getElementById('lastName').value = user.last_name;
        
        document.getElementById('password').value = '';
        document.getElementById('password').placeholder = 'เว้นว่างไว้หากไม่เปลี่ยน';
        document.getElementById('password').required = false;
        document.getElementById('passwordHint').classList.remove('d-none');
        
        document.getElementById('role').value = user.role;
        document.getElementById('shelterId').value = user.shelter_id;
        
        // เช็ค status
        let st = user.status || 'active';
        document.getElementById('statusSwitch').checked = (st === 'active');

        document.getElementById('modalTitle').innerText = 'แก้ไขข้อมูลผู้ใช้';
        userModal.show();
    }

    function confirmDelete(id) {
        Swal.fire({
            title: 'ยืนยันการลบ?',
            text: "บัญชีนี้จะถูกลบถาวร ไม่สามารถกู้คืนได้",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'ลบข้อมูล',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'user_manager.php?action=delete&id=' + id;
            }
        });
    }
</script>

<?php include 'includes/footer.php'; ?>