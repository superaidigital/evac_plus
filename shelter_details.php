<?php
session_start();
require_once 'config/db.php';
include 'includes/header.php';

// รับค่า ID ศูนย์พักพิง
$shelter_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($shelter_id == 0) {
    header("Location: index.php");
    exit();
}

// 1. ดึงข้อมูลศูนย์พักพิง
$sql_shelter = "SELECT * FROM shelters WHERE id = ?";
$stmt = $conn->prepare($sql_shelter);
$stmt->bind_param("i", $shelter_id);
$stmt->execute();
$shelter = $stmt->get_result()->fetch_assoc();

if (!$shelter) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>ไม่พบข้อมูลศูนย์พักพิง</div></div>";
    exit();
}

// 2. คำนวณสถิติ (เฉพาะคนที่ยังอยู่ Active + Hospitalized)
$stats = [
    'total' => 0,
    'male' => 0,
    'female' => 0,
    'elderly' => 0,
    'child' => 0,
    'patient' => 0 // ป่วย/ติดเตียง/พิการ
];

// ดึงรายชื่อคนที่ยังไม่กลับบ้าน (status != returned)
$sql_people = "SELECT * FROM evacuees WHERE shelter_id = ? AND status != 'returned' ORDER BY registered_at DESC";
$stmt_p = $conn->prepare($sql_people);
$stmt_p->bind_param("i", $shelter_id);
$stmt_p->execute();
$result_people = $stmt_p->get_result();

while ($row = $result_people->fetch_assoc()) {
    $stats['total']++;
    
    // เพศ
    if ($row['gender'] == 'male') $stats['male']++;
    else $stats['female']++;

    // กลุ่มเปราะบาง
    if (!empty($row['vulnerable_group'])) {
        $groups = explode(',', $row['vulnerable_group']);
        if (in_array('elderly', $groups)) $stats['elderly']++;
        if (in_array('child', $groups)) $stats['child']++;
        if (in_array('bedridden', $groups) || in_array('disabled', $groups)) $stats['patient']++;
    }
}

// คำนวณความจุ
$capacity_percent = 0;
if ($shelter['capacity'] > 0) {
    $capacity_percent = ($stats['total'] / $shelter['capacity']) * 100;
}

// Helper Function แสดง Badge
function getStatusBadge($s) {
    if ($s == 'active') return '<span class="badge bg-success">พักอาศัยอยู่</span>';
    if ($s == 'hospitalized') return '<span class="badge bg-danger">ส่งโรงพยาบาล</span>';
    return '<span class="badge bg-secondary">กลับบ้านแล้ว</span>';
}
?>

<!-- CSS แก้หน้าจอไม่สมส่วน -->
<style>
    .main-content { margin-left: 0 !important; padding: 20px !important; width: 100% !important; }
    .footer { left: 0 !important; width: 100% !important; }
    #vertical-menu-btn { display: none !important; }
    
    .stat-card { border-left: 4px solid; transition: transform 0.2s; }
    .stat-card:hover { transform: translateY(-5px); }
    .progress-bar-striped { animation: progress-bar-stripes 1s linear infinite; }
