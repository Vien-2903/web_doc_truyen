<?php
header('Content-Type: application/json; charset=UTF-8');

require_once(__DIR__ . '/../../controller/TheoDoiTruyenController.php');

$controller = new TheoDoiTruyenController(true);
$input = json_decode(file_get_contents('php://input'), true) ?: [];

$data = [
    'id_nguoidung' => $input['id_nguoidung'] ?? '',
    'id_truyen' => $input['id_truyen'] ?? ''
];

$response = $controller->checkFollowApi($data);

http_response_code($response['status']);
echo json_encode($response['body'], JSON_UNESCAPED_UNICODE);
exit();
?>
