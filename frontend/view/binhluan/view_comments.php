<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Xem bình luận</title>

    <link rel="stylesheet" href="../public/css/admin.css">
    <link rel="stylesheet" href="../public/css/binhluan.css">
</head>
<body>

<h1 class="form-title" id="title">Đang tải...</h1>

<div class="top-bar">
    <a href="list_binhluan.html" class="btn-action">← Quay lại</a>
    <a id="btn-add" class="btn-action">✍️ Viết bình luận</a>
</div>

<div id="api-message"></div>

<!-- INFO TRUYỆN -->
<div class="card" id="truyen-info"></div>

<!-- DANH SÁCH -->
<div class="card">
    <h2>📝 Danh sách bình luận</h2>
    <div id="comment-list"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async function(){

    const params = new URLSearchParams(window.location.search);
    const id_truyen = params.get('id_truyen');

    const title = document.getElementById('title');
    const infoBox = document.getElementById('truyen-info');
    const listBox = document.getElementById('comment-list');
    const btnAdd = document.getElementById('btn-add');

    if(!id_truyen){
        listBox.innerHTML = 'Thiếu id_truyen';
        return;
    }

    btnAdd.href = `add_binhluan.html?id_truyen=${id_truyen}`;

    try{
        const res = await fetch(`../../../backend/api/binhluan/get_comments_by_truyen.php?id_truyen=${id_truyen}`);
        const json = await res.json();

        const truyen = json.truyen;
        const comments = json.comments;
        const currentUser = json.current_user; // 👈 API phải trả về

        // ===== TRUYỆN =====
        title.textContent = `Bình luận: ${truyen.ten_truyen}`;

        infoBox.innerHTML = `
            <div class="truyen-box">
                <img src="${truyen.anh_bia}" class="cover-img-lg">
                <div>
                    <h2>${truyen.ten_truyen}</h2>
                    <p>${truyen.mo_ta}</p>
                    <p class="comment-count">💬 ${comments.length}</p>
                </div>
            </div>
        `;

        // ===== COMMENTS =====
        if(comments.length === 0){
            listBox.innerHTML = `
                <div class="empty">
                    💬 Chưa có bình luận
                </div>
            `;
            return;
        }

        listBox.innerHTML = '';

        comments.forEach(c => {

            const isOwner = c.id_nguoidung == currentUser.id;
            const isAdmin = currentUser.vai_tro === 'admin';

            let actions = '';

            if(isOwner){
                actions += `<a href="edit_binhluan.html?id=${c.id}" class="btn-action">✏️ Sửa</a>`;
            }

            if(isOwner || isAdmin){
                actions += `<button class="btn-action btn-delete" onclick="deleteComment(${c.id})">🗑️ Xóa</button>`;
            }

            const highlight = isOwner ? 'my-comment' : '';

            const html = `
                <div class="comment-box ${highlight}">
                    <div class="comment-header">
                        <span>
                            👤 ${c.ten_dang_nhap}
                            ${isOwner ? '<span class="me">(Bạn)</span>' : ''}
                        </span>
                        <span>🕒 ${c.ngay_tao}</span>
                    </div>

                    <div class="comment-meta">
                        📍 Chương ${c.so_chuong} - ${c.tieu_de_chuong || ''}
                    </div>

                    <div class="comment-content">
                        ${c.noi_dung}
                    </div>

                    <div class="comment-actions">
                        ${actions}
                    </div>
                </div>
            `;

            listBox.innerHTML += html;
        });

    }catch(err){
        listBox.innerHTML = 'Lỗi tải dữ liệu';
    }

});

// ===== DELETE =====
async function deleteComment(id){
    if(!confirm('Bạn chắc chắn muốn xóa?')) return;

    try{
        const res = await fetch('../../../backend/api/binhluan/delete.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({id})
        });

        const json = await res.json();

        if(json.success){
            location.reload();
        }else{
            alert('Xóa thất bại');
        }

    }catch(err){
        alert('Lỗi kết nối');
    }
}
</script>

</body>
</html>