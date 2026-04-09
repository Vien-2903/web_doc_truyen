<?php
require_once __DIR__ . '/../model/BinhLuanModel.php';
require_once __DIR__ . '/../model/TruyenModel.php';
require_once __DIR__ . '/../model/ChuongModel.php';

class BinhluanController {
    private $model;
    private $truyenModel;
    private $chuongModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->model = new BinhLuanModel();
        $this->truyenModel = new TruyenModel();
        $this->chuongModel = new ChuongModel();
    }

    private function getCurrentUser() {
        return $_SESSION['user'] ?? null;
    }

    public function getTruyenCommentsApi() {
        $user = $this->getCurrentUser();
        $isAdmin = $user && strtolower(trim((string)($user['vai_tro'] ?? ''))) === 'admin';

        if ($isAdmin) {
            $data = $this->model->getTruyenWithComments();
        } elseif ($user) {
            $data = $this->model->getTruyenWithUserComments($user['id']);
        } else {
            $data = $this->model->getTruyenWithComments();
        }

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'data' => $data
            ]
        ];
    }

    public function getCommentsByTruyenApi($id_truyen) {
        $id_truyen = intval($id_truyen);
        if ($id_truyen <= 0) {
            return [
                'status' => 400,
                'body' => [
                    'success' => false,
                    'message' => 'ID truyện không hợp lệ'
                ]
            ];
        }

        $truyen = $this->truyenModel->getById($id_truyen);
        if (!$truyen) {
            return [
                'status' => 404,
                'body' => [
                    'success' => false,
                    'message' => 'Không tìm thấy truyện'
                ]
            ];
        }

        $comments = $this->model->getByTruyen($id_truyen);
        $currentUser = ['id' => 0, 'vai_tro' => '', 'ten_dang_nhap' => ''];
        $user = $this->getCurrentUser();
        if ($user) {
            $currentUser = [
                'id' => isset($user['id']) ? (int)$user['id'] : 0,
                'vai_tro' => $user['vai_tro'] ?? '',
                'ten_dang_nhap' => $user['ten_dang_nhap'] ?? ''
            ];
        }

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'truyen' => $truyen,
                'comments' => $comments,
                'current_user' => $currentUser
            ]
        ];
    }

    public function getCommentByIdApi($id) {
        $id = intval($id);
        if ($id <= 0) {
            return [
                'status' => 400,
                'body' => [
                    'success' => false,
                    'message' => 'ID bình luận không hợp lệ'
                ]
            ];
        }

        $comment = $this->model->getById($id);
        if (!$comment) {
            return [
                'status' => 404,
                'body' => [
                    'success' => false,
                    'message' => 'Không tìm thấy bình luận'
                ]
            ];
        }

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'data' => $comment
            ]
        ];
    }

    public function createCommentApi($data) {
        $user = $this->getCurrentUser();
        if (!$user) {
            return [
                'status' => 401,
                'body' => [
                    'success' => false,
                    'message' => 'Chưa đăng nhập'
                ]
            ];
        }

        $id_chuong = intval($data['id_chuong'] ?? 0);
        $noi_dung = trim($data['noi_dung'] ?? '');

        if ($id_chuong <= 0) {
            return [
                'status' => 400,
                'body' => [
                    'success' => false,
                    'message' => 'Vui lòng chọn chương'
                ]
            ];
        }

        if ($noi_dung === '' || strlen($noi_dung) < 10) {
            return [
                'status' => 400,
                'body' => [
                    'success' => false,
                    'message' => 'Nội dung phải có ít nhất 10 ký tự'
                ]
            ];
        }

        $result = $this->model->create([
            'id_nguoidung' => $user['id'],
            'id_chuong' => $id_chuong,
            'noi_dung' => $noi_dung
        ]);

        if ($result) {
            return [
                'status' => 201,
                'body' => [
                    'success' => true,
                    'message' => 'Thêm bình luận thành công'
                ]
            ];
        }

        return [
            'status' => 500,
            'body' => [
                'success' => false,
                'message' => 'Không thể thêm bình luận'
            ]
        ];
    }

    public function updateCommentApi($data) {
        $user = $this->getCurrentUser();
        if (!$user) {
            return [
                'status' => 401,
                'body' => [
                    'success' => false,
                    'message' => 'Chưa đăng nhập'
                ]
            ];
        }

        $id = intval($data['id'] ?? 0);
        $noi_dung = trim($data['noi_dung'] ?? '');

        if ($id <= 0) {
            return [
                'status' => 400,
                'body' => [
                    'success' => false,
                    'message' => 'ID bình luận không hợp lệ'
                ]
            ];
        }

        if ($noi_dung === '' || strlen($noi_dung) < 10) {
            return [
                'status' => 400,
                'body' => [
                    'success' => false,
                    'message' => 'Nội dung phải có ít nhất 10 ký tự'
                ]
            ];
        }

        $comment = $this->model->getById($id);
        if (!$comment) {
            return [
                'status' => 404,
                'body' => [
                    'success' => false,
                    'message' => 'Không tìm thấy bình luận'
                ]
            ];
        }

        $isAdmin = strtolower(trim((string)($user['vai_tro'] ?? ''))) === 'admin';
        $isOwner = $comment['id_nguoidung'] == $user['id'];
        if (!$isAdmin && !$isOwner) {
            return [
                'status' => 403,
                'body' => [
                    'success' => false,
                    'message' => 'Bạn không có quyền sửa bình luận này'
                ]
            ];
        }

        if ($this->model->update($id, $noi_dung)) {
            return [
                'status' => 200,
                'body' => [
                    'success' => true,
                    'message' => 'Cập nhật bình luận thành công'
                ]
            ];
        }

        return [
            'status' => 500,
            'body' => [
                'success' => false,
                'message' => 'Cập nhật thất bại'
            ]
        ];
    }

    public function deleteCommentApi($id) {
        $user = $this->getCurrentUser();
        if (!$user) {
            return [
                'status' => 401,
                'body' => [
                    'success' => false,
                    'message' => 'Chưa đăng nhập'
                ]
            ];
        }

        $id = intval($id);
        if ($id <= 0) {
            return [
                'status' => 400,
                'body' => [
                    'success' => false,
                    'message' => 'ID bình luận không hợp lệ'
                ]
            ];
        }

        $comment = $this->model->getById($id);
        if (!$comment) {
            return [
                'status' => 404,
                'body' => [
                    'success' => false,
                    'message' => 'Không tìm thấy bình luận'
                ]
            ];
        }

        $isAdmin = strtolower(trim((string)($user['vai_tro'] ?? ''))) === 'admin';
        $isOwner = $comment['id_nguoidung'] == $user['id'];
        if (!$isAdmin && !$isOwner) {
            return [
                'status' => 403,
                'body' => [
                    'success' => false,
                    'message' => 'Bạn không có quyền xóa bình luận này'
                ]
            ];
        }

        if ($this->model->delete($id)) {
            return [
                'status' => 200,
                'body' => [
                    'success' => true,
                    'message' => 'Xóa bình luận thành công'
                ]
            ];
        }

        return [
            'status' => 500,
            'body' => [
                'success' => false,
                'message' => 'Xóa thất bại'
            ]
        ];
    }
}
?>