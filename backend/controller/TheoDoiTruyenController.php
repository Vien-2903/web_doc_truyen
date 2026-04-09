<?php
require_once __DIR__ . '/../model/TheoDoiTruyenModel.php';

class TheoDoiTruyenController {
    private $model;

    public function __construct($isApi = false) {
        $this->model = new TheoDoiTruyenModel();
    }

    public function checkFollowApi($data) {
        $id_nguoidung = $data['id_nguoidung'] ?? 0;
        $id_truyen = $data['id_truyen'] ?? 0;

        if (!$id_nguoidung || !$id_truyen) {
            return [
                'status' => 400,
                'body' => [
                    'success' => false,
                    'message' => 'Thieu du lieu (id_nguoidung, id_truyen)',
                    'is_following' => null
                ]
            ];
        }

        $is_following = $this->model->isFollowing($id_nguoidung, $id_truyen);

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'message' => 'Kiem tra thanh cong',
                'is_following' => $is_following,
                'id_truyen' => $id_truyen,
                'id_nguoidung' => $id_nguoidung
            ]
        ];
    }

    public function toggleFollowApi($data) {
        $id_nguoidung = $data['id_nguoidung'] ?? 0;
        $id_truyen = $data['id_truyen'] ?? 0;

        if (!$id_nguoidung || !$id_truyen) {
            return [
                'status' => 400,
                'body' => [
                    'success' => false,
                    'message' => 'Thieu du lieu (id_nguoidung, id_truyen)',
                    'action' => null,
                    'is_following' => null
                ]
            ];
        }

        $is_currently_following = $this->model->isFollowing($id_nguoidung, $id_truyen);
        $action = '';
        $new_is_following = false;

        if ($is_currently_following) {
            $this->model->removeFollow($id_nguoidung, $id_truyen);
            $action = 'unfollowed';
            $new_is_following = false;
        } else {
            $this->model->addFollow($id_nguoidung, $id_truyen);
            $action = 'followed';
            $new_is_following = true;
        }

        $updated_following = $this->model->getFollowedTruyenByUser($id_nguoidung);

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'message' => $action === 'followed' ? 'Da theo doi truyen' : 'Da bo theo doi truyen',
                'action' => $action,
                'is_following' => $new_is_following,
                'id_truyen' => $id_truyen,
                'id_nguoidung' => $id_nguoidung,
                'following' => $updated_following
            ]
        ];
    }

    public function getFollowingApi($id_nguoidung) {
        if (!$id_nguoidung) {
            return [
                'status' => 400,
                'body' => [
                    'success' => false,
                    'message' => 'Thieu id_nguoidung',
                    'count' => 0,
                    'data' => []
                ]
            ];
        }

        $following = $this->model->getFollowedTruyenByUser($id_nguoidung);

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'message' => 'Lay danh sach thanh cong',
                'count' => count($following),
                'data' => $following
            ]
        ];
    }

    public function removeFollowApi($data) {
        $id_nguoidung = $data['id_nguoidung'] ?? 0;
        $id_truyen = $data['id_truyen'] ?? 0;

        if (!$id_nguoidung || !$id_truyen) {
            return [
                'status' => 400,
                'body' => [
                    'success' => false,
                    'message' => 'Thieu du lieu (id_nguoidung, id_truyen)',
                    'is_following' => null
                ]
            ];
        }

        $this->model->removeFollow($id_nguoidung, $id_truyen);
        $updated_following = $this->model->getFollowedTruyenByUser($id_nguoidung);

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'message' => 'Da bo theo doi truyen',
                'is_following' => false,
                'id_truyen' => $id_truyen,
                'id_nguoidung' => $id_nguoidung,
                'following' => $updated_following
            ]
        ];
    }

    public function addFollowApi($data) {
        $id_nguoidung = $data['id_nguoidung'] ?? 0;
        $id_truyen = $data['id_truyen'] ?? 0;

        if (!$id_nguoidung || !$id_truyen) {
            return [
                'status' => 400,
                'body' => [
                    'success' => false,
                    'message' => 'Thieu du lieu (id_nguoidung, id_truyen)',
                    'is_following' => null
                ]
            ];
        }

        $this->model->addFollow($id_nguoidung, $id_truyen);
        $updated_following = $this->model->getFollowedTruyenByUser($id_nguoidung);

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'message' => 'Da theo doi truyen',
                'is_following' => true,
                'id_truyen' => $id_truyen,
                'id_nguoidung' => $id_nguoidung,
                'following' => $updated_following
            ]
        ];
    }
}
?>
