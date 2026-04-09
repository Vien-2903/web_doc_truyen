<?php
class Database {
    private $host = "localhost";
    private $port = 3306;
    private $username = "root";
    private $password = "";
    private $database = "doc_truyen_web";
    private $conn = null;

    // Kết nối MySQLi
    public function connect() {
        //Kiểm tra xem đã kết nối chưa
        if ($this->conn == null) {
            //Tạo kết nối MySQLi
            $this->conn = mysqli_connect(
                $this->host,
                $this->username,
                $this->password,
                $this->database,
                $this->port
            );
            //Xử lý nếu kết nối thất bại
            if (!$this->conn) {
                die("Kết nối thất bại: " . mysqli_connect_error());
            }

            mysqli_set_charset($this->conn, "utf8");
        }
        return $this->conn;
    }

    // Đóng kết nối
    public function close() {
        if ($this->conn) {
            mysqli_close($this->conn);
            $this->conn = null;
        }
    }
}
?>