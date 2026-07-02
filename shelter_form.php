<?php
/**
 * shelter_form.php
 * ฟอร์มเพิ่ม/แก้ไขข้อมูลศูนย์พักพิง (สมบูรณ์แบบ)
 * รองรับการดึงข้อมูลแบบ Auto-detect Column (address/location, phone/contact_phone, district, province)
 */

require_once 'includes/header.php';

// ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location='login.php';</script>";
    exit();
}

$mode = isset($_GET['id']) ? 'edit' : 'add';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// ค่าเริ่มต้น
$name = '';
$location = ''; 
$district = '';
$province = '';
$latitude = '13.7563'; // Default BKK
$longitude = '100.5018';
$capacity = '';
$status = 'Open';
$contact_person = '';
$contact_phone = '';
$incident_id = '';

// --- ส่วนดึงข้อมูลมาแสดง (GET - Edit Mode) ---
if ($mode == 'edit' && $id > 0) {
    $stmt = $conn->prepare("SELECT * FROM shelters WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $name = $row['name'];
        
        // Auto-detect: Address หรือ Location
        $location = $row['address'] ?? $row['location'] ?? '';
        
        // ดึงข้อมูลเพิ่มเติม (ถ้ามี)
        $district = $row['district'] ?? '';
        $province = $row['province'] ?? '';
        $incident_id = $row['incident_id'] ?? '';
        
        $latitude = $row['latitude'];
        $longitude = $row['longitude'];
        $capacity = $row['capacity'];
        $status = $row['status'];
        $contact_person = $row['contact_person'];
        
        // Auto-detect: Phone หรือ Contact Phone
        $contact_phone = $row['phone'] ?? $row['contact_phone'] ?? '';
        
        // แปลง status ให้ตรงกับ Dropdown
        if(strtolower($status) == 'open') $status = 'Open';
        elseif(strtolower($status) == 'full') $status = 'Full';
        elseif(strtolower($status) == 'closed') $status = 'Closed';
    }
}

// ดึงรายการภารกิจ (ถ้ามีตาราง incidents)
$incidents = [];
$check_table = $conn->query("SHOW TABLES LIKE 'incidents'");
if ($check_table && $check_table->num_rows > 0) {
    $inc_result = $conn->query("SELECT id, name FROM incidents WHERE status = 'active' ORDER BY id DESC");
    if ($inc_result) $incidents = $inc_result->fetch_all(MYSQLI_ASSOC);
}
?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<style>
    #map-picker {
        height: 400px;
        width: 100%;
        border-radius: 8px;
        border: 2px solid #dee2e6;
        z-index: 1;
    }
    .form-section-title {
        border-left: 5px solid #0d6efd;
        padding-left: 15px;
        font-weight: 700;
        color: #0d6efd;
        margin-bottom: 20px;
        font-size: 1.1rem;
        background-color: #f8f9fa;
        padding: 10px;
        border-radius: 0 5px 5px 0;
    }
