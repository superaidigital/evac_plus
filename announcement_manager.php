<?php
/**
 * announcement_manager.php
 * หน้าจัดการข่าวสารและประกาศ
 */
session_start();
require_once 'config/db.php';
include 'includes/header.php';

// ตรวจสอบสิทธิ์ (Admin หรือ Staff)
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

// ดึงข้อมูลประกาศ
$sql = "SELECT a.*, s.name as shelter_name,
        (SELECT COUNT(*) FROM announcement_attachments WHERE announcement_id = a.id) as attach_count
        FROM announcements a 
        LEFT JOIN shelters s ON a.target_shelter_id = s.id 
        ORDER BY a.created_at DESC";
$result = $conn->query($sql);

// ฟังก์ชันแปลงประเภทเป็น Badge
function getTypeBadge($type) {
    switch($type) {
        case 'danger': return '<span class="badge bg-danger"><i class="fas fa-exclamation-circle me-1"></i> วิกฤต/ฉุกเฉิน</span>';
        case 'warning': return '<span class="badge bg-warning text-dark"><i class="fas fa-bullhorn me-1"></i> แจ้งเตือน</span>';
        case 'success': return '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> ประชาสัมพันธ์</span>';
        default: return '<span class="badge bg-info text-dark"><i class="fas fa-info-circle me-1"></i> ทั่วไป</span>';
    }
}
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 15px rgba(0,0,0,0.1);
    }
</style>

<div class="main-content">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h4 class="mb-0 text-primary fw-bold"><i class="fas fa-bullhorn me-2"></i>จัดการข่าวสารและประกาศ</h4>
                <p class="text-muted mb-0 small">แจ้งเตือนสถานการณ์และประชาสัมพันธ์ข้อมูลสู่ประชาชน</p>
            </div>
            <!-- เปลี่ยนปุ่มเป็นลิงก์ไปยังหน้า form -->
            <a href="announcement_form.php" class="btn btn-primary shadow-sm">
                <i class="fas fa-plus-circle me-1"></i> สร้างประกาศใหม่
            </a>
        </div>

        <!-- รายการประกาศ -->
        <div class="row g-4">
            <?php if($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <div class="card h-100">
                            <div class="card-header bg-white border-bottom-0 pt-3 pb-0 d-flex justify-content-between align-items-start">
                                <?php echo getTypeBadge($row['type']); ?>
                                <small class="text-muted" style="font-size: 0.75rem;">
                                    <?php echo date('d/m/y H:i', strtotime($row['created_at'])); ?>
                                </small>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title fw-bold text-dark mb-2 text-truncate" title="<?php echo htmlspecialchars($row['title']); ?>">
                                    <?php echo htmlspecialchars($row['title']); ?>
                                </h5>
                                <p class="card-text text-muted small" style="height: 4.5em; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;">
                                    <?php echo strip_tags($row['content']); ?>
                                </p>
                                
                                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                    <span class="small text-secondary text-truncate" style="max-width: 60%;">
                                        <i class="fas fa-map-marker-alt me-1"></i> 
                                        <?php echo $row['target_shelter_id'] ? $row['shelter_name'] : 'ทุกศูนย์พักพิง'; ?>
                                    </span>
                                    
                                    <?php if($row['attach_count'] > 0): ?>
                                        <span class="badge bg-light text-primary border">
                                            <i class="fas fa-paperclip"></i> <?php echo $row['attach_count']; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-top-0 d-flex justify-content-between pb-3 pt-0">
                                <span class="badge <?php echo $row['status']=='Active'?'bg-success':'bg-secondary'; ?> bg-opacity-10 text-<?php echo $row['status']=='Active'?'success':'secondary'; ?> px-2 py-1 align-self-center">
                                    <?php echo $row['status']; ?>
                                </span>
                                <div class="btn-group">
                                    <a href="announcement_details.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-info" title="ดูรายละเอียด">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <!-- เปลี่ยนปุ่มแก้ไขเป็นลิงก์ -->
                                    <a href="announcement_form.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-warning" title="แก้ไข">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteAnnouncement(<?php echo $row['id']; ?>)" title="ลบ">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <div class="text-muted opacity-50 mb-3"><i class="fas fa-folder-open fa-3x"></i></div>
                    <p class="text-muted">ยังไม่มีประกาศในระบบ</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        // Show alerts from session
        <?php if(isset($_SESSION['swal_icon'])): ?>
            Swal.fire({
                icon: '<?php echo $_SESSION['swal_icon']; ?>',
                title: '<?php echo $_SESSION['swal_title']; ?>',
                text: '<?php echo $_SESSION['swal_text']; ?>',
                timer: 2000, showConfirmButton: false
            });
            <?php unset($_SESSION['swal_icon'], $_SESSION['swal_title'], $_SESSION['swal_text']); ?>
        <?php endif; ?>
    });

    function deleteAnnouncement(id) {
        Swal.fire({
            title: 'ยืนยันการลบ?',
            text: "ประกาศนี้จะถูกลบถาวร",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'ลบ',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'announcement_save.php?action=delete&id=' + id;
            }
        });
    }
</script>
<?php include 'includes/footer.php'; ?>