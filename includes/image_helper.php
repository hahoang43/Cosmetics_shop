<?php
function imageSrc(?string $thumbnail, string $folder = 'products'): string {
    $thumbnail = trim((string)$thumbnail);
    if ($thumbnail === '') {
        return noImageSrc();
    }

    if (filter_var($thumbnail, FILTER_VALIDATE_URL)) {
        return '/Cosmetics_shop/backend/image_proxy.php?url=' . urlencode($thumbnail);
    }

    return '/Cosmetics_shop/assets/uploads/' . $folder . '/' . ltrim($thumbnail, '/');
}

function noImageSrc(string $label = 'No Image'): string {
    $label = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 120 120"><rect width="120" height="120" rx="10" fill="#f3f4f6"/><rect x="18" y="18" width="84" height="84" rx="8" fill="#ffffff" stroke="#d1d5db" stroke-width="2"/><path d="M35 76l16-18 12 14 10-12 12 16" fill="none" stroke="#cbd5e1" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/><circle cx="48" cy="48" r="6" fill="#cbd5e1"/><text x="60" y="104" text-anchor="middle" font-family="Arial, sans-serif" font-size="12" fill="#6b7280">' . $label . '</text></svg>';
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}
