<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT'])) {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Chỉ chấp nhận phương thức POST hoặc PUT"
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

require_once(__DIR__ . '/../../controller/BinhluanController.php');

$controller = new BinhluanController();

$rawInput = json_decode(file_get_contents('php://input'), true);
if (!is_array($rawInput)) {
    $rawInput = [];
}

$data = [
    'id' => $_POST['id'] ?? ($rawInput['id'] ?? 0),
    'noi_dung' => trim($_POST['noi_dung'] ?? ($rawInput['noi_dung'] ?? ''))
];

$response = $controller->updateCommentApi($data);

http_response_code($response['status']);
echo json_encode($response['body'], JSON_UNESCAPED_UNICODE);
exit();
?>
