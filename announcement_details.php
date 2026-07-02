<?php
/**
 * announcement_details.php
 * หน้าแสดงรายละเอียดประกาศแบบเต็มจอ (Full Page View)
 */
session_start();
require_once 'config/db.php';
include 'includes/header.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// ดึงข้อมูลประกาศ
$sql = "SELECT a.*, s.name as shelter_name 
        FROM announcements a 
        LEFT JOIN shelters s ON a.target_shelter_id = s.id 
        WHERE a.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$news = $stmt->get_result()->fetch_assoc();

if (!$news) {
    echo "<div class='container mt-5 text-center'><h3>ไม่พบข้อมูลประกาศ</h3><a href='announcement_manager.php' class='btn btn-secondary'>กลับ</a></div>";
    include 'includes/footer.php';
    exit;
}

// ดึงไฟล์แนบ
$att_sql = "SELECT * FROM announcement_attachments WHERE announcement_id = ? ORDER BY id ASC";
$att_stmt = $conn->prepare($att_sql);
$att_stmt->bind_param("i", $id);
$att_stmt->execute();
$attachments = $att_stmt->get_result();

// ฟังก์ชัน Badge
function getTypeBadgeLg($type) {
    switch($type) {
        case 'danger': return '<span class="badge bg-danger fs-6 px-3 py-2"><i class="fas fa-exclamation-circle me-1"></i> วิกฤต/ฉุกเฉิน</span>';
        case 'warning': return '<span class="badge bg-warning text-dark fs-6 px-3 py-2"><i class="fas fa-bullhorn me-1"></i> แจ้งเตือน</span>';
        case 'success': return '<span class="badge bg-success fs-6 px-3 py-2"><i class="fas fa-check-circle me-1"></i> ประชาสัมพันธ์</span>';
        default: return '<span class="badge bg-info text-dark fs-6 px-3 py-2"><i class="fas fa-info-circle me-1"></i> ทั่วไป</span>';
    }
}
?>

<style>
    .main-content { margin-left: 0 !important; width: 100% !important; padding: 30px; background-color: #f4f6f9; }
    .footer { left: 0 !important; width: 100% !important; }
    #vertical-menu-btn { display: none !important; }
    
    .news-content { font-size: 1.1rem; line-height: 1.8; color: #333; white-space: pre-wrap; }
    .img-gallery img { max-height: 400px; object-fit: contain; background: #000; cursor: pointer; transition: transform 0.2s; }
    .img-gallery img:hover { transform: scale(1.02); }
</style>

<div class="main-content">
    <div class="container">
        
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <a href="announcement_manager.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> ย้อนกลับ</a>
            <button onclick="window.print()" class="btn btn-outline-primary"><i class="fas fa-print me-1"></i> พิมพ์</button>
        </div>

        <div class="card shadow-lg border-0 overflow-hidden">
            <div class="card-body p-5">
                
                <!-- Header -->
                <div class="mb-4 pb-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <?php echo getTypeBadgeLg($news['type']); ?>
                            <span class="ms-2 text-muted"><i class="far fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($news['created_at'])); ?></span>
                        </div>
                        <span class="badge bg-light text-dark border">
                            <i class="fas fa-map-marker-alt text-danger me-1"></i> 
                            <?php echo $news['target_shelter_id'] ? $news['shelter_name'] : 'ประกาศทั่วไป (ทุกศูนย์)'; ?>
                        </span>
                    </div>
                    <h2 class="fw-bold text-dark mt-3"><?php echo htmlspecialchars($news['title']); ?></h2>
                </div>

                <!-- Content -->
                <div class="news-content mb-5">
                    <?php echo htmlspecialchars($news['content']); ?>
                </div>

                <!-- Attachments Display -->
                <?php if($attachments->num_rows > 0): ?>
                    <div class="bg-light p-4 rounded-3">
                        <h5 class="fw-bold mb-3 text-secondary"><i class="fas fa-paperclip me-2"></i> เอกสารแนบและรูปภาพ</h5>
                        
                        <div class="row g-3">
                            <?php while($file = $attachments->fetch_assoc()): ?>
                                
                                <!-- กรณีเป็นรูปภาพ -->
                                <?php if($file['file_type'] == 'image'): ?>
                                    <div class="col-md-4 col-lg-3">
                                        <div class="card h-100 border shadow-sm img-gallery">
                                            <a href="uploads/announcements/<?php echo $file['file_path']; ?>" target="_blank">
                                                <img src="uploads/announcements/<?php echo $file['file_path']; ?>" class="card-img-top" alt="<?php echo $file['file_name']; ?>">
                                            </a>
                                        </div>
                                    </div>
                                
                                <!-- กรณีเป็นลิ้งค์ -->
                                <?php elseif($file['file_type'] == 'link'): ?>
                                    <div class="col-12">
                                        <a href="<?php echo $file['file_path']; ?>" target="_blank" class="btn btn-outline-primary w-100 text-start">
                                            <i class="fas fa-link me-2"></i> <?php echo htmlspecialchars($file['file_name']); ?> 
                                            <small class="text-muted ms-2">(<?php echo $file['file_path']; ?>)</small>
                                        </a>
                                    </div>

                                <!-- กรณีเป็นไฟล์เอกสาร (PDF, etc) -->
                                <?php else: ?>
                                    <div class="col-md-6">
                                        <a href="uploads/announcements/<?php echo $file['file_path']; ?>" target="_blank" class="d-flex align-items-center p-3 bg-white border rounded text-decoration-none text-dark shadow-sm">
                                            <i class="fas fa-file-pdf fa-2x text-danger me-3"></i>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($file['file_name']); ?></div>
                                                <small class="text-muted">คลิกเพื่อดาวน์โหลด</small>
                                            </div>
                                        </a>
                                    </div>
                                <?php endif; ?>

                            <?php endwhile; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>

    </div>
</div>
<?php include 'includes/footer.php'; ?>