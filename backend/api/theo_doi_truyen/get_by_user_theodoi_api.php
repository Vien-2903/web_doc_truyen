<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

require_once(__DIR__ . '/../../controller/TheoDoiTruyenController.php');

$controller = new TheoDoiTruyenController(true);
$input = json_decode(file_get_contents('php://input'), true) ?: [];

$id_nguoidung = $input['id_nguoidung'] ?? 0;
$response = $controller->getFollowingApi($id_nguoidung);

http_response_code($response['status']);
echo json_encode($response['body'], JSON_UNESCAPED_UNICODE);
exit();
?>
