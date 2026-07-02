<?php
session_start();
require_once 'config/db.php';
include 'includes/header.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$mode = $id > 0 ? 'edit' : 'add';

// ค่าเริ่มต้น (กำหนด status เป็น active ไว้ก่อน)
$user = [
    'username' => '', 'first_name' => '', 'last_name' => '', 'email' => '',
    'role' => 'staff', 'shelter_id' => 0, 'status' => 'active'
];

if ($mode == 'edit') {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $user = $row;
    }
}

// ดึงรายชื่อศูนย์พักพิง
$shelters = $conn->query("SELECT id, name FROM shelters ORDER BY name ASC");
?>

<style>
    .main-content { margin-left: 0 !important; padding: 20px !important; width: 100% !important; }
    .footer { left: 0 !important; width: 100% !important; }
    #vertical-menu-btn { display: none !important; }
</style>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="mb-0 text-primary fw-bold"><?php echo $mode == 'edit' ? 'แก้ไขข้อมูลผู้ใช้' : 'เพิ่มผู้ใช้ใหม่'; ?></h4>
                        <a href="user_manager.php" class="btn btn-light border">ย้อนกลับ</a>
                    </div>

                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4">
                            <form action="user_save.php" method="POST" autocomplete="off">
                                <input type="hidden" name="id" value="<?php echo $id; ?>">
                                <input type="hidden" name="action" value="save">

                                <div class="mb-3">
                                    <label class="form-label">Username (ชื่อผู้ใช้) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required <?php echo $mode=='edit'?'readonly':''; ?>>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">ชื่อจริง</label>
                                        <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">นามสกุล</label>
                                        <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Password (รหัสผ่าน)</label>
                                    <input type="password" class="form-control" name="password" placeholder="<?php echo $mode=='edit' ? 'เว้นว่างไว้หากไม่ต้องการเปลี่ยน' : 'กำหนดรหัสผ่าน'; ?>" <?php echo $mode=='add'?'required':''; ?>>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Role (สิทธิ์การใช้งาน)</label>
                                    <select class="form-select" name="role">
                                        <option value="staff" <?php echo $user['role']=='staff'?'selected':''; ?>>Staff (เจ้าหน้าที่ทั่วไป)</option>
                                        <option value="admin" <?php echo $user['role']=='admin'?'selected':''; ?>>Admin (ผู้ดูแลระบบ)</option>
                                        <option value="volunteer" <?php echo $user['role']=='volunteer'?'selected':''; ?>>Volunteer (อาสาสมัคร)</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">สังกัดศูนย์พักพิง</label>
                                    <select class="form-select" name="shelter_id">
                                        <option value="0">-- ไม่สังกัด / ดูแลทั้งหมด --</option>
                                        <?php while($s = $shelters->fetch_assoc()): ?>
                                            <option value="<?php echo $s['id']; ?>" <?php echo $user['shelter_id']==$s['id']?'selected':''; ?>>
                                                <?php echo $s['name']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <div class="form-text text-muted">Admin สามารถเลือก "ไม่สังกัด" เพื่อดูข้อมูลทั้งหมด</div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">สถานะบัญชี</label>
                                    <div class="form-check form-switch">
                                        <!-- แก้ไขจุดที่เกิด Warning: ใช้ ?? เพื่อตรวจสอบค่าก่อน -->
                                        <input class="form-check-input" type="checkbox" name="status" value="active" id="statusSwitch" 
                                            <?php echo ($user['status'] ?? 'active') == 'active' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="statusSwitch">เปิดใช้งาน (Active)</label>
                                    </div>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary py-2 fw-bold">
                                        <i class="fas fa-save me-2"></i> บันทึกข้อมูล
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>