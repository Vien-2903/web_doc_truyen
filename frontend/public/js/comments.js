(function () {
    const state = {
        truyenId: 0,
        truyenName: '',
        currentUser: {
            id: 0,
            vai_tro: ''
        },
        comments: [],
        editingCommentId: 0
    };

    let mounted = false;
    let loading = false;

    function getNode(id) {
        return document.getElementById(id);
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatDateTime(value) {
        if (!value) {
            return '';
        }

        const normalized = String(value).replace(' ', 'T');
        const date = new Date(normalized);

        if (Number.isNaN(date.getTime())) {
            return escapeHtml(value);
        }

        return date.toLocaleString('vi-VN');
    }

    function resolveAvatarUrl(fileName, userName) {
        if (fileName) {
            const normalized = String(fileName).trim().replace(/\\/g, '/');

            if (normalized.startsWith('http://') || normalized.startsWith('https://')) {
                return normalized;
            }

            if (normalized.startsWith('/')) {
                return normalized;
            }

            if (normalized.includes('uploads/avatar/')) {
                const fileOnly = normalized.split('uploads/avatar/').pop();
                if (fileOnly) {
                    return '/web_doc_truyen/backend/uploads/avatar/' + fileOnly;
                }
            }

            const parts = normalized.split('/').filter(Boolean);
            const fileOnly = parts.length ? parts[parts.length - 1] : '';
            if (fileOnly) {
                return '/web_doc_truyen/backend/uploads/avatar/' + fileOnly;
            }
        }

        return 'https://ui-avatars.com/api/?name=' + encodeURIComponent(userName || 'User') + '&background=3498db&color=fff&size=200';
    }

    function getCommenterTag(comment) {
        const currentId = Number(state.currentUser?.id || 0);
        const isMine = currentId > 0 && Number(comment.id_nguoidung) === currentId;
        const role = String(comment.vai_tro || '').toLowerCase();

        if (isMine) {
            return { text: 'Ban', className: 'tag-me' };
        }

        if (role === 'admin') {
            return { text: 'Admin', className: 'tag-admin' };
        }

        return { text: 'Nguoi binh luan', className: 'tag-user' };
    }

    function setFormMessage(message, isError) {
        const messageNode = getNode('comments-form-message');
        if (!messageNode) {
            return;
        }

        messageNode.textContent = message || '';
        messageNode.className = isError
            ? 'comments-form-message is-error'
            : 'comments-form-message is-success';
    }

    function renderSkeleton() {
        const header = getNode('comments-header');
        const formWrap = getNode('comments-form-wrap');
        const list = getNode('comments-list');

        if (header) {
            header.innerHTML = '<h2>💬 Bình luận</h2>';
        }

        if (formWrap) {
            formWrap.innerHTML = '<div class="comments-loading">Đang tải biểu mẫu bình luận...</div>';
        }

        if (list) {
            list.innerHTML = '<div class="comments-loading">Đang tải bình luận...</div>';
        }
    }

    function getToastHost() {
        let host = document.getElementById('comments-toast-host');
        if (host) {
            return host;
        }

        host = document.createElement('div');
        host.id = 'comments-toast-host';
        host.className = 'comments-toast-host';
        document.body.appendChild(host);
        return host;
    }

    function showToast(message, type) {
        const host = getToastHost();
        const toast = document.createElement('div');
        toast.className = 'comments-toast is-' + String(type || 'info');
        toast.textContent = message || '';
        host.appendChild(toast);

        requestAnimationFrame(function () {
            toast.classList.add('is-visible');
        });

        window.setTimeout(function () {
            toast.classList.remove('is-visible');
            window.setTimeout(function () {
                toast.remove();
            }, 220);
        }, 2200);
    }

    function canDeleteComment(comment) {
        const currentId = Number(state.currentUser?.id || 0);
        const role = String(state.currentUser?.vai_tro || '').toLowerCase();

        if (currentId <= 0) {
            return false;
        }

        return Number(comment.id_nguoidung) === currentId || role === 'admin';
    }

    function canEditComment(comment) {
        const currentId = Number(state.currentUser?.id || 0);
        if (currentId <= 0) {
            return false;
        }

        return Number(comment.id_nguoidung) === currentId;
    }

    function renderHeader() {
        const header = getNode('comments-header');
        if (!header) {
            return;
        }

        const titleSuffix = state.truyenName ? ': ' + escapeHtml(state.truyenName) : '';

        header.innerHTML = `
            <h2>💬 Bình luận${titleSuffix}</h2>
            <span class="comments-count">${state.comments.length} bình luận</span>
        `;
    }

    function renderComments() {
        const list = getNode('comments-list');
        if (!list) {
            return;
        }

        if (!Array.isArray(state.comments) || state.comments.length === 0) {
            list.innerHTML = '<div class="comments-empty">Chưa có bình luận nào. Hãy là người bình luận đầu tiên.</div>';
            return;
        }

        list.innerHTML = state.comments.map(function (comment) {
            const isEditing = Number(state.editingCommentId) === Number(comment.id);
            const editButton = canEditComment(comment)
                ? `<button type="button" class="comment-edit-btn" data-comment-edit="${Number(comment.id)}">Sửa</button>`
                : '';
            const deleteButton = canDeleteComment(comment)
                ? `<button type="button" class="comment-delete-btn" data-comment-delete="${Number(comment.id)}">Xóa</button>`
                : '';
            const actionHtml = editButton || deleteButton
                ? `<div class="comment-actions">${editButton}${deleteButton}</div>`
                : '';
            const editPanelHtml = isEditing
                ? `
                    <div class="comment-edit-panel">
                        <textarea class="comment-edit-textarea" data-comment-edit-input="${Number(comment.id)}" minlength="10">${escapeHtml(comment.noi_dung || '')}</textarea>
                        <div class="comment-actions comment-edit-actions">
                            <button type="button" class="comment-save-btn" data-comment-save="${Number(comment.id)}">Lưu</button>
                            <button type="button" class="comment-cancel-btn" data-comment-cancel="${Number(comment.id)}">Hủy</button>
                        </div>
                    </div>
                `
                : '';

            const tag = getCommenterTag(comment);
            const avatarUrl = resolveAvatarUrl(comment.avatar || '', comment.ten_dang_nhap || 'User');
            const fallbackAvatarUrl = resolveAvatarUrl('', comment.ten_dang_nhap || 'User');

            return `
                <article class="comment-card">
                    <div class="comment-top">
                        <img
                            class="comment-avatar"
                            src="${escapeHtml(avatarUrl)}"
                            alt="${escapeHtml(comment.ten_dang_nhap || 'User')}"
                            onerror="this.onerror=null;this.src='${escapeHtml(fallbackAvatarUrl)}';"
                        >
                        <div class="comment-identity">
                            <div class="comment-name-row">
                                <strong class="comment-author">${escapeHtml(comment.ten_dang_nhap || 'An danh')}</strong>
                                <span class="commenter-tag ${tag.className}">${tag.text}</span>
                            </div>
                            <span class="comment-time">${formatDateTime(comment.ngay_tao)}</span>
                        </div>
                    </div>
                    <div class="comment-meta">Chương ${escapeHtml(comment.so_chuong || '')} - ${escapeHtml(comment.tieu_de_chuong || '')}</div>
                    ${isEditing ? editPanelHtml : `<p class="comment-content">${escapeHtml(comment.noi_dung || '').replace(/\n/g, '<br>')}</p>`}
                    ${isEditing ? '' : actionHtml}
                </article>
            `;
        }).join('');

        if (state.editingCommentId > 0) {
            const editingInput = list.querySelector(`[data-comment-edit-input="${Number(state.editingCommentId)}"]`);
            if (editingInput) {
                editingInput.focus();
                editingInput.setSelectionRange(editingInput.value.length, editingInput.value.length);
                editingInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    }

    async function loadChapterOptions() {
        const select = getNode('comment-chuong-select');
        if (!select) {
            return;
        }

        try {
            const res = await fetch(`/web_doc_truyen/backend/api/chuong/get_all_chuong_api.php?id_truyen=${state.truyenId}`);
            const json = await res.json();

            const chapters = Array.isArray(json.data) ? json.data : [];
            if (!json.success || chapters.length === 0) {
                select.innerHTML = '<option value="">Truyện này chưa có chương</option>';
                select.disabled = true;
                return;
            }

            select.disabled = false;
            select.innerHTML = '<option value="">-- Chọn chương --</option>' + chapters.map(function (chapter) {
                return `<option value="${Number(chapter.id)}">Chương ${escapeHtml(chapter.so_chuong)} - ${escapeHtml(chapter.ten_chuong || '')}</option>`;
            }).join('');
        } catch (error) {
            select.innerHTML = '<option value="">Không tải được chương</option>';
            select.disabled = true;
        }
    }

    function renderForm() {
        const formWrap = getNode('comments-form-wrap');
        if (!formWrap) {
            return;
        }

        const userId = Number(state.currentUser?.id || 0);

        if (userId <= 0) {
            formWrap.innerHTML = '<div class="comments-login-hint">Bạn cần đăng nhập để gửi bình luận.</div>';
            return;
        }

        formWrap.innerHTML = `
            <form id="comments-form" class="comments-form">
                <div class="comments-field">
                    <label for="comment-chuong-select">Chương</label>
                    <select id="comment-chuong-select" name="id_chuong" required>
                        <option value="">Đang tải danh sách chương...</option>
                    </select>
                </div>
                <div class="comments-field">
                    <label for="comment-content">Nội dung bình luận</label>
                    <textarea id="comment-content" name="noi_dung" minlength="10" placeholder="Nhập tối thiểu 10 ký tự" required></textarea>
                </div>
                <div class="comments-submit-row">
                    <button type="submit" id="comment-submit-btn">Gửi bình luận</button>
                    <span id="comments-form-message" class="comments-form-message"></span>
                </div>
            </form>
        `;

        const form = getNode('comments-form');
        if (form) {
            form.addEventListener('submit', handleSubmitComment);
        }

        loadChapterOptions();
    }

    async function handleSubmitComment(event) {
        event.preventDefault();

        const form = event.currentTarget;
        const submitBtn = getNode('comment-submit-btn');
        if (!form || !submitBtn) {
            return;
        }

        setFormMessage('', false);
        submitBtn.disabled = true;

        try {
            const formData = new FormData(form);
            const res = await fetch('/web_doc_truyen/backend/api/binhluan/add_binhluan_api.php', {
                method: 'POST',
                body: formData
            });

            const json = await res.json();

            if (!res.ok || !json.success) {
                setFormMessage(json.message || 'Không thể thêm bình luận', true);
                showToast(json.message || 'Không thể thêm bình luận', 'error');
                return;
            }

            const contentInput = getNode('comment-content');
            if (contentInput) {
                contentInput.value = '';
            }

            setFormMessage(json.message || 'Thêm bình luận thành công', false);
            showToast(json.message || 'Thêm bình luận thành công', 'success');
            await loadComments();
        } catch (error) {
            setFormMessage('Lỗi kết nối, vui lòng thử lại', true);
            showToast('Lỗi kết nối, vui lòng thử lại', 'error');
        } finally {
            submitBtn.disabled = false;
        }
    }

    async function handleDeleteComment(commentId) {
        if (!commentId || !window.confirm('Bạn chắc chắn muốn xóa bình luận này?')) {
            return;
        }

        try {
            const res = await fetch('/web_doc_truyen/backend/api/binhluan/delete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: commentId })
            });

            const json = await res.json();
            if (!res.ok || !json.success) {
                showToast(json.message || 'Xóa bình luận thất bại', 'error');
                return;
            }

            showToast(json.message || 'Xóa bình luận thành công', 'success');
            await loadComments();
        } catch (error) {
            showToast('Lỗi kết nối, chưa thể xóa bình luận', 'error');
        }
    }

    async function handleEditComment(commentId) {
        if (!commentId) {
            return;
        }

        const comment = state.comments.find(function (item) {
            return Number(item.id) === Number(commentId);
        });

        if (!comment) {
            showToast('Không tìm thấy bình luận để sửa', 'error');
            return;
        }

        state.editingCommentId = Number(commentId);
        renderComments();
    }

    function cancelEditComment() {
        if (state.editingCommentId <= 0) {
            return;
        }

        state.editingCommentId = 0;
        renderComments();
        showToast('Đã hủy sửa bình luận', 'info');
    }

    async function saveEditComment(commentId) {
        if (!commentId) {
            return;
        }

        const input = document.querySelector(`[data-comment-edit-input="${Number(commentId)}"]`);
        if (!input) {
            showToast('Không tìm thấy nội dung để lưu', 'error');
            return;
        }

        const normalizedContent = String(input.value || '').trim();
        if (normalizedContent.length < 10) {
            showToast('Nội dung phải có ít nhất 10 ký tự', 'error');
            return;
        }

        input.disabled = true;

        try {
            const res = await fetch('/web_doc_truyen/backend/api/binhluan/update.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: commentId,
                    noi_dung: normalizedContent
                })
            });

            const json = await res.json();
            if (!res.ok || !json.success) {
                showToast(json.message || 'Sửa bình luận thất bại', 'error');
                input.disabled = false;
                return;
            }

            state.editingCommentId = 0;
            showToast(json.message || 'Sửa bình luận thành công', 'success');
            await loadComments();
        } catch (error) {
            input.disabled = false;
            showToast('Lỗi kết nối, chưa thể sửa bình luận', 'error');
        }
    }

    function bindCommentActions() {
        const list = getNode('comments-list');
        if (!list || list.dataset.bindDone === '1') {
            return;
        }

        list.addEventListener('click', function (event) {
            const saveButton = event.target.closest('[data-comment-save]');
            if (saveButton) {
                const saveId = Number(saveButton.getAttribute('data-comment-save'));
                if (saveId > 0) {
                    saveEditComment(saveId);
                }
                return;
            }

            const cancelButton = event.target.closest('[data-comment-cancel]');
            if (cancelButton) {
                cancelEditComment();
                return;
            }

            const editButton = event.target.closest('[data-comment-edit]');
            if (editButton) {
                const editId = Number(editButton.getAttribute('data-comment-edit'));
                if (editId > 0) {
                    handleEditComment(editId);
                }
                return;
            }

            const deleteButton = event.target.closest('[data-comment-delete]');
            if (!deleteButton) {
                return;
            }

            const commentId = Number(deleteButton.getAttribute('data-comment-delete'));
            if (commentId > 0) {
                handleDeleteComment(commentId);
            }
        });

        list.dataset.bindDone = '1';
    }

    async function loadComments() {
        if (loading || state.truyenId <= 0) {
            return;
        }

        loading = true;

        try {
            const res = await fetch(`/web_doc_truyen/backend/api/binhluan/get_comments_by_truyen.php?id_truyen=${state.truyenId}`);
            const json = await res.json();

            if (!res.ok || !json.success) {
                throw new Error(json.message || 'Không thể tải bình luận');
            }

            state.comments = Array.isArray(json.comments) ? json.comments : [];
            state.currentUser = json.current_user || { id: 0, vai_tro: '' };

            if (json.truyen && json.truyen.ten_truyen) {
                state.truyenName = json.truyen.ten_truyen;
            }

            renderHeader();
            renderForm();
            renderComments();
            bindCommentActions();
        } catch (error) {
            const list = getNode('comments-list');
            if (list) {
                list.innerHTML = `<div class="comments-error">${escapeHtml(error.message || 'Không thể tải bình luận')}</div>`;
            }

            renderHeader();
            renderForm();
        } finally {
            loading = false;
        }
    }

    function init(payload) {
        const host = getNode('comments-host');
        if (!host) {
            return;
        }

        const incoming = payload || {};
        const urlId = Number(new URLSearchParams(window.location.search).get('id') || 0);
        const nextTruyenId = Number(incoming.truyenId || host.dataset.truyenId || urlId);

        if (nextTruyenId <= 0) {
            return;
        }

        const changedStory = state.truyenId !== nextTruyenId;
        state.truyenId = nextTruyenId;
        state.truyenName = String(incoming.truyenName || host.dataset.truyenName || state.truyenName || '');

        if (!mounted || changedStory) {
            renderSkeleton();
            loadComments();
        }

        mounted = true;
    }

    window.CommentsModule = {
        init: init
    };

    document.addEventListener('truyen:detail-ready', function (event) {
        init((event && event.detail) || {});
    });

    document.addEventListener('DOMContentLoaded', function () {
        init({});
    });
})();
