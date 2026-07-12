$(document).ready(function() {
    // Chuyển đổi giữa các Form
    $('#show-register').click(function() {
        $('#login-form').hide();
        $('#register-form').fadeIn();
    });

    $('#back-login1, #back-login2').click(function() {
        $('#register-form, #forgot-form').hide();
        $('#login-form').fadeIn();
    });

    $('#show-forgot').click(function() {
        $('#login-form').hide();
        $('#forgot-form').fadeIn();
    });

    // XỬ LÝ ĐĂNG KÝ
    $('#register-form').submit(function(e) {
        e.preventDefault();
        let password = $('#register-password').val();
        let confirm = $('#register-password2').val();

        if (password !== confirm) {
            alert('Mật khẩu nhập lại không khớp!');
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
            success: function(response) {
                let res = JSON.parse(response);
                alert(res.message);
                if (res.status === 'success') {
                    location.reload(); // Reload để hiện tên User sau khi đăng ký
                }
            }
        });
    });

    // XỬ LÝ ĐĂNG NHẬP
    $('#login-form').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: '/Cosmetics_shop/backend/auth_process.php',
            type: 'POST',
            data: {
                action: 'login',
                email: $('#login-email').val(),
                password: $('#login-password').val()
            },
            success: function(response) {
                let res = JSON.parse(response);
                if (res.status === 'success') {
                    window.location.href = '/Cosmetics_shop/frontend/index.php'; // Chuyển về trang chủ
                } else {
                    alert(res.message);
                }
            }
        });
    });
});