</style>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-0 text-primary fw-bold"><i class="fas fa-campground me-2"></i><?php echo htmlspecialchars($shelter['name']); ?></h4>
                    <span class="text-muted"><i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($shelter['location'] ?? 'ไม่ระบุที่ตั้ง'); ?></span>
                </div>
                <div>
                    <a href="evacuee_form.php?shelter_id=<?php echo $shelter_id; ?>" class="btn btn-primary shadow-sm"><i class="fas fa-user-plus me-1"></i> รับผู้อพยพเพิ่ม</a>
                    <a href="index.php" class="btn btn-outline-secondary ms-2">กลับหน้าหลัก</a>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card stat-card border-primary shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="text-muted text-uppercase mb-2">ผู้อพยพปัจจุบัน</h6>
                            <div class="d-flex align-items-center">
                                <h2 class="mb-0 text-primary fw-bold"><?php echo $stats['total']; ?></h2>
                                <span class="ms-2 text-muted">/ <?php echo $shelter['capacity']; ?> คน</span>
                            </div>
                            <!-- Progress Bar ความจุ -->
                            <div class="progress mt-2" style="height: 6px;">
                                <div class="progress-bar <?php echo $capacity_percent > 90 ? 'bg-danger' : 'bg-primary'; ?>" 
                                     role="progressbar" style="width: <?php echo $capacity_percent; ?>%"></div>
                            </div>
                            <small class="text-muted mt-1 d-block"><?php echo number_format($capacity_percent, 1); ?>% ของความจุ</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card border-warning shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-muted text-uppercase mb-2">ผู้สูงอายุ</h6>
                                    <h2 class="mb-0 text-warning fw-bold"><?php echo $stats['elderly']; ?></h2>
                                </div>
                                <div class="text-end">
                                    <h6 class="text-muted text-uppercase mb-2">เด็กเล็ก</h6>
                                    <h2 class="mb-0 text-info fw-bold"><?php echo $stats['child']; ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card border-danger shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="text-muted text-uppercase mb-2">ผู้ป่วย/ติดเตียง/พิการ</h6>
                            <h2 class="mb-0 text-danger fw-bold"><?php echo $stats['patient']; ?></h2>
                            <small class="text-danger"><i class="fas fa-procedures me-1"></i> ต้องการดูแลพิเศษ</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card border-success shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="text-muted text-uppercase mb-2">เจ้าหน้าที่ดูแล</h6>
                            <h2 class="mb-0 text-success fw-bold"><?php echo htmlspecialchars($shelter['contact_person'] ?? '-'); ?></h2>
                            <small class="text-muted"><i class="fas fa-phone me-1"></i> <?php echo htmlspecialchars($shelter['phone'] ?? '-'); ?></small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Evacuee List Table -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-dark font-weight-bold">รายชื่อผู้เข้าพัก (ปัจจุบัน)</h5>
                    <a href="evacuee_list.php?shelter_id=<?php echo $shelter_id; ?>" class="btn btn-sm btn-outline-primary">ดูประวัติทั้งหมด</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">ชื่อ-นามสกุล</th>
                                    <th>อายุ</th>
                                    <th>กลุ่มเปราะบาง</th>
                                    <th>เวลาเข้าพัก</th>
                                    <th>สถานะ</th>
                                    <th class="text-end pe-4">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // รีเซ็ต pointer ของ result set เพื่อวนลูปแสดงผล
                                $result_people->data_seek(0);
                                if ($result_people->num_rows > 0): 
                                    while($row = $result_people->fetch_assoc()): 
                                ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark"><?php echo $row['prefix'].$row['first_name'].' '.$row['last_name']; ?></div>
                                            <small class="text-muted">ID: <?php echo $row['id_card']; ?></small>
                                        </td>
                                        <td>
                                            <?php echo $row['age']; ?> ปี 
                                            <i class="fas <?php echo $row['gender']=='male'?'fa-male text-primary':'fa-female text-danger'; ?>"></i>
                                        </td>
                                        <td>
                                            <?php 
                                            if(!empty($row['vulnerable_group'])) {
                                                $vg = explode(',', $row['vulnerable_group']);
                                                foreach($vg as $v) {
                                                    if($v=='elderly') echo '<span class="badge bg-warning text-dark me-1" title="ผู้สูงอายุ"><i class="fas fa-user-clock"></i></span>';
                                                    if($v=='child') echo '<span class="badge bg-info text-dark me-1" title="เด็กเล็ก"><i class="fas fa-baby"></i></span>';
                                                    if($v=='bedridden') echo '<span class="badge bg-danger me-1" title="ติดเตียง"><i class="fas fa-procedures"></i></span>';
                                                    if($v=='disabled') echo '<span class="badge bg-primary me-1" title="พิการ"><i class="fas fa-wheelchair"></i></span>';
                                                    if($v=='pregnant') echo '<span class="badge bg-pink text-dark me-1" style="background-color:pink" title="ตั้งครรภ์"><i class="fas fa-female"></i></span>';
                                                }
                                            } else {
                                                echo '<span class="text-muted small">-</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $dt = strtotime($row['registered_at']);
                                            echo date('d/m/', $dt) . (date('Y', $dt)+543) . '<br><small class="text-muted">'.date('H:i น.', $dt).'</small>';
                                            ?>
                                        </td>
                                        <td><?php echo getStatusBadge($row['status']); ?></td>
                                        <td class="text-end pe-4">
                                            <a href="evacuee_form.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-secondary" title="แก้ไข"><i class="fas fa-edit"></i></a>
                                            <a href="evacuee_delete.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('ยืนยันการลบ?');" title="ลบ"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endwhile; else: ?>
                                    <tr><td colspan="6" class="text-center py-5 text-muted">ยังไม่มีผู้เข้าพักในขณะนี้</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>