<?php
require_once __DIR__ . '/../database/myconnection.php';

class BinhluanModel {
    private $conn;
    private $table = "binhluan";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // Lấy tất cả bình luận của 1 truyện (theo chương)
    public function getByTruyen($id_truyen) {
        $id_truyen = mysqli_real_escape_string($this->conn, $id_truyen);
        
        $query = "SELECT bl.*, 
                         nd.ten_dang_nhap,
                    nd.vai_tro,
                    hs.avatar,
                         c.so_chuong,
                         c.tieu_de as tieu_de_chuong
                  FROM {$this->table} bl
                  INNER JOIN nguoidung nd ON bl.id_nguoidung = nd.id
                LEFT JOIN hoso_nguoidung hs ON hs.id_nguoidung = nd.id
                  INNER JOIN chuong c ON bl.id_chuong = c.id
                  WHERE c.id_truyen = '$id_truyen'
                  ORDER BY bl.ngay_tao DESC";
        
        $result = mysqli_query($this->conn, $query);
        
        if (!$result) {
            die("Lỗi truy vấn: " . mysqli_error($this->conn));
        }
        
        $comments = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $comments[] = $row;
        }
        
        return $comments;
    }

    // Lấy tất cả bình luận của 1 chương
    public function getByChuong($id_chuong) {
        $id_chuong = mysqli_real_escape_string($this->conn, $id_chuong);
        
        $query = "SELECT bl.*, 
                         nd.ten_dang_nhap
                  FROM {$this->table} bl
                  INNER JOIN nguoidung nd ON bl.id_nguoidung = nd.id
                  WHERE bl.id_chuong = '$id_chuong'
                  ORDER BY bl.ngay_tao DESC";
        
        $result = mysqli_query($this->conn, $query);
        
        if (!$result) {
            die("Lỗi truy vấn: " . mysqli_error($this->conn));
        }
        
        $comments = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $comments[] = $row;
        }
        
        return $comments;
    }

    // Lấy 1 bình luận theo ID
    public function getById($id) {
        $id = mysqli_real_escape_string($this->conn, $id);
        
        $query = "SELECT bl.*, 
                         nd.ten_dang_nhap
                  FROM {$this->table} bl
                  INNER JOIN nguoidung nd ON bl.id_nguoidung = nd.id
                  WHERE bl.id = '$id'";
        
        $result = mysqli_query($this->conn, $query);
        
        if (!$result) {
            die("Lỗi truy vấn: " . mysqli_error($this->conn));
        }
        
        return mysqli_fetch_assoc($result);
    }

    // Thêm bình luận mới
    public function create($data) {
        $id_nguoidung = mysqli_real_escape_string($this->conn, $data['id_nguoidung']);
        $id_chuong = mysqli_real_escape_string($this->conn, $data['id_chuong']);
        $noi_dung = mysqli_real_escape_string($this->conn, $data['noi_dung']);
        
        $query = "INSERT INTO {$this->table} (id_nguoidung, id_chuong, noi_dung) 
                  VALUES ('$id_nguoidung', '$id_chuong', '$noi_dung')";
        
        return mysqli_query($this->conn, $query);
    }

    // Cập nhật bình luận
    public function update($id, $noi_dung) {
        $id = mysqli_real_escape_string($this->conn, $id);
        $noi_dung = mysqli_real_escape_string($this->conn, $noi_dung);
        
        $query = "UPDATE {$this->table} 
                  SET noi_dung = '$noi_dung'
                  WHERE id = '$id'";
        
        return mysqli_query($this->conn, $query);
    }

    // Xóa bình luận
    public function delete($id) {
        $id = mysqli_real_escape_string($this->conn, $id);
        $query = "DELETE FROM {$this->table} WHERE id = '$id'";
        return mysqli_query($this->conn, $query);
    }

    // Đếm số bình luận của 1 truyện
    public function countByTruyen($id_truyen) {
        $id_truyen = mysqli_real_escape_string($this->conn, $id_truyen);
        
        $query = "SELECT COUNT(*) as total 
                  FROM {$this->table} bl
                  INNER JOIN chuong c ON bl.id_chuong = c.id
                  WHERE c.id_truyen = '$id_truyen'";
        
        $result = mysqli_query($this->conn, $query);
        
        if (!$result) {
            die("Lỗi truy vấn: " . mysqli_error($this->conn));
        }
        
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }

    // Đếm số bình luận của 1 chương
    public function countByChuong($id_chuong) {
        $id_chuong = mysqli_real_escape_string($this->conn, $id_chuong);
        
        $query = "SELECT COUNT(*) as total 
                  FROM {$this->table} 
                  WHERE id_chuong = '$id_chuong'";
        
        $result = mysqli_query($this->conn, $query);
        
        if (!$result) {
            die("Lỗi truy vấn: " . mysqli_error($this->conn));
        }
        
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }

    // Lấy danh sách truyện có bình luận (để quản lý)
    public function getTruyenWithComments() {
        $query = "SELECT t.*, 
                         tg.ten_tacgia,
                         tg.but_danh,
                         (SELECT COUNT(*) 
                          FROM {$this->table} bl
                          INNER JOIN chuong c ON bl.id_chuong = c.id
                          WHERE c.id_truyen = t.id) as total_comments
                  FROM truyen t
                  LEFT JOIN tacgia tg ON t.id_tacgia = tg.id
                  ORDER BY t.id DESC";
        
        $result = mysqli_query($this->conn, $query);
        
        if (!$result) {
            die("Lỗi truy vấn: " . mysqli_error($this->conn));
        }
        
        $truyens = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $truyens[] = $row;
        }
        
        return $truyens;
    }

    // ========== METHODS MỚI CHO USER ==========

    // Lấy danh sách truyện mà USER đã bình luận
    public function getTruyenWithUserComments($id_nguoidung) {
        $id_nguoidung = mysqli_real_escape_string($this->conn, $id_nguoidung);
        
        $query = "SELECT DISTINCT t.*, 
                         tg.ten_tacgia,
                         tg.but_danh,
                         (SELECT COUNT(*) 
                          FROM {$this->table} bl
                          INNER JOIN chuong c ON bl.id_chuong = c.id
                          WHERE c.id_truyen = t.id AND bl.id_nguoidung = '$id_nguoidung') as my_comments
                  FROM truyen t
                  LEFT JOIN tacgia tg ON t.id_tacgia = tg.id
                  INNER JOIN chuong c ON c.id_truyen = t.id
                  INNER JOIN {$this->table} bl ON bl.id_chuong = c.id
                  WHERE bl.id_nguoidung = '$id_nguoidung'
                  ORDER BY t.id DESC";
        
        $result = mysqli_query($this->conn, $query);
        
        if (!$result) {
            die("Lỗi truy vấn: " . mysqli_error($this->conn));
        }
        
        $truyens = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $truyens[] = $row;
        }
        
        return $truyens;
    }

    // Lấy thông tin chương để hiển thị
    public function getChuongInfo($id_chuong) {
        $id_chuong = mysqli_real_escape_string($this->conn, $id_chuong);
        
        $query = "SELECT c.*, t.ten_truyen
                  FROM chuong c
                  INNER JOIN truyen t ON c.id_truyen = t.id
                  WHERE c.id = '$id_chuong'";
        
        $result = mysqli_query($this->conn, $query);
        
        if (!$result) {
            die("Lỗi truy vấn: " . mysqli_error($this->conn));
        }
        
        return mysqli_fetch_assoc($result);
    }
}
?>