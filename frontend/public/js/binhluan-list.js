// ========================================
// List Stories with Comments Logic
// ========================================

async function initListTruyenComments() {
    const tbody = document.getElementById('table-body');
    const msgBox = document.getElementById('api-message');

    if (!tbody || !msgBox) {
        console.error('Missing table elements');
        return;
    }

    async function loadData() {
        try {
            const res = await fetch('/web_doc_truyen/backend/api/binhluan/get_truyen_comments.php');
            const json = await res.json();

            const list = json.data || [];

            if (list.length === 0) {
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
                            <a href="view_comments.html?id_truyen=${truyen.id}" class="btn-action">👁️ Xem</a>
                            <a href="add_form.html?id_truyen=${truyen.id}" class="btn-action">✍️ Viết</a>
                        </td>
                    </tr>
                `;

                tbody.innerHTML += row;
            });

        } catch (err) {
            msgBox.style.color = 'red';
            msgBox.textContent = 'Lỗi tải dữ liệu';
        }
    }

    loadData();
}

document.addEventListener('DOMContentLoaded', initListTruyenComments);
