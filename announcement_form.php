<?php
/**
 * announcement_form.php
 * หน้าฟอร์มสำหรับ สร้าง/แก้ไข ประกาศ (แยกออกมาจาก Modal)
 */
session_start();
require_once 'config/db.php';
include 'includes/header.php';

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$mode = $id > 0 ? 'edit' : 'add';

// ค่าเริ่มต้น
$data = [
    'title' => '',
    'content' => '',
    'type' => 'info',
    'target_shelter_id' => 0,
    'status' => 'Active'
];
$attachments = [];

// ถ้าเป็นโหมดแก้ไข ให้ดึงข้อมูล
if ($mode == 'edit') {
    $stmt = $conn->prepare("SELECT * FROM announcements WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $data = $row;
        // ดึงไฟล์แนบ
        $att_res = $conn->query("SELECT * FROM announcement_attachments WHERE announcement_id = $id");
        while($att = $att_res->fetch_assoc()) {
            $attachments[] = $att;
        }
    } else {
        // ไม่พบข้อมูล
        echo "<script>window.location='announcement_manager.php';</script>";
        exit;
    }
}

// ดึงข้อมูลศูนย์พักพิง
$shelters = $conn->query("SELECT id, name FROM shelters ORDER BY name ASC");
?>

<style>
    .card-box {
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        border: none;
    }
    .attachment-preview {
        border: 1px solid #dee2e6;
        padding: 10px;
        border-radius: 8px;
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8f9fa;
    }
    .form-label { font-weight: 600; color: #495057; }
</style>

<div class="main-content">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0 text-primary fw-bold">
                    <i class="fas <?php echo $mode=='edit'?'fa-edit':'fa-plus-circle'; ?> me-2"></i>
                    <?php echo $mode=='edit' ? 'แก้ไขประกาศ' : 'สร้างประกาศใหม่'; ?>
                </h4>
            </div>
            <a href="announcement_manager.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> ย้อนกลับ
            </a>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card card-box">
                    <div class="card-body p-4">
                        
                        <form action="announcement_save.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?php echo $id; ?>">
                            <input type="hidden" name="action" value="save">

                            <!-- ส่วนหัวข้อ -->
                            <div class="mb-3">
                                <label class="form-label">หัวข้อประกาศ <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg fw-bold" name="title" value="<?php echo htmlspecialchars($data['title']); ?>" required placeholder="ระบุหัวข้อเรื่อง...">
                            </div>

                            <!-- ตัวเลือก -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">ประเภทความสำคัญ</label>
                                    <select class="form-select" name="type">
                                        <option value="info" <?php echo $data['type']=='info'?'selected':''; ?>>ℹ️ ทั่วไป (Info)</option>
                                        <option value="warning" <?php echo $data['type']=='warning'?'selected':''; ?>>⚠️ แจ้งเตือน (Warning)</option>
                                        <option value="danger" <?php echo $data['type']=='danger'?'selected':''; ?>>🚨 วิกฤต/ด่วนที่สุด (Danger)</option>
                                        <option value="success" <?php echo $data['type']=='success'?'selected':''; ?>>✅ ข่าวดี/ประชาสัมพันธ์ (Success)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">กลุ่มเป้าหมาย</label>
                                    <select class="form-select" name="target_shelter_id">
                                        <option value="0" <?php echo $data['target_shelter_id']==0?'selected':''; ?>>🌐 ทุกศูนย์พักพิง (Public)</option>
                                        <?php while($s = $shelters->fetch_assoc()): ?>
                                            <option value="<?php echo $s['id']; ?>" <?php echo $data['target_shelter_id']==$s['id']?'selected':''; ?>>
                                                <?php echo $s['name']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- เนื้อหา -->
                            <div class="mb-4">
                                <label class="form-label">รายละเอียดเนื้อหา <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="content" rows="8" required placeholder="เขียนรายละเอียดประกาศที่นี่..."><?php echo htmlspecialchars($data['content']); ?></textarea>
                            </div>

                            <hr class="text-muted">

                            <!-- ส่วนจัดการไฟล์แนบ -->
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-primary fw-bold mb-3"><i class="fas fa-paperclip"></i> ไฟล์แนบ (รูปภาพ/PDF)</h6>
                                    
                                    <!-- ไฟล์เดิม -->
                                    <?php if(!empty($attachments)): ?>
                                        <div class="mb-3">
                                            <label class="form-label small text-muted">ไฟล์ที่มีอยู่เดิม:</label>
                                            <?php foreach($attachments as $f): 
                                                if($f['file_type'] == 'link') continue; 
                                                $icon = $f['file_type'] == 'image' ? 'fa-image text-primary' : 'fa-file-pdf text-danger';
                                            ?>
                                            <div class="attachment-preview">
                                                <div class="d-flex align-items-center overflow-hidden">
                                                    <i class="fas <?php echo $icon; ?> me-2 fa-lg"></i>
                                                    <span class="text-truncate"><?php echo $f['file_name']; ?></span>
                                                </div>
                                                <div class="form-check form-check-inline m-0">
                                                    <input class="form-check-input" type="checkbox" name="delete_files[]" value="<?php echo $f['id']; ?>" id="del_<?php echo $f['id']; ?>">
                                                    <label class="form-check-label text-danger small cursor-pointer" for="del_<?php echo $f['id']; ?>">ลบ</label>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- อัปโหลดใหม่ -->
                                    <div class="mb-3">
                                        <label class="form-label small">เพิ่มไฟล์ใหม่ (เลือกได้หลายไฟล์)</label>
                                        <input type="file" class="form-control" name="attachments[]" multiple accept="image/*,.pdf">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <h6 class="text-info fw-bold mb-3"><i class="fas fa-link"></i> ลิ้งค์ภายนอก</h6>
                                    
                                    <!-- ลิ้งค์เดิม -->
                                    <?php if(!empty($attachments)): ?>
                                        <div class="mb-2">
                                            <?php foreach($attachments as $f): 
                                                if($f['file_type'] != 'link') continue; 
                                            ?>
                                            <div class="attachment-preview">
                                                <div class="d-flex align-items-center overflow-hidden">
                                                    <i class="fas fa-link me-2 text-info"></i>
                                                    <span class="text-truncate"><?php echo $f['file_name']; ?></span>
                                                </div>
                                                <div class="form-check form-check-inline m-0">
                                                    <input class="form-check-input" type="checkbox" name="delete_files[]" value="<?php echo $f['id']; ?>" id="del_<?php echo $f['id']; ?>">
                                                    <label class="form-check-label text-danger small cursor-pointer" for="del_<?php echo $f['id']; ?>">ลบ</label>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- เพิ่มลิ้งค์ใหม่ -->
                                    <div id="link_container">
                                        <!-- Dynamic Inputs -->
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary w-100 border-dashed" onclick="addLinkInput()">
                                        <i class="fas fa-plus"></i> เพิ่มช่องใส่ลิ้งค์
                                    </button>
                                </div>
                            </div>

                            <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="status" value="Active" id="status" <?php echo $data['status']=='Active'?'checked':''; ?>>
                                    <label class="form-check-label fw-bold" for="status">เผยแพร่ทันที (Active)</label>
                                </div>
                                <button type="submit" class="btn btn-primary px-5 fw-bold shadow-sm">
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

<script>
    // เพิ่มช่องใส่ลิ้งค์เริ่มต้น 1 ช่องถ้าเพิ่มใหม่
    <?php if($mode == 'add'): ?>
    window.onload = function() { addLinkInput(); };
    <?php endif; ?>

    function addLinkInput() {
        var html = `
            <div class="input-group mb-2 fade-in">
                <span class="input-group-text"><i class="fas fa-link"></i></span>
                <input type="text" class="form-control form-control-sm" name="link_names[]" placeholder="ชื่อลิ้งค์ (เช่น ข่าว)">
                <input type="url" class="form-control form-control-sm" name="link_urls[]" placeholder="https://...">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
            </div>`;
        document.getElementById('link_container').insertAdjacentHTML('beforeend', html);
    }
</script>

<?php include 'includes/footer.php'; ?>