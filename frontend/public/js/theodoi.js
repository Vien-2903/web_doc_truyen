const followedStoryIds = new Set();

function ensureToastContainer() {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    return container;
}

function showToast(message, type = 'success') {
    const container = ensureToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    container.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('show');
    }, 10);

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 260);
    }, 2200);
}

function getLoggedInUser() {
    try {
        return JSON.parse(localStorage.getItem('user') || 'null');
    } catch (error) {
        return null;
    }
}

async function loadFollowedIds() {
    const user = getLoggedInUser();
    if (!user || !user.id) {
        return;
    }

    followedStoryIds.clear();

    try {
        const response = await fetch('/web_doc_truyen/backend/api/theo_doi_truyen/get_by_user_theodoi_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_nguoidung: user.id })
        });

        const data = await response.json();
        if (response.ok && data.success && Array.isArray(data.data)) {
            data.data.forEach((item) => {
                const idValue = Number(item.id || item.id_truyen || 0);
                if (idValue) {
                    followedStoryIds.add(idValue);
                }
            });
            localStorage.setItem('followedStoryIds', JSON.stringify(Array.from(followedStoryIds)));
        }
    } catch (error) {
        console.warn('Không tải được danh sách theo dõi:', error);
    }
}

function updateFollowButton(button, isFollowing) {
    button.classList.toggle('is-following', isFollowing);
    button.classList.toggle('not-following', !isFollowing);
    button.textContent = isFollowing ? 'Đang theo dõi' : 'Theo dõi';
    button.title = isFollowing ? 'Bỏ theo dõi truyện này' : 'Theo dõi truyện này';
    button.setAttribute('aria-label', button.title);
}

function bindFollowButtons() {
    document.querySelectorAll('.follow-card-button').forEach((button) => {
        button.addEventListener('click', async (event) => {
            event.preventDefault();
            event.stopPropagation();

            const storyId = Number(button.dataset.storyId || 0);
            const user = getLoggedInUser();
            if (!user || !user.id) {
                window.alert('Bạn cần đăng nhập để theo dõi truyện.');
                return;
            }

            const isFollowingNow = button.classList.contains('is-following');
            const confirmed = window.confirm(
                isFollowingNow
                    ? 'Bạn có đồng ý bỏ theo dõi truyện này không?'
                    : 'Bạn có đồng ý theo dõi truyện này không?'
            );
            if (!confirmed) {
                return;
            }

            button.disabled = true;

            try {
                const response = await fetch('/web_doc_truyen/backend/api/theo_doi_truyen/toggle_theodoi_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id_nguoidung: user.id, id_truyen: storyId })
                });

                const data = await response.json();
                if (response.ok && data.success) {
                    const nowFollowing = Boolean(data.is_following);

                    followedStoryIds.clear();
                    if (Array.isArray(data.following)) {
                        data.following.forEach((item) => {
                            const id = Number(item.id || item.id_truyen || 0);
                            if (id) followedStoryIds.add(id);
                        });
                    }

                    localStorage.setItem('followedStoryIds', JSON.stringify(Array.from(followedStoryIds)));
                    localStorage.setItem('followListUpdatedAt', String(Date.now()));
                    updateFollowButton(button, nowFollowing);
                    showToast(nowFollowing ? 'Đã theo dõi truyện.' : 'Đã bỏ theo dõi truyện.', 'success');

                    window.dispatchEvent(new CustomEvent('followingUpdated', {
                        detail: {
                            action: data.action,
                            storyId,
                            following: data.following,
                            message: data.message
                        }
                    }));
                } else {
                    showToast(data.message || 'Không thể thay đổi trạng thái theo dõi.', 'error');
                }
            } catch (error) {
                console.error('Lỗi khi toggle theo dõi:', error);
                showToast('Lỗi mạng khi thay đổi theo dõi.', 'error');
            } finally {
                button.disabled = false;
            }
        });
    });
}

function addFollowButtons() {
    document.querySelectorAll('.story-card').forEach((card) => {
        const storyId = card.dataset.storyId || 0;
        if (!storyId) return;

        const isFollowing = followedStoryIds.has(Number(storyId));
        const followBtn = document.createElement('button');

        followBtn.type = 'button';
        followBtn.className = 'follow-card-button';
        followBtn.dataset.storyId = storyId;

        updateFollowButton(followBtn, isFollowing);
        card.appendChild(followBtn);
    });

    bindFollowButtons();
}

async function initializeTheoDoiTruyen() {
    await loadFollowedIds();
    addFollowButtons();
}
