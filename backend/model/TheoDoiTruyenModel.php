<?php
require_once __DIR__ . '/../database/myconnection.php';

class TheoDoiTruyenModel {
    private $conn;
    private $table = 'theo_doi_truyen';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function isFollowing($id_nguoidung, $id_truyen) {
        $id_nguoidung = mysqli_real_escape_string($this->conn, $id_nguoidung);
        $id_truyen = mysqli_real_escape_string($this->conn, $id_truyen);

        $query = "SELECT 1 FROM {$this->table}
                  WHERE id_nguoidung = '$id_nguoidung'
                  AND id_truyen = '$id_truyen'";

        $result = mysqli_query($this->conn, $query);
        if (!$result) {
            die('Loi truy van isFollowing: ' . mysqli_error($this->conn));
        }

        return mysqli_num_rows($result) > 0;
    }

    public function addFollow($id_nguoidung, $id_truyen) {
        $id_nguoidung = mysqli_real_escape_string($this->conn, $id_nguoidung);
        $id_truyen = mysqli_real_escape_string($this->conn, $id_truyen);

        if ($this->isFollowing($id_nguoidung, $id_truyen)) {
            return false;
        }

        $query = "INSERT INTO {$this->table} (id_nguoidung, id_truyen)
                  VALUES ('$id_nguoidung', '$id_truyen')";

        return mysqli_query($this->conn, $query);
    }

    public function removeFollow($id_nguoidung, $id_truyen) {
        $id_nguoidung = mysqli_real_escape_string($this->conn, $id_nguoidung);
        $id_truyen = mysqli_real_escape_string($this->conn, $id_truyen);

        $query = "DELETE FROM {$this->table}
                  WHERE id_nguoidung = '$id_nguoidung'
                  AND id_truyen = '$id_truyen'";

        return mysqli_query($this->conn, $query);
    }

    public function getFollowedTruyenByUser($id_nguoidung) {
        $id_nguoidung = mysqli_real_escape_string($this->conn, $id_nguoidung);

        $query = "SELECT t.*, tg.ten_tacgia, tg.but_danh, tdt.ngay_theo_doi
                  FROM {$this->table} tdt
                  INNER JOIN truyen t ON tdt.id_truyen = t.id
                  LEFT JOIN tacgia tg ON t.id_tacgia = tg.id
                  WHERE tdt.id_nguoidung = '$id_nguoidung'
                  ORDER BY tdt.ngay_theo_doi DESC, t.id DESC";

        $result = mysqli_query($this->conn, $query);
        if (!$result) {
            die('Loi truy van getFollowedTruyenByUser: ' . mysqli_error($this->conn));
        }

        $truyens = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $truyens[] = $row;
        }

        return $truyens;
    }

    public function getFollowedTruyenIdsByUser($id_nguoidung) {
        $id_nguoidung = mysqli_real_escape_string($this->conn, $id_nguoidung);

        $query = "SELECT id_truyen
                  FROM {$this->table}
                  WHERE id_nguoidung = '$id_nguoidung'";

        $result = mysqli_query($this->conn, $query);
        if (!$result) {
            die('Loi truy van getFollowedTruyenIdsByUser: ' . mysqli_error($this->conn));
        }

        $ids = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $ids[] = $row['id_truyen'];
        }

        return $ids;
    }

    public function countFollows($id_truyen) {
        $id_truyen = mysqli_real_escape_string($this->conn, $id_truyen);

        $query = "SELECT COUNT(*) as total
                  FROM {$this->table}
                  WHERE id_truyen = '$id_truyen'";

        $result = mysqli_query($this->conn, $query);
        if (!$result) {
            die('Loi truy van countFollows: ' . mysqli_error($this->conn));
        }

        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }

    public function countUserFollowed($id_nguoidung) {
        $id_nguoidung = mysqli_real_escape_string($this->conn, $id_nguoidung);

        $query = "SELECT COUNT(*) as total
                  FROM {$this->table}
                  WHERE id_nguoidung = '$id_nguoidung'";

        $result = mysqli_query($this->conn, $query);
        if (!$result) {
            die('Loi truy van countUserFollowed: ' . mysqli_error($this->conn));
        }

        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }
}
?>
