<?php
session_start();
require_once 'config/db.php';
include 'includes/header.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id == 0) { echo "ไม่พบข้อมูล"; exit; }

// ดึงข้อมูลส่วนตัว
$evacuee = $conn->query("SELECT * FROM evacuees WHERE id = $id")->fetch_assoc();

// ดึงประวัติ
$sql = "SELECT h.*, s.name as shelter_name, u.username 
        FROM evacuee_history h 
        LEFT JOIN shelters s ON h.shelter_id = s.id 
        LEFT JOIN users u ON h.user_id = u.id 
        WHERE h.evacuee_id = $id 
        ORDER BY h.action_date DESC";
$history = $conn->query($sql);

function getActionBadge($a) {
    if($a=='check_in') return '<span class="badge bg-success"><i class="fas fa-sign-in-alt"></i> เข้าพัก</span>';
    if($a=='check_out') return '<span class="badge bg-secondary"><i class="fas fa-home"></i> กลับบ้าน</span>';
    if($a=='transfer') return '<span class="badge bg-warning text-dark"><i class="fas fa-exchange-alt"></i> ย้ายศูนย์</span>';
    if($a=='refer') return '<span class="badge bg-danger"><i class="fas fa-ambulance"></i> ส่ง รพ.</span>';
    if($a=='undo_checkout') return '<span class="badge bg-info"><i class="fas fa-undo"></i> ยกเลิกจำหน่าย</span>';
    return '<span class="badge bg-light text-dark">'.$a.'</span>';
}

function thaiDate($datetime) {
    if(!$datetime) return '-';
    $ts = strtotime($datetime);
    $year = date('Y', $ts) + 543;
    return date('d/m/', $ts) . $year . date(' H:i น.', $ts);
}
?>
<style>
    .main-content { margin-left: 0 !important; width: 100% !important; padding: 20px; background-color: #f8f9fa; }
    .timeline { border-left: 3px solid #dee2e6; margin-left: 15px; padding-left: 30px; position: relative; }
    .timeline-item { position: relative; margin-bottom: 30px; background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    .timeline-item::before { 
        content: ''; position: absolute; left: -39px; top: 15px; width: 20px; height: 20px; 
        border-radius: 50%; background: #fff; border: 4px solid #0d6efd; z-index: 1;
    }
</style>

<div class="main-content">
    <div class="container">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0 fw-bold text-primary"><i class="fas fa-history me-2"></i>ประวัติการเข้าพัก</h4>
            </div>
            <a href="evacuee_list.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> ย้อนกลับ</a>
        </div>

        <div class="card mb-4 border-0 shadow-sm border-start border-5 border-primary">
            <div class="card-body">
                <h5 class="fw-bold mb-1"><?php echo $evacuee['prefix'].$evacuee['first_name'].' '.$evacuee['last_name']; ?></h5>
                <span class="badge bg-light text-dark border">เลขบัตร: <?php echo $evacuee['id_card']; ?></span>
            </div>
        </div>

        <?php if($history && $history->num_rows > 0): ?>
            <div class="timeline">
                <?php while($row = $history->fetch_assoc()): ?>
                    <div class="timeline-item">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <?php echo getActionBadge($row['action']); ?> 
                                <span class="fw-bold ms-2 text-dark">ณ <?php echo htmlspecialchars($row['shelter_name']); ?></span>
                            </div>
                            <small class="text-muted"><i class="far fa-clock me-1"></i> <?php echo thaiDate($row['action_date']); ?></small>
                        </div>
                        
                        <div class="text-muted small">
                            <?php 
                            // แสดงรายละเอียด
                            $details = htmlspecialchars($row['details'] ?? '');
                            if (strpos($details, 'ย้ายจาก') !== false) {
                                // ไฮไลท์ข้อความการย้าย
                                echo '<span class="text-warning fw-bold"><i class="fas fa-suitcase"></i> ' . $details . '</span>';
                            } else {
                                echo $details;
                            }

                            if ($row['action'] == 'check_out') {
                                if (empty($row['details']) || strpos($row['details'], 'บ้านเลขที่') === false) {
                                    $addr_show = $evacuee['current_address'] ? $evacuee['current_address'] : $evacuee['address'];
                                    if($addr_show) {
                                        echo '<div class="mt-2 p-2 bg-light rounded border text-primary"><i class="fas fa-map-marker-alt me-1"></i> <strong>ที่อยู่ปลายทาง:</strong> ' . htmlspecialchars($addr_show) . '</div>';
                                    }
                                }
                            }
                            ?>
                        </div>

                        <?php if(isset($row['username']) && $row['username']): ?>
                            <div class="mt-2 pt-2 border-top text-end">
                                <small class="text-muted" style="font-size: 11px;">
                                    <i class="fas fa-user-edit me-1"></i> โดย: <?php echo $row['username']; ?>
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-light text-center py-5 shadow-sm">
                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                <p class="mb-0 text-muted">ยังไม่มีประวัติการบันทึกในระบบ</p>
            </div>
        <?php endif; ?>

    </div>
</div>
<?php include 'includes/footer.php'; ?>