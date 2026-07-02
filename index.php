<?php
/**
 * index.php
 * หน้า Dashboard หลัก
 * Features: Role-based Filtering (Admin/Staff)
 */
session_start();
require_once 'config/db.php';

// 1. ตรวจสอบ Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/header.php';

// --- 2. เตรียมตัวแปรตามสิทธิ์ ---
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'guest';
$my_shelter_id = $_SESSION['shelter_id'] ?? 0;

// รับค่ากรอง (Search/Filter)
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_incident = isset($_GET['incident_id']) ? intval($_GET['incident_id']) : 0;

// --- Logic การดึงข้อมูล ---
$where_clauses = ["1=1"];

if ($role !== 'admin') {
    // [Staff] บังคับดูเฉพาะศูนย์ตัวเอง
    $where_clauses[] = "s.id = $my_shelter_id";
} else {
    // [Admin] กรองตามที่เลือก
    if ($filter_incident > 0) {
        $where_clauses[] = "s.incident_id = $filter_incident";
    }
}

if (!empty($search)) {
    $search_safe = $conn->real_escape_string($search);
    $where_clauses[] = "(s.name LIKE '%$search_safe%' OR s.location LIKE '%$search_safe%')";
}

$where_sql = implode(' AND ', $where_clauses);

// Main Query (Shelters + Stats)
$sql_main = "SELECT s.*, i.name as incident_name,
             (SELECT COUNT(*) FROM evacuees WHERE shelter_id = s.id AND status IN ('active','hospitalized')) as current_people
             FROM shelters s
             LEFT JOIN incidents i ON s.incident_id = i.id
             WHERE $where_sql
             ORDER BY s.status DESC, current_people DESC";
$result_main = $conn->query($sql_main);

// Calculate Stats for Top Cards (Dynamic based on permission)
$stats = [ 'total_people' => 0, 'vulnerable' => 0, 'shelters' => 0, 'requests' => 0 ];

// Helper Query for Stats
$stats_where = ($role !== 'admin') ? "shelter_id = $my_shelter_id" : "1=1";
$stats['total_people'] = $conn->query("SELECT COUNT(*) FROM evacuees WHERE status IN ('active','hospitalized') AND $stats_where")->fetch_row()[0];
$stats['vulnerable'] = $conn->query("SELECT COUNT(*) FROM evacuees WHERE status IN ('active','hospitalized') AND vulnerable_group != '' AND $stats_where")->fetch_row()[0];
$stats['shelters'] = ($role !== 'admin') ? 1 : $conn->query("SELECT COUNT(*) FROM shelters WHERE status = 'open'")->fetch_row()[0];
$stats['requests'] = $conn->query("SELECT COUNT(*) FROM shelter_requests WHERE status = 'pending' AND $stats_where")->fetch_row()[0];

// Dropdown Incident (Only for Admin)
$incidents_opt = $conn->query("SELECT id, name FROM incidents ORDER BY created_at DESC");
?>

<style>
    .main-content { margin-left: 0 !important; width: 100% !important; padding: 25px; background-color: #f4f7f6; }
    .footer { left: 0 !important; width: 100% !important; }
    #vertical-menu-btn { display: none !important; }
    .stat-card { border: none; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); transition: transform 0.3s; height: 100%; }
    .stat-card:hover { transform: translateY(-5px); }
</style>

