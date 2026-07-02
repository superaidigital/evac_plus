<?php
/**
 * request_admin.php
 * หน้าจัดการคำร้องขอทรัพยากรจากศูนย์พักพิง
 * ปรับปรุง: แก้ไข Error Undefined array key และ TypeError
 */
session_start();
require_once 'config/db.php';
include 'includes/header.php';

// ตรวจสอบสิทธิ์ (ถ้ามีระบบ Login)
if (!isset($_SESSION['user_id'])) {
    // header("Location: login.php"); // ปิดไว้ก่อนเพื่อให้ทดสอบได้
}

// กรองสถานะ
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending';
$where_sql = "WHERE 1=1";
if ($status_filter != 'all') {
    $where_sql .= " AND r.status = '" . $conn->real_escape_string($status_filter) . "'";
}

// ดึงข้อมูลคำร้องขอ + ชื่อศูนย์พักพิง + ชื่อผู้ขอ
// ใช้ Try-Catch เพื่อดักจับกรณีฐานข้อมูลไม่สมบูรณ์
try {
    $sql = "SELECT r.*, s.name as shelter_name, u.first_name, u.last_name 
            FROM shelter_requests r 
            LEFT JOIN shelters s ON r.shelter_id = s.id 
            LEFT JOIN users u ON r.user_id = u.id 
            $where_sql 
            ORDER BY r.created_at DESC";
    $result = $conn->query($sql);
} catch (Exception $e) {
    $error_msg = $e->getMessage();
    echo "<div class='container mt-4'><div class='alert alert-danger'>
            <strong>เกิดข้อผิดพลาดในการดึงข้อมูล:</strong> $error_msg <br>
            <a href='fix_request_db.php' class='btn btn-warning btn-sm mt-2'>คลิกเพื่อซ่อมแซมฐานข้อมูล</a>
          </div></div>";
    include 'includes/footer.php';
    exit();
}

// Helper Functions
function getRequestStatusBadge($s) {
    if ($s == 'pending') return '<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i> รออนุมัติ</span>';
    if ($s == 'approved') return '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> อนุมัติแล้ว</span>';
    if ($s == 'rejected') return '<span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i> ปฏิเสธ</span>';
    if ($s == 'completed') return '<span class="badge bg-info"><i class="fas fa-flag-checkered me-1"></i> เสร็จสิ้น</span>';
    return '<span class="badge bg-secondary">' . ($s ?? 'ไม่ระบุ') . '</span>';
}

function getUrgencyBadge($u) {
    if ($u == 'high' || $u == 'critical') return '<span class="text-danger fw-bold"><i class="fas fa-exclamation-triangle"></i> เร่งด่วน</span>';
    if ($u == 'medium') return '<span class="text-warning fw-bold"><i class="fas fa-exclamation-circle"></i> ปานกลาง</span>';
    return '<span class="text-success"><i class="fas fa-info-circle"></i> ปกติ</span>';
}
?>

