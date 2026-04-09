<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Chỉ chấp nhận phương thức POST"
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

require_once(__DIR__ . '/../../controller/BinhluanController.php');

$controller = new BinhluanController();

$data = [
    'id_chuong' => $_POST['id_chuong'] ?? '',
    'noi_dung' => trim($_POST['noi_dung'] ?? '')
];

$response = $controller->createCommentApi($data);

http_response_code($response['status']);
echo json_encode($response['body'], JSON_UNESCAPED_UNICODE);
exit();
?>