<?php
session_start();
require_once 'config/db.php';
include 'includes/header.php';

$shelter_id = isset($_GET['shelter_id']) ? intval($_GET['shelter_id']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// สร้าง Query (ใช้ COALESCE ป้องกันค่า NULL)
$sql = "SELECT e.*, 
        s.name as shelter_name,
        COALESCE(DATEDIFF(NOW(), e.registered_at), 0) as days_stay,
        (SELECT COUNT(*) FROM evacuee_history h WHERE h.evacuee_id = e.id AND h.action = 'check_in') as stay_count
        FROM evacuees e 
        LEFT JOIN shelters s ON e.shelter_id = s.id 
        WHERE 1=1";

if ($shelter_id > 0) {
    $sql .= " AND e.shelter_id = $shelter_id";
}
if (!empty($search)) {
    $sql .= " AND (e.first_name LIKE '%$search%' OR e.last_name LIKE '%$search%' OR e.id_card LIKE '%$search%')";
}

$sql .= " ORDER BY e.registered_at DESC";
$result = $conn->query($sql);

function getStatusBadge($s) {
    if ($s == 'active') return '<span class="badge bg-success">พักอาศัยอยู่</span>';
    if ($s == 'hospitalized') return '<span class="badge bg-danger">ส่ง รพ.</span>';
    if ($s == 'returned') return '<span class="badge bg-secondary">กลับบ้านแล้ว</span>';
    return '<span class="badge bg-light text-dark">ไม่ระบุ</span>';
}
?>

<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
    /* บังคับหน้าจอเต็มความกว้าง */
    .main-content { margin-left: 0 !important; padding: 20px !important; width: 100% !important; }
    .footer { left: 0 !important; width: 100% !important; }
    #vertical-menu-btn { display: none !important; }
    
    .table td { vertical-align: middle; }
    .btn-action-group .btn { margin-right: 3px; }
</style>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0 text-primary fw-bold"><i class="fas fa-users me-2"></i>รายชื่อผู้ประสบภัย</h4>
                <a href="evacuee_form.php?shelter_id=<?php echo $shelter_id; ?>" class="btn btn-primary shadow-sm">
                    <i class="fas fa-user-plus me-2"></i> ลงทะเบียนใหม่
                </a>
            </div>

            <!-- Search Bar -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-2">
                        <input type="hidden" name="shelter_id" value="<?php echo $shelter_id; ?>">
                        <div class="col-md-10">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" class="form-control border-start-0" name="search" placeholder="ค้นหาชื่อ, นามสกุล หรือเลขบัตรประชาชน..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-dark w-100" type="submit">ค้นหา</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">ชื่อ-นามสกุล</th>
                                    <th>ข้อมูลการพัก</th>
                                    <th>กลุ่มเปราะบาง</th>
                                    <th>ที่อยู่</th>
                                    <th>สถานะ</th>
                                    <th class="text-end pe-4" style="min-width: 180px;">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold text-dark">
                                                    <?php echo htmlspecialchars($row['prefix'] . $row['first_name'] . ' ' . $row['last_name']); ?>
                                                </div>
                                                <small class="text-muted"><i class="fas fa-id-card me-1"></i> <?php echo htmlspecialchars($row['id_card']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info text-white">
                                                    <?php echo max(1, intval($row['stay_count'])); ?> ครั้ง
                                                </span>
                                                <div class="small text-muted mt-1">
                                                    <i class="far fa-clock me-1"></i> <?php echo max(0, intval($row['days_stay'])); ?> วัน
                                                </div>
                                            </td>
                                            <td>
                                                <?php 
                                                if (!empty($row['vulnerable_group'])) {
                                                    $groups = explode(',', $row['vulnerable_group']);
                                                    foreach ($groups as $g) {
                                                        if ($g == 'elderly') echo '<span class="badge rounded-pill bg-warning text-dark me-1" title="ผู้สูงอายุ"><i class="fas fa-user-clock"></i></span>';
                                                        if ($g == 'child') echo '<span class="badge rounded-pill bg-info text-dark me-1" title="เด็กเล็ก"><i class="fas fa-baby"></i></span>';
                                                        if ($g == 'disabled') echo '<span class="badge rounded-pill bg-primary me-1" title="ผู้พิการ"><i class="fas fa-wheelchair"></i></span>';
                                                        if ($g == 'bedridden') echo '<span class="badge rounded-pill bg-danger me-1" title="ติดเตียง"><i class="fas fa-procedures"></i></span>';
                                                        if ($g == 'pregnant') echo '<span class="badge rounded-pill bg-pink text-dark me-1" style="background-color:pink" title="ตั้งครรภ์"><i class="fas fa-female"></i></span>';
                                                    }
                                                } else {
                                                    echo '<span class="text-muted small">-</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <div style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($row['address'] ?? ''); ?>">
                                                    <small><?php echo htmlspecialchars($row['address'] ?? '-'); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <?php echo getStatusBadge($row['status']); ?>
                                            </td>
                                            <td class="text-end pe-4">
                                                <div class="btn-action-group">
                                                    <!-- ปุ่มดูประวัติ (แสดงเสมอ) -->
                                                    <a href="evacuee_history.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-info" title="ดูประวัติ">
                                                        <i class="fas fa-history"></i>
                                                    </a>

                                                    <!-- ปุ่มแก้ไข (แสดงเสมอ) -->
                                                    <a href="evacuee_form.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary" title="แก้ไข">
                                                        <i class="fas fa-edit"></i>
                                                    </a>

                                                    <!-- ปุ่มจัดการสถานะ (แสดงตามเงื่อนไข) -->
                                                    <?php if ($row['status'] == 'active' || $row['status'] == 'hospitalized'): ?>
                                                        <a href="evacuee_action.php?action=checkout&id=<?php echo $row['id']; ?>&shelter_id=<?php echo $shelter_id; ?>" 
                                                           class="btn btn-sm btn-outline-warning" 
                                                           onclick="return confirm('ยืนยันจำหน่ายกลับบ้าน?')" title="จำหน่ายกลับบ้าน">
                                                            <i class="fas fa-home"></i>
                                                        </a>
                                                    <?php elseif ($row['status'] == 'returned'): ?>
                                                        <a href="evacuee_action.php?action=undo&id=<?php echo $row['id']; ?>&shelter_id=<?php echo $shelter_id; ?>" 
                                                           class="btn btn-sm btn-outline-secondary" 
                                                           onclick="return confirm('ดึงรายชื่อกลับมา Active?')" title="ยกเลิกการจำหน่าย">
                                                            <i class="fas fa-undo"></i>
                                                        </a>
                                                    <?php endif; ?>

                                                    <!-- ปุ่มลบ (แสดงเสมอ) -->
                                                    <a href="evacuee_delete.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" 
                                                       onclick="return confirm('ยืนยันลบข้อมูลถาวร?')" title="ลบ">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="fas fa-user-slash fa-3x mb-3"></i><br>
                                            ไม่พบข้อมูลผู้ประสบภัยในระบบ
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // แสดง Popup แจ้งเตือนถ้ามี Session
        <?php if (isset($_SESSION['swal_icon'])): ?>
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
</script>

<?php include 'includes/footer.php'; ?>