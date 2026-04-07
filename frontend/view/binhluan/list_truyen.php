<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Bình luận</title>

    <link rel="stylesheet" href="../public/css/admin.css">
    <link rel="stylesheet" href="../public/css/binhluan.css">
</head>
<body>

<h1 class="form-title">Quản lý Bình luận</h1>

<div class="top-bar">
    <a href="index.php?page=user&controller=home" class="btn-action">← Quay lại</a>
</div>

<div id="api-message"></div>

<table class="table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Ảnh bìa</th>
            <th>Tên truyện</th>
            <th>Tác giả</th>
            <th>Trạng thái</th>
            <th>Số bình luận</th>
            <th>Hành động</th>
        </tr>
    </thead>
    <tbody id="table-body">
        <tr>
            <td colspan="7" style="text-align:center">Đang tải dữ liệu...</td>
        </tr>
    </tbody>
</table>

<script>
document.addEventListener('DOMContentLoaded', async function(){

    const tbody = document.getElementById('table-body');
    const msgBox = document.getElementById('api-message');

    async function loadData(){
        try{
            const res = await fetch('../../../backend/api/binhluan/get_truyen_comments.php');
            const json = await res.json();

            const list = json.data || [];

            if(list.length === 0){
                tbody.innerHTML = `<tr>
                    <td colspan="7" style="text-align:center">Không có dữ liệu</td>
                </tr>`;
                return;
            }

            tbody.innerHTML = '';

            list.forEach(truyen => {

                const status = truyen.trang_thai === 'dang_ra'
                    ? '<span class="status-badge status-active">Đang ra</span>'
                    : '<span class="status-badge status-inactive">Hoàn thành</span>';

                const row = `
                    <tr>
                        <td>${truyen.id}</td>
                        <td>
                            ${truyen.anh_bia 
                                ? `<img src="${truyen.anh_bia}" class="cover-img">`
                                : '<span class="no-image">Không có ảnh</span>'}
                        </td>
                        <td>${truyen.ten_truyen}</td>
                        <td>${truyen.but_danh || truyen.ten_tacgia}</td>
                        <td>${status}</td>
                        <td class="comment-count">💬 ${truyen.total_comments}</td>
                        <td>
                            <a href="view_binhluan.html?id_truyen=${truyen.id}" class="btn-action">👁️ Xem</a>
                            <a href="add_binhluan.html?id_truyen=${truyen.id}" class="btn-action">✍️ Viết</a>
                        </td>
                    </tr>
                `;

                tbody.innerHTML += row;
            });

        }catch(err){
            msgBox.style.color = 'red';
            msgBox.textContent = 'Lỗi tải dữ liệu';
        }
    }

    loadData();
});
</script>

</body>
</html>