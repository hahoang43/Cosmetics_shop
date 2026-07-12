<?php

if (!function_exists('popup_assets')) {
    function popup_assets(): string
    {
        return <<<HTML
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    .swal2-popup.swal-lumina-popup {
        border-radius: 24px !important;
        padding: 1.75rem 1.5rem 1.5rem !important;
        box-shadow: 0 30px 70px rgba(15, 23, 42, 0.18) !important;
    }

    .swal2-title.swal-lumina-title {
        font-weight: 700 !important;
        font-size: 1.5rem !important;
        letter-spacing: 0.2px;
        color: #1f2937 !important;
    }

    .swal2-html-container.swal-lumina-text,
    .swal2-content.swal-lumina-text {
        color: #4b5563 !important;
        font-size: 1rem !important;
        line-height: 1.6 !important;
    }

    .swal2-confirm.swal-lumina-button {
        border-radius: 999px !important;
        padding: 0.75rem 1.5rem !important;
        background: linear-gradient(135deg, #d4a373, #b86e3c) !important;
        box-shadow: 0 10px 22px rgba(212, 163, 115, 0.3) !important;
    }

    .swal2-cancel.swal-lumina-cancel {
        border-radius: 999px !important;
        background: #eef2f7 !important;
        color: #334155 !important;
    }
</style>
HTML;
    }
}

if (!function_exists('popup_notify')) {
    function popup_notify(string $icon, string $title, string $text, array $options = []): void
    {
        $titleJson = json_encode($title, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $textJson = json_encode($text, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $confirmButtonText = json_encode($options['confirmButtonText'] ?? 'Đóng', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $cancelButtonText = json_encode($options['cancelButtonText'] ?? 'Hủy', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $timer = isset($options['timer']) ? (int) $options['timer'] : 2200;
        $showConfirmButton = array_key_exists('showConfirmButton', $options)
            ? ($options['showConfirmButton'] ? 'true' : 'false')
            : 'true';
        $showCancelButton = !empty($options['showCancelButton']) ? 'true' : 'false';
        $redirectJson = array_key_exists('redirect', $options)
            ? json_encode($options['redirect'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : 'null';
        $backAction = !empty($options['back']) ? 'true' : 'false';
        $withAssets = !empty($options['withAssets']);
        $timerProgressBar = !empty($options['timerProgressBar']) || ($redirectJson !== 'null' && $timer > 0) ? 'true' : 'false';

        echo ($withAssets ? popup_assets() : '') . <<<HTML
<script>
document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
        icon: '{$icon}',
        title: {$titleJson},
        text: {$textJson},
        confirmButtonText: {$confirmButtonText},
        showConfirmButton: {$showConfirmButton},
        showCancelButton: {$showCancelButton},
        cancelButtonText: {$cancelButtonText},
        confirmButtonColor: '#d4a373',
        cancelButtonColor: '#94a3b8',
        background: '#ffffff',
        iconColor: '#d4a373',
        timer: {$timer},
        timerProgressBar: {$timerProgressBar},
        customClass: {
            popup: 'swal-lumina-popup',
            title: 'swal-lumina-title',
            htmlContainer: 'swal-lumina-text',
            confirmButton: 'swal-lumina-button',
            cancelButton: 'swal-lumina-cancel'
        }
    }).then(function (result) {
        if ({$backAction} && result.isConfirmed) {
            window.history.back();
        }
HTML;

        if ($redirectJson !== 'null') {
            echo "        window.location.href = {$redirectJson};\n";
        }

        echo <<<HTML
    });
});
</script>
HTML;
    }
}

if (!function_exists('popup_success')) {
    function popup_success(string $title, string $text, ?string $redirect = null, int $timer = 1800): void
    {
        popup_notify('success', $title, $text, [
            'withAssets' => true,
            'showConfirmButton' => $redirect === null,
            'timer' => $redirect ? $timer : 0,
            'timerProgressBar' => $redirect !== null,
            'redirect' => $redirect,
            'confirmButtonText' => 'OK',
        ]);
    }
}

if (!function_exists('popup_error')) {
    function popup_error(string $title, string $text, bool $back = false): void
    {
        popup_notify('error', $title, $text, [
            'withAssets' => true,
            'showConfirmButton' => true,
            'timer' => 0,
            'back' => $back,
            'confirmButtonText' => 'Đóng',
        ]);
    }
}

if (!function_exists('popup_warning')) {
    function popup_warning(string $title, string $text, ?string $redirect = null, int $timer = 2200): void
    {
        popup_notify('warning', $title, $text, [
            'withAssets' => true,
            'showConfirmButton' => $redirect === null,
            'timer' => $redirect ? $timer : 0,
            'timerProgressBar' => $redirect !== null,
            'redirect' => $redirect,
            'confirmButtonText' => 'OK',
        ]);
    }
}