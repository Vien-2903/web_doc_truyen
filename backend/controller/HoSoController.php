<?php
require_once(__DIR__ . '/../model/HoSoModel.php');
require_once(__DIR__ . '/../model/NguoiDungModel.php');

class HoSoController {
    private $hoSoModel;
    private $nguoiDungModel;
    
    public function __construct() {
        $this->hoSoModel = new HoSoModel();
        $this->nguoiDungModel = new NguoiDungModel();
    }

    private function getAvatarUploadDir() {
        $targetDir = realpath(__DIR__ . '/../uploads/avatar');
        if ($targetDir === false) {
            $targetDir = __DIR__ . '/../uploads/avatar';
            if (!is_dir($targetDir)) {
                @mkdir($targetDir, 0755, true);
            }
            $targetDir = realpath($targetDir) ?: $targetDir;
        }

        return $targetDir;
    }

    private function deleteAvatarFile($avatarFileName) {
        $avatarFileName = trim((string)$avatarFileName);
        if ($avatarFileName === '') {
            return;
        }

        $fileName = basename(str_replace('\\', '/', $avatarFileName));
        if ($fileName === '' || $fileName === '.' || $fileName === '..') {
            return;
        }

        $candidates = [
            __DIR__ . '/../uploads/avatar/' . $fileName,
            __DIR__ . '/../../frontend/public/uploads/avatar/' . $fileName
        ];

        foreach ($candidates as $candidate) {
            $fullPath = realpath($candidate);
            if ($fullPath && file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }
    }

    // Upload avatar
    private function uploadAvatar($file) {
        $target_dir = $this->getAvatarUploadDir() . DIRECTORY_SEPARATOR;

        if(!is_uploaded_file($file['tmp_name'])) {
            return false;
        }
        
        // Kiểm tra file
        $imageFileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        
        if(!in_array($imageFileType, $allowed_types)) {
            return false;
        }

        if(@getimagesize($file['tmp_name']) === false) {
            return false;
        }
        
        // Kiểm tra kích thước (max 5MB)
        if($file['size'] > 5000000) {
            return false;
        }
        
        // Tạo tên file mới
        try {
            $randomPrefix = bin2hex(random_bytes(8));
        } catch (Throwable $e) {
            $randomPrefix = uniqid();
        }
        $new_filename = $randomPrefix . '_' . time() . '.' . $imageFileType;
        $target_file = $target_dir . $new_filename;
        
        // Upload file
        if(move_uploaded_file($file['tmp_name'], $target_file)) {
            return $new_filename;
        }
        
        return false;
    }

    // API: Lấy toàn bộ thông tin người dùng đang đăng nhập
    public function getCurrentUserProfileData($sessionUser) {
        if (empty($sessionUser) || empty($sessionUser['id'])) {
            return [
                'status' => 401,
                'body' => [
                    'success' => false,
                    'message' => 'Bạn chưa đăng nhập'
                ]
            ];
        }

        $idNguoiDung = (int) $sessionUser['id'];
        $nguoiDung = $this->nguoiDungModel->getNguoiDungById($idNguoiDung);

        if (!$nguoiDung) {
            return [
                'status' => 404,
                'body' => [
                    'success' => false,
                    'message' => 'Không tìm thấy thông tin người dùng'
                ]
            ];
        }

        $hoSo = $this->hoSoModel->getHoSoByUserId($idNguoiDung);

        $profileData = [
            'ho_ten' => $hoSo['ho_ten'] ?? '',
            'avatar' => $hoSo['avatar'] ?? '',
            'so_dien_thoai' => $hoSo['so_dien_thoai'] ?? '',
            'gioi_tinh' => $hoSo['gioi_tinh'] ?? '',
            'ngay_sinh' => $hoSo['ngay_sinh'] ?? '',
            'dia_chi' => $hoSo['dia_chi'] ?? ''
        ];

        return [
            'status' => 200,
            'body' => [
                'success' => true,
                'message' => 'Lấy thông tin người dùng thành công',
                'data' => [
                    'id' => (int) $nguoiDung['id'],
                    'ten_dang_nhap' => $nguoiDung['ten_dang_nhap'] ?? '',
                    'email' => $nguoiDung['email'] ?? '',
                    'vai_tro' => $nguoiDung['vai_tro'] ?? 'user',
                    'profile' => $profileData
                ]
            ]
        ];
    }

    // API: Cập nhật hồ sơ cho người dùng hiện tại (hỗ trợ user mới chưa có hồ sơ)
    public function updateCurrentUserProfileData($sessionUser, $inputData, $avatarFile = null) {
        if (empty($sessionUser) || empty($sessionUser['id'])) {
            return [
                'status' => 401,
                'body' => [
                    'success' => false,
                    'message' => 'Bạn chưa đăng nhập'
                ]
            ];
        }

        if (!is_array($inputData)) {
            return [
                'status' => 400,
                'body' => [
                    'success' => false,
                    'message' => 'Dữ liệu gửi lên không hợp lệ'
                ]
            ];
        }

        $idNguoiDung = (int) $sessionUser['id'];
        $hoSoHienTai = $this->hoSoModel->getHoSoByUserId($idNguoiDung);

        $ho_ten = array_key_exists('ho_ten', $inputData)
            ? trim((string) $inputData['ho_ten'])
            : ($hoSoHienTai['ho_ten'] ?? '');

        $ngay_sinh = array_key_exists('ngay_sinh', $inputData)
            ? trim((string) $inputData['ngay_sinh'])
            : ($hoSoHienTai['ngay_sinh'] ?? '');

        $gioi_tinh = array_key_exists('gioi_tinh', $inputData)
            ? strtolower(trim((string) $inputData['gioi_tinh']))
            : ($hoSoHienTai['gioi_tinh'] ?? '');

        $so_dien_thoai = array_key_exists('so_dien_thoai', $inputData)
            ? preg_replace('/\s+/', '', (string) $inputData['so_dien_thoai'])
            : ($hoSoHienTai['so_dien_thoai'] ?? '');

        $dia_chi = array_key_exists('dia_chi', $inputData)
            ? trim((string) $inputData['dia_chi'])
            : ($hoSoHienTai['dia_chi'] ?? '');

        $requiredErrors = [];
        if ($ho_ten === '') {
            $requiredErrors[] = 'Họ và tên không được để trống';
        }
        if ($so_dien_thoai === '') {
            $requiredErrors[] = 'Số điện thoại không được để trống';
        }
        if ($gioi_tinh === '') {
            $requiredErrors[] = 'Giới tính không được để trống';
        }
        if ($ngay_sinh === '') {
            $requiredErrors[] = 'Ngày sinh không được để trống';
        }
        if ($dia_chi === '') {
            $requiredErrors[] = 'Địa chỉ không được để trống';
        }

        if (!empty($requiredErrors)) {
            return [
                'status' => 422,
                'body' => [
                    'success' => false,
                    'message' => 'Vui lòng nhập đầy đủ thông tin bắt buộc',
                    'errors' => $requiredErrors
                ]
            ];
        }

        if ($so_dien_thoai !== '' && !preg_match('/^[0-9]{10,11}$/', $so_dien_thoai)) {
            return [
                'status' => 400,
                'body' => [
                    'success' => false,
                    'message' => 'Số điện thoại phải có 10-11 chữ số'
                ]
            ];
        }

        if ($gioi_tinh !== '' && !in_array($gioi_tinh, ['nam', 'nu', 'khac'], true)) {
            return [
                'status' => 400,
                'body' => [
                    'success' => false,
                    'message' => 'Giới tính không hợp lệ'
                ]
            ];
        }

        if ($ngay_sinh !== '') {
            $ngaySinhDate = DateTime::createFromFormat('Y-m-d', $ngay_sinh);
            $ngaySinhValid = $ngaySinhDate && $ngaySinhDate->format('Y-m-d') === $ngay_sinh;

            if (!$ngaySinhValid || $ngay_sinh > date('Y-m-d')) {
                return [
                    'status' => 400,
                    'body' => [
                        'success' => false,
                        'message' => 'Ngày sinh không hợp lệ'
                    ]
                ];
            }
        }

        if ($so_dien_thoai !== '' && $this->hoSoModel->checkSoDienThoaiExists($so_dien_thoai, $idNguoiDung)) {
            return [
                'status' => 409,
                'body' => [
                    'success' => false,
                    'message' => 'Số điện thoại đã tồn tại'
                ]
            ];
        }

        if ($this->hoSoModel->checkHoSoExists($idNguoiDung)) {
            $saved = $this->hoSoModel->updateHoSo($idNguoiDung, $ho_ten, $ngay_sinh, $gioi_tinh, $so_dien_thoai, $dia_chi);
        } else {
            $saved = $this->hoSoModel->createHoSo($idNguoiDung, $ho_ten, $ngay_sinh, $gioi_tinh, $so_dien_thoai, $dia_chi);
        }

        if (!$saved) {
            return [
                'status' => 500,
                'body' => [
                    'success' => false,
                    'message' => 'Không thể cập nhật hồ sơ'
                ]
            ];
        }

        // Nếu có gửi avatar thì cập nhật thêm ảnh đại diện.
        if (is_array($avatarFile) && isset($avatarFile['error']) && (int)$avatarFile['error'] !== UPLOAD_ERR_NO_FILE) {
            if ((int)$avatarFile['error'] !== UPLOAD_ERR_OK) {
                return [
                    'status' => 400,
                    'body' => [
                        'success' => false,
                        'message' => 'Upload ảnh thất bại! Mã lỗi: ' . (int)$avatarFile['error']
                    ]
                ];
            }

            $oldAvatar = $this->hoSoModel->getAvatar($idNguoiDung);
            $newAvatar = $this->uploadAvatar($avatarFile);

            if ($newAvatar === false) {
                return [
                    'status' => 400,
                    'body' => [
                        'success' => false,
                        'message' => 'Upload ảnh thất bại! Kiểm tra định dạng (JPG, JPEG, PNG, GIF, WEBP) và kích thước (max 5MB)'
                    ]
                ];
            }

            $avatarSaved = $this->hoSoModel->updateAvatar($idNguoiDung, $newAvatar);
            if (!$avatarSaved) {
                $this->deleteAvatarFile($newAvatar);

                return [
                    'status' => 500,
                    'body' => [
                        'success' => false,
                        'message' => 'Không thể lưu thông tin avatar'
                    ]
                ];
            }

            if (!empty($oldAvatar)) {
                $this->deleteAvatarFile($oldAvatar);
            }
        }

        $response = $this->getCurrentUserProfileData($sessionUser);
        if (($response['status'] ?? 500) === 200) {
            $response['body']['message'] = 'Cập nhật hồ sơ thành công';
        }
        return $response;
    }
}
?>