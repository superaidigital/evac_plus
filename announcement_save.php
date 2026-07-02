<?php
include 'config/db.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }

$action = $_REQUEST['action'] ?? '';

// --- AJAX: Get Attachments List ---
if ($action == 'get_attachments') {
    $id = intval($_GET['id']);
    $sql = "SELECT * FROM announcement_attachments WHERE announcement_id = $id ORDER BY id ASC";
    $res = $conn->query($sql);
    $data = [];
    while($row = $res->fetch_assoc()) { $data[] = $row; }
    echo json_encode($data);
    exit;
}

// --- AJAX: Get Single Announcement for Edit ---
if ($action == 'get_single') {
    $id = intval($_GET['id']);
    $sql = "SELECT * FROM announcements WHERE id = $id";
    $ann = $conn->query($sql)->fetch_assoc();
    
    // Get Attachments
    $att_sql = "SELECT * FROM announcement_attachments WHERE announcement_id = $id";
    $att_res = $conn->query($att_sql);
    $ann['attachments'] = [];
    while($row = $att_res->fetch_assoc()) { $ann['attachments'][] = $row; }
    
    echo json_encode($ann);
    exit;
}

// --- DELETE ---
if ($action == 'delete') {
    $id = intval($_GET['id']);
    
    // ลบไฟล์จริงออกจาก Server
    $files = $conn->query("SELECT file_path FROM announcement_attachments WHERE announcement_id = $id AND file_type != 'link'");
    while($f = $files->fetch_assoc()) {
        @unlink('uploads/announcements/' . $f['file_path']);
    }
    
    // ลบข้อมูลใน DB (Cascading delete ควรจัดการ child table แต่ลบ manual เพื่อความชัวร์)
    $conn->query("DELETE FROM announcement_attachments WHERE announcement_id = $id");
    $conn->query("DELETE FROM announcements WHERE id = $id");
    
    $_SESSION['swal_icon'] = 'success';
    $_SESSION['swal_title'] = 'ลบสำเร็จ';
    header("Location: announcement_manager.php");
    exit;
}

// --- SAVE (Insert/Update) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $action == 'save') {
    $id = intval($_POST['id']);
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $type = $_POST['type'];
    $target_shelter_id = intval($_POST['target_shelter_id']);
    $status = isset($_POST['status']) ? 'Active' : 'Inactive';

    if ($id == 0) {
        $stmt = $conn->prepare("INSERT INTO announcements (title, content, type, target_shelter_id, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssis", $title, $content, $type, $target_shelter_id, $status);
        $stmt->execute();
        $id = $stmt->insert_id;
    } else {
        $stmt = $conn->prepare("UPDATE announcements SET title=?, content=?, type=?, target_shelter_id=?, status=? WHERE id=?");
        $stmt->bind_param("sssisi", $title, $content, $type, $target_shelter_id, $status, $id);
        $stmt->execute();
    }

    // 1. Handle File Deletions (from Edit)
    if (isset($_POST['delete_files'])) {
        foreach($_POST['delete_files'] as $del_id) {
            $del_id = intval($del_id);
            $f = $conn->query("SELECT file_path, file_type FROM announcement_attachments WHERE id = $del_id")->fetch_assoc();
            if ($f && $f['file_type'] != 'link') {
                @unlink('uploads/announcements/' . $f['file_path']);
            }
            $conn->query("DELETE FROM announcement_attachments WHERE id = $del_id");
        }
    }

    // 2. Handle New File Uploads
    if (isset($_FILES['attachments'])) {
        $total = count($_FILES['attachments']['name']);
        $upload_dir = 'uploads/announcements/';
        
        for ($i = 0; $i < $total; $i++) {
            if ($_FILES['attachments']['error'][$i] == 0) {
                $tmp_name = $_FILES['attachments']['tmp_name'][$i];
                $orig_name = $_FILES['attachments']['name'][$i];
                $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
                
                // Rename file
                $new_name = uniqid() . '_' . time() . '.' . $ext;
                
                if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                    $file_type = in_array($ext, ['jpg','jpeg','png','gif']) ? 'image' : 'file';
                    
                    $stmt_att = $conn->prepare("INSERT INTO announcement_attachments (announcement_id, file_type, file_path, file_name) VALUES (?, ?, ?, ?)");
                    $stmt_att->bind_param("isss", $id, $file_type, $new_name, $orig_name);
                    $stmt_att->execute();
                }
            }
        }
    }

    // 3. Handle External Links
    if (isset($_POST['link_urls'])) {
        $link_names = $_POST['link_names'];
        $link_urls = $_POST['link_urls'];
        
        for ($i = 0; $i < count($link_urls); $i++) {
            $url = trim($link_urls[$i]);
            $name = trim($link_names[$i]);
            if (empty($name)) $name = 'Link';
            
            if (!empty($url)) {
                $stmt_link = $conn->prepare("INSERT INTO announcement_attachments (announcement_id, file_type, file_path, file_name) VALUES (?, 'link', ?, ?)");
                $stmt_link->bind_param("iss", $id, $url, $name);
                $stmt_link->execute();
            }
        }
    }

    $_SESSION['swal_icon'] = 'success';
    $_SESSION['swal_title'] = 'บันทึกสำเร็จ';
    header("Location: announcement_manager.php");
    exit;
}
?>