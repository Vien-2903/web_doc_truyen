<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Chi chap nhan POST'
    ]);
    exit();
}

require_once(__DIR__ . '/../../controller/TheoDoiTruyenController.php');

$controller = new TheoDoiTruyenController(true);
$input = json_decode(file_get_contents('php://input'), true) ?: [];

$data = [
    'id_nguoidung' => $input['id_nguoidung'] ?? '',
    'id_truyen' => $input['id_truyen'] ?? ''
];

$response = $controller->toggleFollowApi($data);

http_response_code($response['status']);
echo json_encode($response['body'], JSON_UNESCAPED_UNICODE);
exit();
?>
