# Nhật Ký Thay Đổi Module Bình Luận

Ngày cập nhật: 2026-04-12

## Mục tiêu
- Tách riêng module bình luận khỏi logic trang truyện.
- Render bình luận bằng JavaScript độc lập.
- Bổ sung hiển thị avatar, tên người bình luận, thẻ người bình luận, nội dung bình luận.

## Danh sách file đã thay đổi (đầy đủ)
1. backend/api/binhluan/get_comments_by_truyen.php
2. backend/model/BinhLuanModel.php
3. frontend/view/truyen/chitiet.html
4. frontend/public/js/comments.js
5. frontend/public/css/comments.css
6. docs/comment-change-log.md

## Chi tiết thay đổi theo file

### 1) backend/api/binhluan/get_comments_by_truyen.php
- Tạo endpoint GET lấy danh sách bình luận theo id_truyen.
- Trả dữ liệu theo format API hiện có (success/message/data theo controller).

### 2) backend/model/BinhLuanModel.php
- Cập nhật query getByTruyen().
- JOIN thêm bảng hoso_nguoidung để lấy avatar.
- Lấy thêm vai_tro từ bảng nguoidung.

Dữ liệu bình luận trả về có thêm:
- ten_dang_nhap
- vai_tro
- avatar
- so_chuong
- tieu_de_chuong

### 3) frontend/view/truyen/chitiet.html
- Thêm khu vực host để render module bình luận (header/form/list).
- Nhúng file comments.css và comments.js riêng.
- Trigger event để module bình luận khởi tạo sau khi tải xong thông tin truyện.

### 4) frontend/public/js/comments.js
- Render toàn bộ danh sách bình luận bằng JS.
- Hiển thị avatar + tên + thẻ người bình luận + nội dung.
- Có fallback avatar khi không có ảnh đại diện.
- Giữ chức năng thêm bình luận (POST add_binhluan_api.php).
- Giữ chức năng xóa bình luận (owner/admin).

Thẻ người bình luận:
- Ban: bình luận của tài khoản đang đăng nhập.
- Admin: tác giả bình luận có vai_tro admin.
- Nguoi binh luan: mặc định.

### 5) frontend/public/css/comments.css
- Thêm style cho avatar, khung danh tính người bình luận và thẻ.
- Điều chỉnh bố cục card bình luận cho desktop/mobile.

### 6) docs/comment-change-log.md
- Viết lại tài liệu bằng tiếng Việt.
- Bổ sung danh sách file thay đổi đầy đủ và rõ ràng.

## Kết quả sau cập nhật
- Khu bình luận trên trang chi tiết truyện hoạt động độc lập.
- Người dùng thấy được avatar, tên, thẻ và nội dung bình luận.
- Chức năng thêm/xóa bình luận tiếp tục hoạt động theo phân quyền hiện có.

## Ghi chú
- Không sử dụng Laravel.
- Giữ hướng tiếp cận tối thiểu, tương thích với cấu trúc dự án hiện tại.
