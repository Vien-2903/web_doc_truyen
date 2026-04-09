<?php require_once __DIR__ . '/../layouts/page_image_helper.php'; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang <?php echo $trang['so_trang']; ?></title>
    <link rel="stylesheet" href="/web_doc_truyen/frontend/public/css/user.css">
    <link rel="stylesheet" href="/web_doc_truyen/frontend/public/css/chitiettrang.css">
</head>
<body>
    <?php include __DIR__ . '/../layouts/user/header.html'; ?>
    
    <div class="container">
        <!-- Nút quay lại -->
        <div class="back-button-wrapper">
            <a href="index.php?page=danhsachtrang&id=<?php echo $chuong['id']; ?>" class="btn-back">
                ← Quay lại 
            </a>
        </div>

        <!-- Thông tin trang -->
        <div class="page-header">
            <h1 class="page-title">
                Chương <?php echo $chuong['so_chuong']; ?>: 
                <?php echo isset($chuong['tieu_de']) ? htmlspecialchars($chuong['tieu_de']) : ''; ?>
            </h1>
            <h2 class="page-number">Trang <?php echo $trang['so_trang']; ?></h2>
        </div>

        <!-- Nội dung trang -->
        <div class="page-content">
            <?php 
            // Hiển thị nội dung dựa vào loại
            if($trang['loai'] == 'text') {
                // Hiển thị văn bản
                echo '<div class="text-content">';
                echo nl2br(htmlspecialchars($trang['noi_dung']));
                echo '</div>';
                
            } elseif($trang['loai'] == 'image') {
                // Hiển thị hình ảnh
                if(!empty($trang['anh'])) {
                    $imageSrc = resolve_page_image_url($trang['anh']);
                    echo '<div class="image-wrapper">';
                    echo '<img src="' . htmlspecialchars($imageSrc) . '" alt="Trang ' . $trang['so_trang'] . '" class="page-image" onerror="this.src=\'https://via.placeholder.com/900x1300/2c3e50/ffffff?text=Image+Not+Found\'">';
                    echo '</div>';
                } elseif(!empty($trang['noi_dung'])) {
                    $imageSrc = resolve_page_image_url($trang['noi_dung']);
                    echo '<div class="image-wrapper">';
                    echo '<img src="' . htmlspecialchars($imageSrc) . '" alt="Trang ' . $trang['so_trang'] . '" class="page-image" onerror="this.src=\'https://via.placeholder.com/900x1300/2c3e50/ffffff?text=Image+Not+Found\'">';
                    echo '</div>';
                }
            }
            ?>
        </div>

        <!-- Điều hướng trang -->
        <div class="page-navigation">
            <?php if($trangTruoc): ?>
            <a href="index.php?page=doctrang&id=<?php echo $trangTruoc['id']; ?>" class="btn-nav btn-prev">
                ← Trang trước
            </a>
            <?php else: ?>
            <span class="btn-nav btn-disabled">
                ← Trang trước
            </span>
            <?php endif; ?>
            
            <?php if($trangSau): ?>
            <a href="index.php?page=doctrang&id=<?php echo $trangSau['id']; ?>" class="btn-nav btn-next">
                Trang sau →
            </a>
            <?php else: ?>
            <span class="btn-nav btn-disabled">
                Trang sau →
            </span>
            <?php endif; ?>
        </div>
        
    </div>

</body>
</html>