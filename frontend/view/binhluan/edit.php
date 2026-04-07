<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa bình luận</title>

    <link rel="stylesheet" href="../public/css/admin.css">
    <link rel="stylesheet" href="../public/css/binhluan.css">
</head>
<body>

<a href="javascript:history.back()" class="btn-home">← Quay lại</a>

<h1 class="form-title">Sửa bình luận</h1>

<div id="api-message"></div>

<div class="user-form">
    <form id="form-edit">

        <div class="form-group">
            <label>Người đăng:</label>
            <input type="text" id="ten_dang_nhap" readonly class="readonly-input">
        </div>

        <div class="form-group">
            <label>Ngày tạo:</label>
            <input type="text" id="ngay_tao" readonly class="readonly-input">
        </div>

        <div class="form-group">
            <label>Nội dung bình luận: <span class="required">*</span></label>
            <textarea name="noi_dung" id="noi_dung" rows="8" required></textarea>
        </div>

        <div class="form-actions">
            <button type="submit">💾 Lưu thay đổi</button>
            <a href="javascript:history.back()" class="btn-back">← Hủy</a>
        </div>

    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){

    const msgBox = document.getElementById('api-message');
    const form = document.getElementById('form-edit');

    // ⚡ Lấy id từ URL (?id=...)
    const params = new URLSearchParams(window.location.search);
    const id = params.get('id');

    if(!id){
        msgBox.style.color = 'red';
        msgBox.textContent = 'Thiếu ID bình luận';
        return;
    }

    // ⚡ Load dữ liệu bình luận
    async function loadComment(){
        try{
            const res = await fetch(`../../../backend/api/binhluan/get_one.php?id=${id}`);
            const json = await res.json();

            if(json.success){
                const c = json.data;

                document.getElementById('ten_dang_nhap').value = c.ten_dang_nhap;
                document.getElementById('ngay_tao').value = c.ngay_tao;
                document.getElementById('noi_dung').value = c.noi_dung;

            }else{
                msgBox.style.color = 'red';
                msgBox.textContent = 'Không tìm thấy bình luận';
            }

        }catch(err){
            msgBox.style.color = 'red';
            msgBox.textContent = 'Lỗi tải dữ liệu';
        }
    }

    loadComment();

    // ⚡ Submit cập nhật
    form.addEventListener('submit', async function(e){
        e.preventDefault();

        msgBox.textContent = '';

        const submitBtn = form.querySelector('button');
        submitBtn.disabled = true;

        const fd = new FormData(form);
        fd.append('id', id);

        try{
            const res = await fetch('../../../backend/api/binhluan/update_binhluan_api.php', {
                method: 'POST',
                body: fd
            });

            const json = await res.json();

            if(res.ok && json.success){
                msgBox.style.color = 'green';
                msgBox.textContent = json.message || 'Cập nhật thành công';

                setTimeout(()=>{
                    window.location.href = 'index.php?page=admin&controller=binhluan';
                }, 800);

            }else{
                msgBox.style.color = 'red';
                msgBox.textContent = json.message || 'Lỗi cập nhật';
            }

        }catch(err){
            msgBox.style.color = 'red';
            msgBox.textContent = 'Lỗi kết nối: ' + err.message;
        }

        submitBtn.disabled = false;
    });

});
</script>

</body>
</html>