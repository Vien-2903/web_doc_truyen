<?php
require_once __DIR__ . '/../model/YeuthichModel.php';

class YeuthichController {
    private $model;

    public function __construct($isApi = false) {
        $this->model = new YeuthichModel();
    }

    /**
     * API: Kiểm tra trạng thái yêu thích của truyện
     * 
     * Luồng: 
     * Bấm nút tim → Gọi API check để xem trạng thái hiện tại
     * → Nếu chưa yêu thích → hiển thị "thêm" 
     * → Nếu đã yêu thích → hiển thị "bỏ"
     */
    public function checkLikeApi($data) {
        $id_nguoidung = $data['id_nguoidung'] ?? 0;
        $id_truyen = $data['id_truyen'] ?? 0;

        if (!$id_nguoidung || !$id_truyen) {
            return [
                'status' => 400,
                'body' => [
                    'success' => false,
                    'message' => 'Thiếu dữ liệu (id_nguoidung, id_truyen)',
                    'is_liked' => null
                ]
            ];
        }

        $is_liked = $this->model->isLiked($id_nguoidung, $id_truyen);

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'message' => 'Kiểm tra thành công',
                'is_liked' => $is_liked,
                'id_truyen' => $id_truyen,
                'id_nguoidung' => $id_nguoidung
            ]
        ];
    }

    /**
     * API: Toggle (thêm/bỏ) yêu thích
     * 
     * Luồng LỜI CHÍNH:
     * 1. Bấm nút tim → Gọi toggle.php
     * 2. Check xem đã yêu thích chưa
     * 3. Nếu chưa → addLike() → is_liked = true
     * 4. Nếu rồi → removeLike() → is_liked = false
     * 5. Trả về danh sách yêu thích MỚI để frontend cập nhật
     * 6. Frontend cập nhật: tim (đỏ/xám) + danh sách yêu thích
     */
    public function toggleLikeApi($data) {
        $id_nguoidung = $data['id_nguoidung'] ?? 0;
        $id_truyen = $data['id_truyen'] ?? 0;

        if (!$id_nguoidung || !$id_truyen) {
            return [
                'status' => 400,
                'body' => [
                    'success' => false,
                    'message' => 'Thiếu dữ liệu (id_nguoidung, id_truyen)',
                    'action' => null,
                    'is_liked' => null
                ]
            ];
        }

        $is_currently_liked = $this->model->isLiked($id_nguoidung, $id_truyen);
        $action = '';
        $new_is_liked = false;

        if ($is_currently_liked) {
            // Đã yêu thích → bỏ yêu thích
            $this->model->removeLike($id_nguoidung, $id_truyen);
            $action = 'removed';
            $new_is_liked = false;
        } else {
            // Chưa yêu thích → thêm yêu thích
            $this->model->addLike($id_nguoidung, $id_truyen);
            $action = 'added';
            $new_is_liked = true;
        }

        // Lấy danh sách yêu thích CẬP NHẬT để gửi về frontend
        $updated_favorites = $this->model->getLikedTruyenByUser($id_nguoidung);

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'message' => $action === 'added' ? 'Đã thêm yêu thích' : 'Đã bỏ yêu thích',
                'action' => $action,
                'is_liked' => $new_is_liked,
                'id_truyen' => $id_truyen,
                'id_nguoidung' => $id_nguoidung,
                'favorites' => $updated_favorites  // Danh sách yêu thích mới
            ]
        ];
    }

    /**
     * API: Lấy danh sách yêu thích của người dùng
     * 
     * Frontend gọi khi:
     * - Vào trang chủ (để load danh sách + đánh dấu các tim đã yêu thích)
     * - Sau khi toggle xong (để refresh danh sách)
     */
    public function getFavoritesApi($id_nguoidung) {
        if (!$id_nguoidung) {
            return [
                'status' => 400,
                'body' => [
                    'success' => false,
                    'message' => 'Thiếu id_nguoidung',
                    'count' => 0,
                    'data' => []
                ]
            ];
        }

        $favorites = $this->model->getLikedTruyenByUser($id_nguoidung);

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'message' => 'Lấy danh sách thành công',
                'count' => count($favorites),
                'data' => $favorites
            ]
        ];
    }

    /**
     * API: Bỏ yêu thích (chỉ bỏ, không toggle)
     * 
     * Dùng khi muốn bỏ → gửi danh sách mới về frontend
     */
    public function removeLikeApi($data) {
        $id_nguoidung = $data['id_nguoidung'] ?? 0;
        $id_truyen = $data['id_truyen'] ?? 0;

        if (!$id_nguoidung || !$id_truyen) {
            return [
                'status' => 400,
                'body' => [
                    'success' => false,
                    'message' => 'Thiếu dữ liệu (id_nguoidung, id_truyen)',
                    'is_liked' => null
                ]
            ];
        }

        $this->model->removeLike($id_nguoidung, $id_truyen);
        $updated_favorites = $this->model->getLikedTruyenByUser($id_nguoidung);

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'message' => 'Đã xóa khỏi yêu thích',
                'is_liked' => false,
                'id_truyen' => $id_truyen,
                'id_nguoidung' => $id_nguoidung,
                'favorites' => $updated_favorites
            ]
        ];
    }

    /**
     * API: Thêm yêu thích (chỉ thêm, không toggle)
     * 
     * Dùng khi muốn thêm → gửi danh sách mới về frontend
     */
    public function addLikeApi($data) {
        $id_nguoidung = $data['id_nguoidung'] ?? 0;
        $id_truyen = $data['id_truyen'] ?? 0;

        if (!$id_nguoidung || !$id_truyen) {
            return [
                'status' => 400,
                'body' => [
                    'success' => false,
                    'message' => 'Thiếu dữ liệu (id_nguoidung, id_truyen)',
                    'is_liked' => null
                ]
            ];
        }

        $this->model->addLike($id_nguoidung, $id_truyen);
        $updated_favorites = $this->model->getLikedTruyenByUser($id_nguoidung);

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'message' => 'Đã thêm yêu thích',
                'is_liked' => true,
                'id_truyen' => $id_truyen,
                'id_nguoidung' => $id_nguoidung,
                'favorites' => $updated_favorites
            ]
        ];
    }
}
?>
