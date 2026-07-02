<?php
/**
 * shelter_list.php
 * หน้าแสดงรายการศูนย์พักพิงทั้งหมด
 * ปรับปรุง: เพิ่มระบบแบ่งหน้า (Pagination) + Popup Alert + ลบหลายรายการ
 */

require_once 'includes/header.php'; 

// ตรวจสอบสิทธิ์: เฉพาะ Admin หรือ Staff เท่านั้นที่เข้าได้
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    echo "<script>window.location='login.php';</script>";
    exit();
}

// --- การจัดการ Pagination ---
$limit = 10; // จำนวนรายการต่อหน้า
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
if($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// จัดการการค้นหา
$search = $_GET['search'] ?? '';
$where_sql = "";
$params = [];
$types = "";

if ($search) {
    // ใช้ Auto-detect columns ในการค้นหา
    $has_addr = ($conn->query("SHOW COLUMNS FROM shelters LIKE 'address'")->num_rows > 0);
    $has_loc = ($conn->query("SHOW COLUMNS FROM shelters LIKE 'location'")->num_rows > 0);
    
    $addr_col = $has_addr ? 'address' : ($has_loc ? 'location' : 'name'); 
    
    $where_sql = "WHERE name LIKE ? OR $addr_col LIKE ? OR contact_person LIKE ?";
    $search_param = "%{$search}%";
    $params = [$search_param, $search_param, $search_param];
    $types = "sss";
}

// 1. นับจำนวนรายการทั้งหมด (สำหรับ Pagination)
$count_sql = "SELECT COUNT(*) as total FROM shelters $where_sql";
$stmt_count = $conn->prepare($count_sql);
if ($search) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_rows = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// 2. ดึงข้อมูลจริง (พร้อม LIMIT)
$sql = "SELECT * FROM shelters $where_sql ORDER BY id DESC LIMIT ?, ?";
// เพิ่ม Parameter สำหรับ Limit/Offset
$params[] = $offset;
$params[] = $limit;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-campground text-primary me-2"></i>ข้อมูลศูนย์พักพิง</h4>
            <p class="text-muted small mb-0">จัดการรายชื่อและสถานะของศูนย์พักพิงในระบบ</p>
        </div>
        <div>
            <!-- ปุ่มลบหลายรายการ (ซ่อนอยู่ถ้ายังไม่เลือก) -->
            <button id="btn-delete-selected" class="btn btn-danger shadow-sm me-2" style="display:none;" onclick="deleteSelected()">
                <i class="fas fa-trash-alt me-2"></i>ลบที่เลือก (<span id="selected-count">0</span>)
            </button>
            <a href="shelter_form.php" class="btn btn-primary shadow-sm">
                <i class="fas fa-plus me-2"></i>เพิ่มศูนย์พักพิงใหม่
            </a>
        </div>
    </div>

    <!-- Search Box -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3">
            <form action="" method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="ค้นหาชื่อศูนย์, ที่อยู่, ผู้ติดต่อ..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">ค้นหา</button>
                </div>
                <?php if($search): ?>
                <div class="col-md-2">
                    <a href="shelter_list.php" class="btn btn-outline-secondary w-100">ล้างค่า</a>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-0">
            <!-- Form สำหรับส่งค่า ID ที่เลือกไปลบ -->
            <form id="form-delete-multiple" action="shelter_delete_multiple.php" method="POST">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-secondary">
                            <tr>
                                <th class="ps-4" width="5%">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="checkAll">
                                    </div>
                                </th>
                                <th width="25%">ชื่อศูนย์พักพิง</th>
                                <th width="25%">ที่ตั้ง</th>
                                <th width="15%" class="text-center">ความจุ</th>
                                <th width="10%" class="text-center">สถานะ</th>
                                <th width="15%">ผู้ติดต่อ</th>
                                <th width="5%" class="text-end pe-4">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                    <?php 
                                        // คำนวณเปอร์เซ็นต์
                                        $current = $row['current_population'] ?? 0;
                                        $capacity = $row['capacity'] ?? 1;
                                        $percent = ($capacity > 0) ? ($current / $capacity) * 100 : 0;
                                        
                                        // Status Badge
                                        $status_badge = 'bg-secondary';
                                        $status = strtolower($row['status'] ?? '');
                                        if ($status == 'open') $status_badge = 'bg-success';
                                        elseif ($status == 'full') $status_badge = 'bg-danger';
                                        elseif ($status == 'closed') $status_badge = 'bg-dark';
                                        elseif ($status == 'standby') $status_badge = 'bg-warning text-dark';
                                        
                                        // Data Handling
                                        $address = $row['address'] ?? $row['location'] ?? '-';
                                        if (!empty($row['district'])) $address .= " อ.".$row['district'];
                                        if (!empty($row['province'])) $address .= " จ.".$row['province'];
                                        
                                        $phone = $row['phone'] ?? $row['contact_phone'] ?? '-';
                                    ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="form-check">
                                                <input class="form-check-input check-item" type="checkbox" name="ids[]" value="<?php echo $row['id']; ?>">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-primary"><?php echo htmlspecialchars($row['name']); ?></div>
                                            <div class="small text-muted" style="font-size: 0.75rem;">
                                                <i class="fas fa-map-pin me-1"></i>
                                                <?php echo ($row['latitude'] && $row['longitude']) ? "{$row['latitude']}, {$row['longitude']}" : "ไม่ระบุพิกัด"; ?>
                                            </div>
                                        </td>
                                        <td><small><?php echo htmlspecialchars($address); ?></small></td>
                                        <td class="text-center">
                                            <div class="d-flex flex-column align-items-center">
                                                <span class="fw-bold small"><?php echo number_format($current); ?> / <?php echo number_format($capacity); ?></span>
                                                <div class="progress w-75 mt-1" style="height: 4px;">
                                                    <div class="progress-bar <?php echo ($percent > 90) ? 'bg-danger' : 'bg-info'; ?>" role="progressbar" style="width: <?php echo $percent; ?>%"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge rounded-pill <?php echo $status_badge; ?>" style="font-weight: 500;">
                                                <?php echo ucfirst($status); ?>
                                            </span>
                                        </td>
                                        <td class="small">
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['contact_person'] ?? '-'); ?></div>
                                            <div class="text-muted"><i class="fas fa-phone-alt me-1"></i> <?php echo htmlspecialchars($phone); ?></div>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group">
                                                <a href="shelter_form.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-warning border-0" title="แก้ไข">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="confirmDelete(<?php echo $row['id']; ?>)" title="ลบ">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted bg-light">
                                        <i class="fas fa-folder-open fa-3x mb-3 text-secondary opacity-25"></i>
                                        <p>ไม่พบข้อมูลศูนย์พักพิง</p>
                                        <a href="shelter_form.php" class="btn btn-sm btn-primary px-3">เพิ่มข้อมูลใหม่</a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
        
        <!-- Pagination Section -->
        <?php if ($total_pages > 1): ?>
        <div class="card-footer bg-white border-0 py-3">
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center justify-content-md-end mb-0">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link border-0" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <?php 
                    $start_loop = $page - 2;
                    $end_loop = $page + 2;
                    if($start_loop <= 0) { $start_loop = 1; $end_loop = 5; }
                    if($end_loop > $total_pages) { $end_loop = $total_pages; }
                    
                    for($i = $start_loop; $i <= $end_loop; $i++): 
                    ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link border-0 rounded-circle mx-1" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link border-0" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="text-center text-muted small mt-2">
                แสดงหน้า <?php echo $page; ?> จาก <?php echo $total_pages; ?> (ทั้งหมด <?php echo number_format($total_rows); ?> รายการ)
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- SweetAlert2 Scripts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// จัดการ Checkbox ทั้งหมด
document.getElementById('checkAll').addEventListener('change', function() {
    var checkboxes = document.querySelectorAll('.check-item');
    for(var checkbox of checkboxes) {
        checkbox.checked = this.checked;
    }
    updateDeleteButton();
});

