<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm Bình Luận</title>

    <link rel="stylesheet" href="../public/css/admin.css">
    <link rel="stylesheet" href="../public/css/binhluan.css">
</head>
<body>

<a href="index.php?page=admin&controller=binhluan" class="btn-home">← Quay lại</a>

<h1 class="form-title">Viết bình luận</h1>

<div id="api-message"></div>

<div class="user-form">
    <form id="form-binhluan">
        <table>
            <tr>
                <td>Chương: <span class="required">*</span></td>
                <td>
                    <select name="id_chuong" id="select-chuong" required>
                        <option value="">-- Đang tải chương... --</option>
                    </select>
                </td>
            </tr>

            <tr>
                <td>Nội dung: <span class="required">*</span></td>
                <td>
                    <textarea name="noi_dung" rows="8" placeholder="Nhập bình luận..." required></textarea>
                </td>
            </tr>
        </table>

        <div class="form-actions">
            <button type="submit">Gửi bình luận</button>
            <a href="index.php?page=admin&controller=binhluan" class="btn-back">Hủy</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){

    const msgBox = document.getElementById('api-message');
    const selectChuong = document.getElementById('select-chuong');
    const form = document.getElementById('form-binhluan');

    // ⚡ Load danh sách chương
    async function loadChuong(){
        try{
            const res = await fetch('../../../backend/api/chuong/get_chuong.php');
            const json = await res.json();

            const list = json.data || [];

            selectChuong.innerHTML = '<option value="">-- Chọn chương --</option>';

            list.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = `Chương ${c.so_chuong} - ${c.tieu_de || ''}`;
                selectChuong.appendChild(opt);
            });

        }catch(err){
            msgBox.style.color = 'red';
            msgBox.textContent = 'Không tải được danh sách chương';
        }
    }

    loadChuong();

    // ⚡ Submit form
    form.addEventListener('submit', async function(e){
        e.preventDefault();

        msgBox.textContent = '';

        const submitBtn = form.querySelector('button');
        submitBtn.disabled = true;

        const fd = new FormData(form);

        try{
            const res = await fetch('../../../backend/api/binhluan/add_binhluan_api.php', {
                method: 'POST',
                body: fd
            });

            const json = await res.json();

            if(res.ok && json.success){
                msgBox.style.color = 'green';
                msgBox.textContent = json.message || 'Thêm bình luận thành công';

                setTimeout(()=>{
                    window.location.href = 'index.php?page=admin&controller=binhluan';
                }, 800);

            }else{
                msgBox.style.color = 'red';
                msgBox.textContent = json.message || 'Lỗi khi thêm';
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