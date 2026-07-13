$(document).ready(function () {
    function showAuthAlert(icon, title, text) {
        if (typeof Swal !== 'undefined') {
            return Swal.fire({
                icon: icon,
                title: title,
                text: text,
                confirmButtonColor: '#D4A373'
            });
        }

        alert(text || title);
        return Promise.resolve({ isConfirmed: true });
    }

    function parseJsonResponse(response) {
        try {
            return typeof response === 'string' ? JSON.parse(response) : response;
        } catch (error) {
            return { status: 'error', message: 'Lỗi phản hồi từ máy chủ.' };
        }
    }

    // Chuyển đổi giữa các Form
    $('#show-register').click(function () {
        $('#login-form').hide();
        $('#register-form').fadeIn();
    });

    $('#back-login1, #back-login2').click(function () {
        $('#register-form, #forgot-form').hide();
        $('#login-form').fadeIn();
    });

    $('#show-forgot').click(function () {
        $('#login-form').hide();
        $('#forgot-form').fadeIn();
    });

    // XỬ LÝ ĐĂNG KÝ
    $('#register-form').submit(function (e) {
        e.preventDefault();
        let password = $('#register-password').val();
        let confirm = $('#register-password2').val();

        if (password !== confirm) {
            showAuthAlert('warning', 'Mật khẩu không khớp', 'Mật khẩu nhập lại không khớp!');
            return;
        }

        $.ajax({
            url: '/Cosmetics_shop/backend/auth_process.php',
            type: 'POST',
            data: {
                action: 'register',
                fullname: $('#register-fullname').val(),
                email: $('#register-email').val(),
                password: password
            },
            success: function (response) {
                let res = parseJsonResponse(response);
                showAuthAlert(
                    res.status === 'success' ? 'success' : 'error',
                    res.status === 'success' ? 'Thành công' : 'Lỗi',
                    res.message || 'Không thể xử lý yêu cầu.'
                );
                if (res.status === 'success') {
                    setTimeout(function () {
                        location.reload(); // Reload để hiện tên User sau khi đăng ký
                    }, 1200);
                }
            },
            error: function () {
                showAuthAlert('error', 'Lỗi kết nối', 'Không thể kết nối tới máy chủ.');
            }
        });
    });

    // XỬ LÝ ĐĂNG NHẬP
    $('#login-form').submit(function (e) {
        e.preventDefault();
        $.ajax({
            url: '/Cosmetics_shop/backend/auth_process.php',
            type: 'POST',
            data: {
                action: 'login',
                email: $('#login-email').val(),
                password: $('#login-password').val()
            },
            success: function (response) {
                let res = parseJsonResponse(response);
                if (res.status === 'success') {
                    showAuthAlert('success', 'Đăng nhập thành công', res.message || 'Đang chuyển hướng...').then(function () {
                        window.location.href = '/Cosmetics_shop/frontend/index.php'; // Chuyển về trang chủ
                    });
                } else {
                    showAuthAlert('error', 'Đăng nhập thất bại', res.message || 'Email hoặc mật khẩu không chính xác!');
                }
            },
            error: function () {
                showAuthAlert('error', 'Lỗi kết nối', 'Không thể kết nối tới máy chủ.');
            }
        });
    });
});