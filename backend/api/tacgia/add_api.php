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

require_once __DIR__ . '/../../model/TacGiaModel.php';

$input = json_decode(file_get_contents("php://input"), true) ?: [];

$ten_tacgia = trim($_POST['ten_tacgia'] ?? ($input['ten_tacgia'] ?? ''));
$but_danh = trim($_POST['but_danh'] ?? ($input['but_danh'] ?? ''));
$gioi_thieu = trim($_POST['gioi_thieu'] ?? ($input['gioi_thieu'] ?? ''));

if ($ten_tacgia === '') {
	http_response_code(400);
	echo json_encode([
		"success" => false,
		"message" => "Tên tác giả không được để trống"
	], JSON_UNESCAPED_UNICODE);
	exit();
}

$model = new TacGiaModel();

if ($model->checkTenTacGiaExists($ten_tacgia)) {
	http_response_code(409);
	echo json_encode([
		"success" => false,
		"message" => "Tên tác giả đã tồn tại"
	], JSON_UNESCAPED_UNICODE);
	exit();
}

$ok = $model->insert($ten_tacgia, $but_danh, $gioi_thieu);

if ($ok) {
	http_response_code(201);
	echo json_encode([
		"success" => true,
		"message" => "Thêm tác giả thành công",
		"data" => [
			"ten_tacgia" => $ten_tacgia,
			"but_danh" => $but_danh,
			"gioi_thieu" => $gioi_thieu
		]
	], JSON_UNESCAPED_UNICODE);
	exit();
}

http_response_code(500);
echo json_encode([
	"success" => false,
	"message" => "Có lỗi xảy ra khi thêm tác giả"
], JSON_UNESCAPED_UNICODE);
exit();
?>