<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
    .main-content { margin-left: 0 !important; padding: 20px !important; width: 100% !important; }
    .footer { left: 0 !important; width: 100% !important; }
    #vertical-menu-btn { display: none !important; }
    .nav-tabs .nav-link.active { font-weight: bold; color: #0d6efd; border-bottom: 3px solid #0d6efd; }
</style>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-0 text-primary fw-bold">ศูนย์ควบคุมคำร้องขอ</h4>
                    <span class="text-muted">จัดการคำขอเสบียงและทรัพยากร</span>
                </div>
            </div>

            <!-- Tabs -->
            <ul class="nav nav-tabs mb-3">
                <li class="nav-item"><a class="nav-link <?php echo $status_filter=='pending'?'active':''; ?>" href="?status=pending">⏳ รออนุมัติ</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $status_filter=='approved'?'active':''; ?>" href="?status=approved">✅ ดำเนินการ</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $status_filter=='completed'?'active':''; ?>" href="?status=completed">🏁 เสร็จสิ้น</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $status_filter=='rejected'?'active':''; ?>" href="?status=rejected">❌ ปฏิเสธ</a></li>
                <li class="nav-item"><a class="nav-link <?php echo $status_filter=='all'?'active':''; ?>" href="?status=all">ทั้งหมด</a></li>
            </ul>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">วันที่</th>
                                    <th>ศูนย์พักพิง</th>
                                    <th>รายการ</th>
                                    <th>จำนวน</th>
                                    <th>ความเร่งด่วน</th>
                                    <th>ผู้แจ้ง</th>
                                    <th>สถานะ</th>
                                    <th class="text-end pe-4">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <?php 
                                                // ตรวจสอบ created_at
                                                $dateVal = $row['created_at'] ?? null;
                                                echo $dateVal ? date('d/m/y H:i', strtotime($dateVal)) : '-'; 
                                                ?>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-primary"><?php echo htmlspecialchars($row['shelter_name'] ?? 'ไม่ระบุศูนย์'); ?></div>
                                            </td>
                                            <td>
                                                <!-- แก้ไข Undefined array key "item_name" โดยใช้ ?? -->
                                                <span class="fw-bold text-dark"><?php echo htmlspecialchars($row['item_name'] ?? 'ไม่ระบุรายการ'); ?></span>
                                                <?php if(!empty($row['description'])): ?>
                                                    <div class="small text-muted text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($row['description']); ?>">
                                                        <?php echo htmlspecialchars($row['description']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <!-- แก้ไข TypeError: number_format -->
                                                <span class="badge bg-light text-dark border">
                                                    <?php 
                                                    // แปลงเป็น float ก่อนเข้า number_format เพื่อป้องกัน Error หากค่าเป็น NULL หรือ String
                                                    echo number_format((float)($row['quantity'] ?? 0)); 
                                                    ?> 
                                                    <?php echo htmlspecialchars($row['unit'] ?? 'หน่วย'); ?>
                                                </span>
                                            </td>
                                            <td><?php echo getUrgencyBadge($row['urgency'] ?? 'medium'); ?></td>
                                            <td>
                                                <div class="small">
                                                    <i class="fas fa-user-circle me-1"></i> 
                                                    <?php echo htmlspecialchars(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')); ?>
                                                </div>
                                            </td>
                                            <td><?php echo getRequestStatusBadge($row['status'] ?? 'pending'); ?></td>
                                            <td class="text-end pe-4">
                                                <?php 
                                                $status = $row['status'] ?? 'pending';
                                                if ($status == 'pending'): 
                                                ?>
                                                    <button class="btn btn-sm btn-success" onclick="processRequest(<?php echo $row['id']; ?>, 'approved')"><i class="fas fa-check"></i> อนุมัติ</button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="processRequest(<?php echo $row['id']; ?>, 'rejected')"><i class="fas fa-times"></i> ปฏิเสธ</button>
                                                <?php elseif ($status == 'approved'): ?>
                                                    <button class="btn btn-sm btn-info text-white" onclick="processRequest(<?php echo $row['id']; ?>, 'completed')"><i class="fas fa-flag-checkered"></i> จบงาน</button>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="text-center py-5 text-muted">ไม่พบข้อมูลคำร้องขอในสถานะนี้</td></tr>
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
    // แสดง Popup ผลลัพธ์ (จาก Session)
    document.addEventListener('DOMContentLoaded', function() {
        <?php if(isset($_SESSION['swal_icon'])): ?>
            Swal.fire({
                icon: <?php echo json_encode($_SESSION['swal_icon']); ?>,
                title: <?php echo json_encode($_SESSION['swal_title']); ?>,
                text: <?php echo json_encode($_SESSION['swal_text']); ?>,
                timer: 4000,
                showConfirmButton: true
            });
            <?php 
            unset($_SESSION['swal_icon']);
            unset($_SESSION['swal_title']);
            unset($_SESSION['swal_text']);
            ?>
        <?php endif; ?>
    });

    // ฟังก์ชันกดปุ่ม Action
    function processRequest(id, action) {
        let title = 'ยืนยันการทำรายการ?';
        let btnColor = '#3085d6';

        if(action === 'approved') { title = 'ยืนยันการอนุมัติ?'; btnColor = '#198754'; }
        else if (action === 'rejected') { title = 'ยืนยันการปฏิเสธ?'; btnColor = '#dc3545'; }
        else if (action === 'completed') { title = 'ยืนยันจบงาน?'; btnColor = '#0dcaf0'; }

        Swal.fire({
            title: title,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: btnColor,
            confirmButtonText: 'ยืนยัน',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'request_process.php?id=' + id + '&action=' + action;
            }
        });
    }
</script>

<?php include 'includes/footer.php'; ?>