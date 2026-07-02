<?php
session_start();
require_once 'config/db.php';
include 'includes/header.php';

// รับค่าวันที่กรอง (Default: วันนี้)
$filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$shelter_id = isset($_GET['shelter_id']) ? intval($_GET['shelter_id']) : 0;

$where = "WHERE DATE(e.registered_at) = '$filter_date'";
if ($shelter_id > 0) $where .= " AND e.shelter_id = $shelter_id";

$sql = "SELECT e.*, s.name as shelter_name 
        FROM evacuees e 
        LEFT JOIN shelters s ON e.shelter_id = s.id 
        $where ORDER BY e.registered_at DESC";
$result = $conn->query($sql);

$shelters = $conn->query("SELECT id, name FROM shelters");
?>

<div class="container-fluid py-4" style="margin-left:0; width:100%;">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-list-alt me-2"></i>รายงานรายชื่อผู้เข้าพักประจำวัน</h5>
        </div>
        <div class="card-body">
            
            <form method="GET" class="row g-3 mb-4 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-bold">เลือกวันที่</label>
                    <input type="date" class="form-control" name="date" value="<?php echo $filter_date; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">ศูนย์พักพิง</label>
                    <select class="form-select" name="shelter_id">
                        <option value="0">-- ดูทั้งหมด --</option>
                        <?php while($s = $shelters->fetch_assoc()): ?>
                            <option value="<?php echo $s['id']; ?>" <?php echo $shelter_id==$s['id']?'selected':''; ?>><?php echo $s['name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter"></i> แสดงผล</button>
                </div>
                <div class="col-md-3 text-end">
                    <button type="button" class="btn btn-success" onclick="window.print()"><i class="fas fa-print"></i> พิมพ์รายงาน</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>เวลา</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th>เลขบัตร</th>
                            <th>ศูนย์พักพิง</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('H:i น.', strtotime($row['registered_at'])); ?></td>
                                    <td><?php echo $row['prefix'].$row['first_name'].' '.$row['last_name']; ?></td>
                                    <td><?php echo $row['id_card']; ?></td>
                                    <td><?php echo $row['shelter_name']; ?></td>
                                    <td>
                                        <?php if($row['status']=='active') echo '<span class="badge bg-success">เข้าพัก</span>'; 
                                              else echo '<span class="badge bg-secondary">อื่นๆ</span>'; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">ไม่มีข้อมูลในวันนี้</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3 text-muted small">
                ข้อมูล ณ วันที่: <?php echo date('d/m/Y', strtotime($filter_date)); ?> | ออกรายงานโดยระบบ Smart Disaster
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>