</style>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                <i class="fas <?php echo $mode == 'edit' ? 'fa-edit' : 'fa-plus-circle'; ?> text-primary me-2"></i>
                <?php echo $mode == 'edit' ? 'แก้ไขข้อมูลศูนย์พักพิง' : 'เพิ่มศูนย์พักพิงใหม่'; ?>
            </h4>
            <p class="text-muted small mb-0">กรอกรายละเอียดข้อมูลศูนย์พักพิงและระบุพิกัด</p>
        </div>
        <div>
            <a href="shelter_list.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> ย้อนกลับ
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-5">
        <div class="card-body p-4">
            <!-- Form Action ชี้ไปที่ shelter_save.php -->
            <form action="shelter_save.php" method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="id" value="<?php echo $id; ?>">

                <!-- ส่วนที่ 1: ข้อมูลทั่วไป -->
                <div class="form-section-title">ข้อมูลทั่วไป</div>
                
                <?php if (!empty($incidents)): ?>
                <div class="mb-3">
                    <label class="form-label fw-bold">ภารกิจภัยพิบัติ</label>
                    <select name="incident_id" class="form-select">
                        <option value="">-- เลือกภารกิจ (ถ้ามี) --</option>
                        <?php foreach ($incidents as $inc): ?>
                            <option value="<?php echo $inc['id']; ?>" <?php echo $incident_id == $inc['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($inc['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label fw-bold">ชื่อศูนย์พักพิง <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($name); ?>" placeholder="เช่น โรงเรียนบ้านหนองไผ่">
                        <div class="invalid-feedback">กรุณากรอกชื่อศูนย์พักพิง</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">สถานะศูนย์</label>
                        <select name="status" class="form-select">
                            <option value="Open" <?php echo ($status == 'Open') ? 'selected' : ''; ?>>🟢 เปิดใช้งาน (Open)</option>
                            <option value="Full" <?php echo ($status == 'Full') ? 'selected' : ''; ?>>🔴 เต็มแล้ว (Full)</option>
                            <option value="Closed" <?php echo ($status == 'Closed') ? 'selected' : ''; ?>>⚪ ปิด (Closed)</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">ผู้ดูแล / ผู้ติดต่อ</label>
                        <input type="text" name="contact_person" class="form-control" value="<?php echo htmlspecialchars($contact_person); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">เบอร์ติดต่อ</label>
                        <input type="text" name="contact_phone" class="form-control" value="<?php echo htmlspecialchars($contact_phone); ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">ความจุสูงสุด (คน) <span class="text-danger">*</span></label>
                    <input type="number" name="capacity" class="form-control" required min="1" value="<?php echo $capacity; ?>">
                    <div class="invalid-feedback">กรุณาระบุความจุที่ถูกต้อง</div>
                </div>

                <!-- ส่วนที่ 2: ที่ตั้งและพิกัด -->
                <div class="form-section-title mt-4">ที่ตั้งและพิกัดแผนที่ (GIS)</div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">รายละเอียดที่ตั้ง <span class="text-danger">*</span></label>
                    <textarea name="location" class="form-control" rows="2" required placeholder="เลขที่, หมู่บ้าน, ถนน..."><?php echo htmlspecialchars($location); ?></textarea>
                    <div class="invalid-feedback">กรุณากรอกรายละเอียดที่ตั้ง</div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">อำเภอ</label>
                        <input type="text" name="district" class="form-control" value="<?php echo htmlspecialchars($district); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">จังหวัด</label>
                        <input type="text" name="province" class="form-control" value="<?php echo htmlspecialchars($province); ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-primary"><i class="fas fa-map-marker-alt me-1"></i> ระบุพิกัดบนแผนที่</label>
                    <div class="alert alert-info py-2 small">
                        <i class="fas fa-info-circle"></i> ลากหมุดสีฟ้า 🔵 ไปยังตำแหน่งของศูนย์พักพิง หรือคลิกบนแผนที่เพื่อระบุพิกัด
                    </div>
                    
                    <!-- Map Container -->
                    <div id="map-picker" class="shadow-sm mb-3"></div>
                    
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light">Lat</span>
                                <input type="text" name="latitude" id="lat" class="form-control bg-white" readonly value="<?php echo $latitude; ?>">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light">Lng</span>
                                <input type="text" name="longitude" id="lng" class="form-control bg-white" readonly value="<?php echo $longitude; ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="shelter_list.php" class="btn btn-secondary px-4">ยกเลิก</a>
                    <!-- ปุ่มบันทึก -->
                    <button type="submit" class="btn btn-primary px-4 fw-bold">
                        <i class="fas fa-save me-2"></i> บันทึกข้อมูล
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Validation Form
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms).forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        });

        // Map Setup
        var defaultLat = <?php echo floatval($latitude) ?: 13.7563; ?>;
        var defaultLng = <?php echo floatval($longitude) ?: 100.5018; ?>;
        var zoomLevel = <?php echo ($mode == 'edit') ? 15 : 6; ?>;
        
        var map = L.map('map-picker').setView([defaultLat, defaultLng], zoomLevel);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        var marker = L.marker([defaultLat, defaultLng], {
            draggable: true
        }).addTo(map);

        function updateInputs(lat, lng) {
            document.getElementById('lat').value = lat.toFixed(6);
            document.getElementById('lng').value = lng.toFixed(6);
        }

        marker.on('dragend', function(e) {
            var coord = e.target.getLatLng();
            updateInputs(coord.lat, coord.lng);
            map.panTo(coord);
        });

        map.on('click', function(e) {
            marker.setLatLng(e.latlng);
            updateInputs(e.latlng.lat, e.latlng.lng);
        });
        
        // Auto locate for new entries
        <?php if($mode == 'add'): ?>
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                var userLat = position.coords.latitude;
                var userLng = position.coords.longitude;
                map.setView([userLat, userLng], 13);
                marker.setLatLng([userLat, userLng]);
                updateInputs(userLat, userLng);
            });
        }
        <?php endif; ?>
        
        setTimeout(function(){ map.invalidateSize(); }, 500);
    });
</script>