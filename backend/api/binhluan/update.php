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
        "message" => "Chi chap nhan phuong thuc POST"
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

require_once(__DIR__ . '/../../controller/BinhluanController.php');

$payload = json_decode(file_get_contents('php://input'), true);
$data = [
    'id' => $payload['id'] ?? ($_POST['id'] ?? 0),
    'noi_dung' => $payload['noi_dung'] ?? ($_POST['noi_dung'] ?? '')
];

$controller = new BinhluanController();
$response = $controller->updateCommentApi($data);

http_response_code($response['status']);
echo json_encode($response['body'], JSON_UNESCAPED_UNICODE);
exit();
?>