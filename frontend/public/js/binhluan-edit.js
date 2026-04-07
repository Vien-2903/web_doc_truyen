// ========================================
// Edit Comment Form Logic
// ========================================

async function initEditCommentForm() {
    const msgBox = document.getElementById('api-message');
    const form = document.getElementById('form-edit');

    if (!msgBox || !form) {
        console.error('Missing form elements');
        return;
    }

    // Lấy id từ URL (?id=...)
    const params = new URLSearchParams(window.location.search);
    const id = params.get('id');

    if (!id) {
        msgBox.style.color = 'red';
        msgBox.textContent = 'Thiếu ID bình luận';
        return;
    }

    // Load dữ liệu bình luận
    async function loadComment() {
        try {
            const res = await fetch(`/web_doc_truyen/backend/api/binhluan/get_one.php?id=${id}`);
            const json = await res.json();

            if (json.success) {
                const c = json.data;

                document.getElementById('ten_dang_nhap').value = c.ten_dang_nhap;
                document.getElementById('ngay_tao').value = c.ngay_tao;
                document.getElementById('noi_dung').value = c.noi_dung;

            } else {
                msgBox.style.color = 'red';
                msgBox.textContent = 'Không tìm thấy bình luận';
            }

        } catch (err) {
            msgBox.style.color = 'red';
            msgBox.textContent = 'Lỗi tải dữ liệu';
        }
    }

    loadComment();

    // Submit cập nhật
    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        msgBox.textContent = '';

        const submitBtn = form.querySelector('button');
        submitBtn.disabled = true;

        const fd = new FormData(form);
        fd.append('id', id);

        try {
            const res = await fetch('/web_doc_truyen/backend/api/binhluan/update_binhluan_api.php', {
                method: 'POST',
                body: fd
            });

            const json = await res.json();

            if (res.ok && json.success) {
                msgBox.style.color = 'green';
                msgBox.textContent = json.message || 'Cập nhật thành công';

                setTimeout(() => {
                    history.back();
                }, 800);

            } else {
                msgBox.style.color = 'red';
                msgBox.textContent = json.message || 'Lỗi cập nhật';
            }

        } catch (err) {
            msgBox.style.color = 'red';
            msgBox.textContent = 'Lỗi kết nối: ' + err.message;
        }

        submitBtn.disabled = false;
    });
}

document.addEventListener('DOMContentLoaded', initEditCommentForm);