<div class="main-content">
    <div class="container-fluid">
        
        <!-- Welcome -->
        <div class="row mb-4 align-items-center">
            <div class="col-md-8">
                <h3 class="fw-bold text-dark mb-1">สวัสดี, <?php echo htmlspecialchars($_SESSION['username']); ?> 👋</h3>
                <p class="text-muted mb-0">
                    <span class="badge <?php echo $role=='admin'?'bg-danger':'bg-info'; ?>"><?php echo ucfirst($role); ?></span>
                    <?php if($role !== 'admin'): ?> ประจำศูนย์: <strong><?php echo $user_shelter_name; ?></strong> <?php endif; ?>
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="qr_scanner.php" class="btn btn-primary rounded-pill px-4 shadow-sm"><i class="fas fa-qrcode me-2"></i> สแกน QR Code</a>
            </div>
        </div>

        <!-- 1. Stats Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="card stat-card bg-primary text-white p-4">
                    <h6 class="text-uppercase opacity-75 mb-2">ผู้อพยพ (Active)</h6>
                    <h2 class="fw-bold mb-0"><?php echo number_format($stats['total_people']); ?> <span class="fs-6 fw-normal">คน</span></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-warning text-dark p-4">
                    <h6 class="text-uppercase opacity-75 mb-2">กลุ่มเปราะบาง</h6>
                    <h2 class="fw-bold mb-0"><?php echo number_format($stats['vulnerable']); ?> <span class="fs-6 fw-normal">คน</span></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-info text-white p-4">
                    <h6 class="text-uppercase opacity-75 mb-2"><?php echo $role=='admin'?'ศูนย์ที่เปิด':'ความจุศูนย์'; ?></h6>
                    <?php if($role=='admin'): ?>
                        <h2 class="fw-bold mb-0"><?php echo number_format($stats['shelters']); ?> <span class="fs-6 fw-normal">แห่ง</span></h2>
                    <?php else: 
                        // คำนวณความจุเฉพาะศูนย์ตัวเอง
                        $cap = $conn->query("SELECT capacity FROM shelters WHERE id = $my_shelter_id")->fetch_row()[0] ?? 1;
                        $pct = ($stats['total_people'] / $cap) * 100;
                    ?>
                        <h2 class="fw-bold mb-0"><?php echo number_format($pct, 1); ?>%</h2>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-danger text-white p-4">
                    <h6 class="text-uppercase opacity-75 mb-2">คำร้องขอ (Pending)</h6>
                    <h2 class="fw-bold mb-0"><?php echo number_format($stats['requests']); ?> <span class="fs-6 fw-normal">รายการ</span></h2>
                </div>
            </div>
        </div>

        <!-- 2. Search & Filter (แสดงเฉพาะ Admin หรือช่องค้นหาทั่วไป) -->
        <div class="card shadow-sm border-0 mb-4 rounded-4">
            <div class="card-body p-3">
                <form method="GET" class="row g-2 align-items-center">
                    <div class="col-md-<?php echo $role=='admin'?'5':'9'; ?>">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="ค้นหาชื่อศูนย์พักพิง..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    
                    <?php if ($role === 'admin'): ?>
                    <div class="col-md-4">
                        <select name="incident_id" class="form-select">
                            <option value="0">-- ทุกเหตุการณ์ภัยพิบัติ --</option>
                            <?php while($inc = $incidents_opt->fetch_assoc()): ?>
                                <option value="<?php echo $inc['id']; ?>" <?php echo $filter_incident == $inc['id'] ? 'selected' : ''; ?>>
                                    <?php echo $inc['name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="col-md-3">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-dark w-100 fw-bold"><i class="fas fa-filter me-1"></i> กรองข้อมูล</button>
                            <?php if(!empty($search) || $filter_incident > 0): ?>
                                <a href="index.php" class="btn btn-outline-secondary w-50"><i class="fas fa-undo"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- 3. Shelter List -->
        <h5 class="fw-bold text-secondary mb-3"><i class="fas fa-map-marked-alt me-2"></i>สถานะศูนย์พักพิง</h5>
        <div class="row g-4">
            <?php if ($result_main->num_rows > 0): ?>
                <?php while($row = $result_main->fetch_assoc()): 
                    $capacity = $row['capacity'] > 0 ? $row['capacity'] : 1;
                    $percent = ($row['current_people'] / $capacity) * 100;
                    $color = ($percent >= 100) ? 'danger' : (($percent >= 80) ? 'warning' : 'success');
                ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100 shadow-sm border-<?php echo $color; ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <h5 class="card-title fw-bold text-dark mb-0 text-truncate" style="max-width: 70%;">
                                    <a href="shelter_details.php?id=<?php echo $row['id']; ?>" class="text-decoration-none text-dark stretched-link">
                                        <?php echo htmlspecialchars($row['name']); ?>
                                    </a>
                                </h5>
                                <span class="badge bg-<?php echo $color; ?>-subtle text-<?php echo $color; ?> rounded-pill px-2">
                                    <?php echo number_format($percent, 1); ?>%
                                </span>
                            </div>
                            <p class="text-muted small mb-2"><i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($row['location']); ?></p>
                            
                            <div class="d-flex justify-content-between align-items-end mb-1">
                                <small class="text-muted">ความจุ</small>
                                <small class="fw-bold text-<?php echo $color; ?>"><?php echo number_format($row['current_people']); ?> / <?php echo number_format($row['capacity']); ?></small>
                            </div>
                            <div class="progress progress-thin" style="height: 6px;">
                                <div class="progress-bar bg-<?php echo $color; ?>" role="progressbar" style="width: <?php echo $percent; ?>%"></div>
                            </div>
                            <?php if($role === 'admin' && $row['incident_name']): ?>
                                <div class="mt-2 text-end">
                                    <span class="badge bg-light text-secondary border"><?php echo $row['incident_name']; ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <div class="text-muted opacity-50 mb-3"><i class="fas fa-campground fa-3x"></i></div>
                    <h6 class="text-muted">ไม่พบข้อมูลศูนย์พักพิง</h6>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>