// อัปเดตปุ่มลบเมื่อมีการเลือก
var checkboxes = document.querySelectorAll('.check-item');
checkboxes.forEach(function(checkbox) {
    checkbox.addEventListener('change', function() {
        updateDeleteButton();
        // ถ้าเอาออกอันนึง checkAll ต้องออกด้วย
        if(!this.checked) document.getElementById('checkAll').checked = false;
    });
});

function updateDeleteButton() {
    var count = document.querySelectorAll('.check-item:checked').length;
    var btn = document.getElementById('btn-delete-selected');
    var badge = document.getElementById('selected-count');
    
    if(count > 0) {
        btn.style.display = 'inline-block';
        badge.textContent = count;
    } else {
        btn.style.display = 'none';
    }
}

// ฟังก์ชันลบหลายรายการ
function deleteSelected() {
    Swal.fire({
        title: 'ยืนยันการลบที่เลือก?',
        text: "ข้อมูลศูนย์พักพิงที่เลือกทั้งหมดจะถูกลบ!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ลบทั้งหมด',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('form-delete-multiple').submit();
        }
    })
}

// ฟังก์ชันลบรายการเดียว
function confirmDelete(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "หากลบแล้ว ข้อมูลผู้อพยพในศูนย์นี้อาจได้รับผลกระทบ!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ลบข้อมูล',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `shelter_delete.php?id=${id}`;
        }
    })
}

// *** ส่วนแสดง Popup แจ้งเตือนเมื่อบันทึกเสร็จ ***
document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($_SESSION['success'])): ?>
        Swal.fire({
            icon: 'success',
            title: 'สำเร็จ!',
            text: '<?php echo $_SESSION['success']; ?>',
            timer: 2000,
            showConfirmButton: false,
            position: 'center'
        });
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด!',
            text: '<?php echo $_SESSION['error']; ?>',
            confirmButtonText: 'ตกลง'
        });
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
});
</script>

<?php require_once 'includes/footer.php'; ?>