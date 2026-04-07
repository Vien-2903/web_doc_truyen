<?php
require_once __DIR__ . '/../model/YeuthichModel.php';
require_once(__DIR__ . '/../middleware/AuthMiddleware.php');

class YeuthichController {
    private $model;

    public function __construct($isApi = false) {

    // ❗ CHỈ check login khi KHÔNG phải API
    if (!$isApi) {
        AuthMiddleware::checkLogin();

        if ($_SESSION['user']['vai_tro'] === 'admin') {
            $_SESSION['error'] = 'Chức năng yêu thích chỉ dành cho người dùng!';
            header('Location: /web_doc_truyen/frontend/public/index.php');
            exit();
        }
    }

    $this->model = new YeuthichModel();
}

    // ================= VIEW =================
    public function myFavorites() {
        $id = $_SESSION['user']['id'];
        $truyens = $this->model->getLikedTruyenByUser($id);
        require_once __DIR__ . '/../view/yeuthich/my_favorites.php';
    }

    // ================= AJAX =================
    public function toggleAjax() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false]);
            exit();
        }

        $id_nguoidung = $_SESSION['user']['id'];
        $id_truyen = $_POST['id_truyen'] ?? 0;

        if (!$id_truyen) {
            echo json_encode(['success' => false]);
            exit();
        }

        if ($this->model->isLiked($id_nguoidung, $id_truyen)) {
            $this->model->removeLike($id_nguoidung, $id_truyen);

            echo json_encode(['success' => true, 'action' => 'removed']);
        } else {
            $this->model->addLike($id_nguoidung, $id_truyen);

            echo json_encode(['success' => true, 'action' => 'added']);
        }

        exit();
    }

    public function remove() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_SESSION['user']['id'];
            $id_truyen = $_POST['id_truyen'] ?? 0;

            if ($id_truyen) {
                $this->model->removeLike($id, $id_truyen);
            }

            header("Location: /web_doc_truyen/frontend/public/index.php?page=yeuthich&action=myFavorites");
            exit();
        }
    }

    // ================= API =================

    // Toggle
    public function toggleLikeApi($data) {
        $id_nguoidung = $data['id_nguoidung'] ?? 0;
        $id_truyen = $data['id_truyen'] ?? 0;

        if (!$id_nguoidung || !$id_truyen) {
            return [
                'status' => 400,
                'body' => [
                    'success' => false,
                    'message' => 'Thiếu dữ liệu'
                ]
            ];
        }

        if ($this->model->isLiked($id_nguoidung, $id_truyen)) {
            $this->model->removeLike($id_nguoidung, $id_truyen);
            return [
                'status' => 200,
                'body' => [
                    'success' => true,
                    'message' => 'Đã bỏ yêu thích',
                    'is_liked' => false
                ]
            ];
        } else {
            $this->model->addLike($id_nguoidung, $id_truyen);
            return [
                'status' => 200,
                'body' => [
                    'success' => true,
                    'message' => 'Đã thêm yêu thích',
                    'is_liked' => true
                ]
            ];
        }
    }

    // Get favorites
    public function getFavoritesApi($id_nguoidung) {
        if (!$id_nguoidung) {
            return [
                'status' => 400,
                'body' => [
                    'success' => false,
                    'message' => 'Thiếu id người dùng'
                ]
            ];
        }

        $data = $this->model->getLikedTruyenByUser($id_nguoidung);

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'message' => 'Lấy danh sách thành công',
                'data' => $data
            ]
        ];
    }

    // Remove
    public function removeLikeApi($data) {
        $id_nguoidung = $data['id_nguoidung'] ?? 0;
        $id_truyen = $data['id_truyen'] ?? 0;

        if (!$id_nguoidung || !$id_truyen) {
            return [
                'status' => 400,
                'body' => [
                    'success' => false,
                    'message' => 'Thiếu dữ liệu'
                ]
            ];
        }

        $this->model->removeLike($id_nguoidung, $id_truyen);

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'message' => 'Đã xóa khỏi yêu thích'
            ]
        ];
    }

    // Check
    public function checkLikeApi($data) {
        $id_nguoidung = $data['id_nguoidung'] ?? 0;
        $id_truyen = $data['id_truyen'] ?? 0;

        if (!$id_nguoidung || !$id_truyen) {
            return [
                'status' => 400,
                'body' => [
                    'success' => false,
                    'message' => 'Thiếu dữ liệu'
                ]
            ];
        }

        $liked = $this->model->isLiked($id_nguoidung, $id_truyen);

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'message' => 'Kiểm tra thành công',
                'is_liked' => $liked
            ]
        ];
    }

    // ================= HELPER =================
    private function response($status, $success, $message = '') {
        return [
            'status' => $status,
            'body' => [
                'success' => $success,
                'message' => $message
            ]
        ];
    }
}
?>