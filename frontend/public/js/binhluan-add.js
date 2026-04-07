// ========================================
// Add Comment Form Logic
// ========================================

async function initAddCommentForm() {
    const msgBox = document.getElementById('api-message');
    const selectChuong = document.getElementById('select-chuong');
    const form = document.getElementById('form-binhluan');

    if (!msgBox || !selectChuong || !form) {
        console.error('Missing form elements');
        return;
    }

    // Load danh sách chương
    async function loadChuong() {
        try {
            const res = await fetch('/web_doc_truyen/backend/api/chuong/get_chuong.php');
            const json = await res.json();

            const list = json.data || [];

            selectChuong.innerHTML = '<option value="">-- Chọn chương --</option>';

            list.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = `Chương ${c.so_chuong} - ${c.tieu_de || ''}`;
                selectChuong.appendChild(opt);
            });

        } catch (err) {
            msgBox.style.color = 'red';
            msgBox.textContent = 'Không tải được danh sách chương';
        }
    }

    loadChuong();

    // Submit form
    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        msgBox.textContent = '';

        const submitBtn = form.querySelector('button');
        submitBtn.disabled = true;

        const fd = new FormData(form);

        try {
            const res = await fetch('/web_doc_truyen/backend/api/binhluan/add_binhluan_api.php', {
                method: 'POST',
                body: fd
            });

            const json = await res.json();

            if (res.ok && json.success) {
                msgBox.style.color = 'green';
                msgBox.textContent = json.message || 'Thêm bình luận thành công';

                setTimeout(() => {
                    window.location.href = 'list_truyen.html';
                }, 800);

            } else {
                msgBox.style.color = 'red';
                msgBox.textContent = json.message || 'Lỗi khi thêm';
            }

        } catch (err) {
            msgBox.style.color = 'red';
            msgBox.textContent = 'Lỗi kết nối: ' + err.message;
        }

        submitBtn.disabled = false;
    });
}

document.addEventListener('DOMContentLoaded', initAddCommentForm);
