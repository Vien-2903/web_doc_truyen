// ========================================
// Favorites / Heart System Module
// ========================================

const favoriteStoryIds = new Set();

function getLoggedInUser() {
    try {
        return JSON.parse(localStorage.getItem('user') || 'null');
    } catch (error) {
        return null;
    }
}

async function loadFavoriteIds() {
    const user = getLoggedInUser();
    if (!user || !user.id) {
        return;
    }

    favoriteStoryIds.clear();

    try {
        const response = await fetch('/web_doc_truyen/backend/api/yeuthich/get_by_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id_nguoidung: user.id })
        });

        const data = await response.json();
        if (response.ok && data.success && Array.isArray(data.data)) {
            data.data.forEach((item) => {
                const idValue = Number(item.id || item.id_truyen || 0);
                if (idValue) {
                    favoriteStoryIds.add(idValue);
                }
            });
            localStorage.setItem('favoriteStoryIds', JSON.stringify(Array.from(favoriteStoryIds)));
        }
    } catch (error) {
        console.warn('Không tải được danh sách yêu thích:', error);
    }
}

function updateHeartButton(button, isLiked) {
    button.classList.toggle('liked', isLiked);
    button.classList.toggle('not-liked', !isLiked);
    button.title = isLiked ? 'Bỏ yêu thích' : 'Thêm vào yêu thích';
    button.setAttribute('aria-label', isLiked ? 'Đã yêu thích' : 'Thêm vào yêu thích');
}

function bindHeartCards() {
    document.querySelectorAll('.heart-card-overlay').forEach((button) => {
        button.addEventListener('click', async (event) => {
            event.preventDefault();
            event.stopPropagation();

            const storyId = Number(button.dataset.storyId || 0);
            const user = getLoggedInUser();
            if (!user || !user.id) {
                window.alert('Bạn cần đăng nhập để thích truyện.');
                return;
            }

            button.disabled = true;

            try {
                const response = await fetch('/web_doc_truyen/backend/api/yeuthich/toggle.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id_nguoidung: user.id,
                        id_truyen: storyId
                    })
                });

                const data = await response.json();
                if (response.ok && data.success) {
                    const nowLiked = Boolean(data.is_liked);
                    
                    // Cập nhật favoriteStoryIds từ danh sách FAVORITES mới từ backend
                    favoriteStoryIds.clear();
                    if (Array.isArray(data.favorites)) {
                        data.favorites.forEach((item) => {
                            const id = Number(item.id || item.id_truyen || 0);
                            if (id) favoriteStoryIds.add(id);
                        });
                    }
                    localStorage.setItem('favoriteStoryIds', JSON.stringify(Array.from(favoriteStoryIds)));
                    localStorage.setItem('favoriteListUpdatedAt', String(Date.now()));

                    // Cập nhật button
                    updateHeartButton(button, nowLiked);
                    
                    // Gửi sự kiện cho trang danh sách yêu thích (nếu đang mở) để nó cập nhật
                    window.dispatchEvent(new CustomEvent('favoritesUpdated', {
                        detail: {
                            action: data.action,
                            storyId: storyId,
                            favorites: data.favorites,
                            message: data.message
                        }
                    }));
                } else {
                    console.warn('Không thể cập nhật yêu thích:', data.message || response.statusText);
                    window.alert(data.message || 'Lỗi khi thay đổi trạng thái yêu thích.');
                }
            } catch (error) {
                console.error('Lỗi khi toggle yêu thích:', error);
                window.alert('Lỗi mạng khi thay đổi yêu thích.');
            } finally {
                button.disabled = false;
            }
        });
    });
}

function addHeartCardOverlays() {
    document.querySelectorAll('.story-card').forEach((card) => {
        const storyId = card.dataset.storyId || 0;
        if (!storyId) return;

        const isLiked = favoriteStoryIds.has(Number(storyId));
        const buttonClasses = isLiked ? 'heart-card-overlay liked' : 'heart-card-overlay not-liked';

        const heartBtn = document.createElement('button');
        heartBtn.type = 'button';
        heartBtn.className = buttonClasses;
        heartBtn.dataset.storyId = storyId;
        heartBtn.setAttribute('aria-label', isLiked ? 'Đã yêu thích' : 'Thêm vào yêu thích');
        heartBtn.innerHTML = '<i class="fa-solid fa-heart"></i>';
        heartBtn.title = isLiked ? 'Bỏ yêu thích' : 'Thêm vào yêu thích';

        card.appendChild(heartBtn);
    });

    bindHeartCards();
}

async function initializeFavorites(stories) {
    await loadFavoriteIds();
    addHeartCardOverlays();
}
