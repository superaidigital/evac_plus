<?php
include 'config/db.php';
header('Content-Type: application/json');
$id_card = isset($_GET['id_card']) ? trim($_GET['id_card']) : '';
$stmt = $conn->prepare("SELECT id FROM evacuees WHERE id_card = ?");
$stmt->bind_param("s", $id_card);
$stmt->execute();
echo json_encode(['exists' => $stmt->get_result()->num_rows > 0]);
?>