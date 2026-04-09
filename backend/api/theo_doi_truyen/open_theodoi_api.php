<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../../controller/TheoDoiTruyenController.php');

if (empty($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Ban can dang nhap de xem danh sach theo doi.',
        'login_required' => true
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$id_nguoidung = $_SESSION['user']['id'];
$controller = new TheoDoiTruyenController(true);
$response = $controller->getFollowingApi($id_nguoidung);

http_response_code($response['status']);
echo json_encode($response['body'], JSON_UNESCAPED_UNICODE);
exit();
?>
