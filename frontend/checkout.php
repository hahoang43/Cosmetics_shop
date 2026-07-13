<?php
require_once '../config/database.php';
require_once '../config/shipping.php';
require_once '../includes/popup_notify.php';

// Khai báo tường minh để IDE không báo Undefined variable khi biến được tạo từ file include.
/** @var PDO|null $conn */
$conn = $conn ?? null;
if (!($conn instanceof PDO)) {
    throw new UnexpectedValueException('Kết nối cơ sở dữ liệu không hợp lệ.');
}

include_once '../includes/header.php';
echo popup_assets();

// === 1. CHẶN KHÁCH VÃNG LAI YÊU CẦU ĐĂNG NHẬP ===
if (!isset($_SESSION['user'])) {
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
    echo "<style>body { background: #fdfaf6; } .main-footer { display: none; }</style>"; 
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Cần đăng nhập để thanh toán!',
                text: 'Vui lòng đăng nhập nếu bạn đã có tài khoản, hoặc tạo tài khoản mới để Lumina tiện chăm sóc và theo dõi đơn hàng cho bạn nhé.',
                icon: 'info',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonColor: '#D4A373',
                denyButtonColor: '#2c3e50',
                cancelButtonColor: '#95a5a6',
                confirmButtonText: 'Đăng nhập',
                denyButtonText: 'Đăng ký mới',
                cancelButtonText: 'Quay lại giỏ hàng',
                allowOutsideClick: false, 
                allowEscapeKey: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'login.php';
                } else if (result.isDenied) {
                    window.location.href = 'register.php';
                } else {
                    window.location.href = 'cart.php';
                }
            });
        });
    </script>";
    exit; 
}

// 2. Kiểm tra giỏ hàng hoặc giỏ thanh toán tạm (buy now / thanh toán sản phẩm đã chọn)
$checkout_cart = !empty($_SESSION['checkout_cart']) ? $_SESSION['checkout_cart'] : [];
$source_cart = !empty($checkout_cart) ? $checkout_cart : ($_SESSION['cart'] ?? []);

if (isset($_SESSION['user']) && (empty($_SESSION['user']['address']) || empty($_SESSION['user']['phone_number']))) {
    $stmt_user = $conn->prepare("SELECT fullname, email, phone_number, address FROM User WHERE id = ? LIMIT 1");
    $stmt_user->execute([(int)$_SESSION['user']['id']]);
    $userRow = $stmt_user->fetch(PDO::FETCH_ASSOC);

    if ($userRow) {
        $_SESSION['user']['fullname'] = $userRow['fullname'] ?? ($_SESSION['user']['fullname'] ?? '');
        $_SESSION['user']['email'] = $userRow['email'] ?? ($_SESSION['user']['email'] ?? '');
        $_SESSION['user']['phone_number'] = $userRow['phone_number'] ?? ($_SESSION['user']['phone_number'] ?? '');
        $_SESSION['user']['address'] = $userRow['address'] ?? '';
    }
}

if (empty($source_cart)) {
    popup_warning('Giỏ hàng trống', 'Giỏ hàng của bạn đang trống!', 'index.php');
    exit;
}

// 3. Tính toán dữ liệu giỏ hàng (CẬP NHẬT THEO BẢNG BIẾN THỂ)
$total_price = 0;
$cart_items = [];
$ids = array_keys($source_cart);
$placeholders = str_repeat('?,', count($ids) - 1) . '?';

$sql = "
    SELECT pv.id AS pv_id, p.title, v.name AS variant_name, pv.price 
    FROM Product_Variant pv 
    JOIN Product p ON pv.product_id = p.id 
    JOIN Variant v ON pv.variant_id = v.id 
    WHERE pv.id IN ($placeholders)
";
$stmt = $conn->prepare($sql);
$stmt->execute($ids);
$products = $stmt->fetchAll();

foreach ($products as $p) {
    $qty = $source_cart[$p['pv_id']];
    $subtotal = $p['price'] * $qty;
    $total_price += $subtotal;
    $cart_items[] = [
        'pv_id' => $p['pv_id'],
        'title' => $p['title'],
        'variant_name' => $p['variant_name'],
        'price' => $p['price'],
        'qty' => $qty,
        'subtotal' => $subtotal
    ];
}
?>

<main class="container checkout-container" style="margin-top: 40px; margin-bottom: 60px;">
    <h2 style="font-family: 'Playfair Display', serif; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 30px;">
        Thanh toán đơn hàng
    </h2>

    <form class="checkout-form" id="checkout-form" style="display: grid; grid-template-columns: 1.6fr 1fr; gap: 40px; align-items: start;">
        
        <div class="billing-details">
    <h3 style="margin-bottom: 20px; font-size: 20px;">Thông tin giao hàng</h3>
    
    <input type="text" id="fullname" placeholder="Họ và tên" required value="<?= htmlspecialchars($_SESSION['user']['fullname'] ?? '') ?>" style="width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 5px;">
    
    <input type="tel" id="phone" placeholder="Số điện thoại" required value="<?= htmlspecialchars($_SESSION['user']['phone_number'] ?? '') ?>" style="width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 5px;">
    
    <input type="email" id="email" placeholder="Email nhận hóa đơn" value="<?= htmlspecialchars($_SESSION['user']['email'] ?? '') ?>" style="width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 5px;">

    <div style="position: relative; margin-bottom: 12px;">
        <input type="text" id="address-search" placeholder="Tìm địa chỉ giao hàng (ví dụ: 227 Nguyễn Văn Cừ, Quận 5)" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px;" autocomplete="off" value="<?= htmlspecialchars($_SESSION['user']['address'] ?? '') ?>">
        <div id="address-suggestions" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #dbe4ef; border-top: none; max-height: 240px; overflow-y: auto; z-index: 1000;"></div>
    </div>

    <button type="button" id="btn-open-map" style="width: 100%; background: #f8fafc; border: 1px solid #dbe4ef; border-radius: 8px; padding: 14px; margin-bottom: 15px; color: #334155; font-weight: 500; cursor: pointer; transition: 0.3s;">
        <i class="fa-solid fa-map-location-dot"></i> Chọn vị trí giao hàng trên bản đồ
    </button>
    <p id="map-address-preview" style="margin: 0 0 15px 0; color: #475569; font-size: 14px; padding: 8px; background: #f9fafb; border-radius: 5px;">
        Chưa chọn vị trí giao hàng.
    </p>
    <p id="distance-preview" style="margin: 0 0 15px 0; color: #64748b; font-size: 13px; padding: 8px; background: #f9fafb; border-radius: 5px;">
        Khoảng cách tạm tính: -- km
    </p>

    <input type="hidden" id="customer-lat" value="">
    <input type="hidden" id="customer-lng" value="">
    <input type="hidden" id="map-address" value="">
    
    <textarea id="note" placeholder="Ghi chú đơn hàng (ví dụ: giao giờ hành chính)" style="width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 5px; height: 60px;"></textarea>

            <h3 class="checkout-title">Phương thức thanh toán</h3>
            <div class="payment-wrapper">
                <label class="payment-item">
                    <input type="radio" name="payment_method" value="cod" checked>
                    <div class="payment-content">
                        <div class="payment-icon"><i class="fa-solid fa-truck-fast"></i></div>
                        <div class="payment-text">
                            <strong>Thanh toán khi nhận hàng (COD)</strong>
                            <span>Bạn sẽ thanh toán bằng tiền mặt khi shipper giao hàng.</span>
                        </div>
                    </div>
                </label>

                <label class="payment-item">
                    <input type="radio" name="payment_method" value="banking">
                    <div class="payment-content">
                        <div class="payment-icon"><i class="fa-solid fa-qrcode"></i></div>
                        <div class="payment-text">
                            <strong>Chuyển khoản MoMo / QR</strong>
                            <span>Thanh toán bằng mã QR MoMo.</span>
                        </div>
                    </div>
                </label>

                <div id="bank-info" class="bank-details" style="display: none;">
                    <div class="bank-info-header"><i class="fa-solid fa-circle-info"></i> THÔNG TIN THANH TOÁN MOMO</div>
                    <div class="bank-info-body">
                        <p><strong>Ví nhận:</strong> MoMo - LUMINA COSMETICS</p>
                        <p><strong>Số điện thoại:</strong> 0909 123 456</p>
                        <p><strong>Nội dung:</strong> LUMINA [Số điện thoại của bạn]</p>
                        <button type="button" id="btn-show-momo-qr" style="margin-top: 10px; background: #a50064; color: #fff; border: none; border-radius: 6px; padding: 10px 14px; cursor: pointer; font-weight: 600; width: 100%;">
                            <i class="fa-solid fa-qrcode"></i> Xem mã QR MoMo
                        </button>
                    </div>
                </div>
            </div> 
        </div> 

        <div class="order-review">
            <h3>Đơn hàng của bạn</h3>
            
            <div class="order-items-list">
                <?php foreach ($cart_items as $item): ?>
                    <div class="review-item" id="item-<?= $item['pv_id'] ?>" style="display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px dashed #eee;">
                        <div class="item-main">
                            <span class="item-title" style="display: block; font-weight: 500; color: #333; margin-bottom: 5px;">
                                <?= htmlspecialchars($item['title']); ?>
                            </span>
                            <span style="font-size: 13px; color: #777; background: #f9f9f9; padding: 2px 6px; border-radius: 4px; border: 1px solid #eee;">
                                Phân loại: <?= htmlspecialchars($item['variant_name']); ?>
                            </span>
                            <div class="item-actions" style="margin-top: 8px;">
                                <div class="quantity-selector">
                                    <button type="button" class="qty-btn" onclick="changeQty(<?= $item['pv_id'] ?>, -1)">-</button>
                                    <input type="text" id="qty-<?= $item['pv_id'] ?>" class="qty-input" value="<?= $item['qty'] ?>" readonly>
                                    <button type="button" class="qty-btn" onclick="changeQty(<?= $item['pv_id'] ?>, 1)">+</button>
                                </div>
                            </div>
                        </div>
                        <span class="item-price" id="price-<?= $item['pv_id'] ?>" style="font-weight: bold; color: #D4A373;">
                            <?= number_format($item['subtotal'], 0, ',', '.'); ?>đ
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="margin-top: 20px; border-top: 1px dashed #e2e8f0; padding-top: 14px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 15px; color: #475569;">
                    <span>Tạm tính:</span>
                    <span id="subtotal-price"><?= number_format($total_price, 0, ',', '.'); ?>đ</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 15px; color: #475569;">
                    <span>Phí vận chuyển:</span>
                    <span id="shipping-price">0đ</span>
                </div>
                <div class="total-row" style="display: flex; justify-content: space-between; margin-top: 12px; font-size: 18px;">
                    <span>Tổng cộng:</span>
                    <span class="total-price" id="grand-total" style="color: #e74c3c; font-weight: bold; font-size: 24px;">
                        <?= number_format($total_price, 0, ',', '.'); ?>đ
                    </span>
                </div>
            </div>

            <button type="submit" class="btn-order">XÁC NHẬN ĐẶT HÀNG</button>
        </div>
    </form>
</main>

<div id="momo-qr-modal" style="display:none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.62); z-index: 10000; align-items: center; justify-content: center; padding: 16px;">
    <div style="width: 100%; max-width: 460px; background: #fff; border-radius: 18px; overflow: hidden; box-shadow: 0 25px 60px rgba(0,0,0,0.25);">
        <div style="background: linear-gradient(135deg, #a50064, #d81b60); color: #fff; padding: 18px 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
            <div>
                <div style="font-size: 13px; opacity: 0.9;">Thanh toán qua</div>
                <div style="font-size: 22px; font-weight: 800; letter-spacing: 0.02em;">MoMo QR</div>
            </div>
            <div style="width: 48px; height: 48px; border-radius: 50%; background: rgba(255,255,255,0.16); display:flex; align-items:center; justify-content:center; font-size: 24px;">
                <i class="fa-solid fa-qrcode"></i>
            </div>
        </div>
        <div style="padding: 22px; text-align: center;">
           
            <p style="margin: 0 0 12px; color: #334155; line-height: 1.55;">Quét mã QR bên dưới bằng MoMo để thanh toán cho đơn hàng.</p>
            <img id="momo-qr-image" src="" alt="Mã QR MoMo" style="width: 280px; max-width: 100%; aspect-ratio: 1; object-fit: contain; border: 10px solid #f8fafc; border-radius: 18px; background: #fff; box-shadow: inset 0 0 0 1px #e2e8f0;">
            <div style="margin-top: 16px; text-align: left; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 14px; color: #334155; font-size: 14px; line-height: 1.7;">
                <div><strong>Ví nhận:</strong> MoMo - LUMINA COSMETICS</div>
                <div><strong>Số điện thoại:</strong> 0909 123 456</div>
                <div><strong>Nội dung:</strong> LUMINA [Số điện thoại của bạn]</div>
            </div>
            <div style="display:flex; gap: 10px; margin-top: 18px;">
                <button type="button" id="btn-close-momo-qr" style="flex: 1; background: #e2e8f0; color: #334155; border: none; border-radius: 10px; padding: 12px 16px; cursor: pointer; font-weight: 700;">Đóng</button>
                <button type="button" id="btn-confirm-momo-paid" style="flex: 1; background: #a50064; color: #fff; border: none; border-radius: 10px; padding: 12px 16px; cursor: pointer; font-weight: 700;">Tôi đã chuyển khoản</button>
            </div>
        </div>
    </div>
</div>

<div id="map-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #fff; border-radius: 12px; width: 90%; max-width: 800px; height: 90%; max-height: 700px; display: flex; flex-direction: column; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);">
        <div style="padding: 20px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 18px; color: #333;">Chọn vị trí giao hàng</h3>
            <button type="button" id="btn-close-map" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #999;">✕</button>
        </div>
        <div style="flex: 1; position: relative; overflow: hidden;">
            <div id="modal-map" style="width: 100%; height: 100%;"></div>
        </div>
        <div style="padding: 15px; border-top: 1px solid #e0e0e0; display: flex; gap: 10px; justify-content: space-between;">
            <button type="button" id="btn-current-location-modal" style="background: #2c3e50; color: #fff; border: none; border-radius: 6px; padding: 10px 16px; cursor: pointer;">
                <i class="fa-solid fa-location-crosshairs"></i> Vị trí hiện tại
            </button>
            <div style="display: flex; gap: 10px;">
                <button type="button" id="btn-cancel-map" style="background: #95a5a6; color: #fff; border: none; border-radius: 6px; padding: 10px 20px; cursor: pointer;">
                    Hủy
                </button>
                <button type="button" id="btn-confirm-map" style="background: #D4A373; color: #fff; border: none; border-radius: 6px; padding: 10px 20px; cursor: pointer;">
                    Xác nhận
                </button>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
$(document).ready(function() {
    const subtotal = <?= (int) $total_price ?>;
    let shippingFee = 0;
    let marker = null;
    let modalMap = null;
    let searchTimer = null;
    let pendingOrderData = null;

    const shopLocation = {
        lat: <?= SHOP_LAT ?>,
        lng: <?= SHOP_LNG ?>,
        name: '<?= addslashes(SHOP_NAME) ?>'
    };

    const leafletReady = typeof window.L !== 'undefined';

    function ensureLeafletReady() {
        if (!leafletReady) {
            Swal.fire({ icon: 'error', title: 'Lỗi bản đồ', text: 'Không tải được thư viện bản đồ Leaflet.' });
            return false;
        }

        return true;
    }

    function formatMoney(value) {
        return Number(value).toLocaleString('vi-VN') + 'đ';
    }

    function persistCustomerAddress(addressText) {
        const normalizedAddress = (addressText || '').trim();
        if (normalizedAddress.length < 3) {
            return;
        }

        $.post('../backend/save_customer_address.php', { address: normalizedAddress });
    }

    function collectOrderData() {
        const mapAddress = $('#map-address').val().trim();
        return {
            fullname: $('#fullname').val(),
            phone: $('#phone').val(),
            email: $('#email').val(),
            address: mapAddress,
            note: $('#note').val(),
            payment_method: $('input[name="payment_method"]:checked').val(),
            subtotal_price: subtotal,
            shipping_fee: shippingFee,
            customer_lat: $('#customer-lat').val(),
            customer_lng: $('#customer-lng').val(),
            total_price: subtotal + shippingFee
        };
    }

    function buildMomoQrUrl(orderData) {
        const paymentText = [
            'MO MO THANH TOAN',
            'Merchant: LUMINA COSMETICS',
            'Phone: 0909123456',
            'Content: LUMINA ' + (orderData.phone || ''),
            'Total: ' + formatMoney(orderData.total_price)
        ].join('\n');

        return 'https://quickchart.io/qr?size=320&text=' + encodeURIComponent(paymentText);
    }

    function openMomoQrModal(orderData) {
        pendingOrderData = orderData;
        $('#momo-qr-image').attr('src', buildMomoQrUrl(orderData));
        $('#momo-qr-modal').css('display', 'flex');
    }

    function closeMomoQrModal() {
        $('#momo-qr-modal').hide();
    }

    function submitOrder(orderData) {
        $('.btn-order').text('ĐANG XỬ LÝ...').prop('disabled', true);

        $.ajax({
            url: '../backend/checkout_process.php',
            type: 'POST',
            data: orderData,
            success: function(response) {
                let res;
                try { res = (typeof response === 'object') ? response : JSON.parse(response); }
                catch(e) { Swal.fire({ icon: 'error', title: 'Lỗi dữ liệu', text: 'Lỗi dữ liệu trả về!' }); $('.btn-order').text('XÁC NHẬN ĐẶT HÀNG').prop('disabled', false); return; }

                if(res.status === 'success') {
                    const orderDisplay = res.order_code || res.order_id;
                    Swal.fire({ icon: 'success', title: 'Đặt hàng thành công', text: 'Mã đơn: #' + orderDisplay, timer: 1800, showConfirmButton: false }).then(() => {
                        window.location.href = 'order_history.php';
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Đặt hàng thất bại', text: 'Lỗi: ' + res.message });
                    $('.btn-order').text('XÁC NHẬN ĐẶT HÀNG').prop('disabled', false);
                }
            },
            error: function() {
                Swal.fire({ icon: 'error', title: 'Lỗi kết nối', text: 'Lỗi kết nối máy chủ!' });
                $('.btn-order').text('XÁC NHẬN ĐẶT HÀNG').prop('disabled', false);
            }
        });
    }

    function updateTotals() {
        $('#shipping-price').text(formatMoney(shippingFee));
        $('#grand-total').text(formatMoney(subtotal + shippingFee));
    }

    function reverseGeocode(lat, lng) {
        const url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&lat=' + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng);

        return fetch(url, {
            headers: {
                'Accept-Language': 'vi'
            }
        }).then((response) => {
            if (!response.ok) {
                throw new Error('Reverse geocode failed');
            }

            return response.json();
        });
    }

    function forwardGeocode(query) {
        const url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&limit=5&q=' + encodeURIComponent(query);

        return fetch(url, {
            headers: {
                'Accept-Language': 'vi'
            }
        }).then((response) => {
            if (!response.ok) {
                throw new Error('Search failed');
            }

            return response.json();
        }).then((items) => {
            return (Array.isArray(items) ? items : []).map((result) => ({
                display_name: result.display_name,
                lat: result.lat,
                lon: result.lon
            }));
        });
    }

    function requestShippingQuote(lat, lng) {
        return $.post('../backend/shipping_quote.php', { lat, lng });
    }

    function setMarker(lat, lng, shouldOpenModal = true) {
        // Ensure modal map is initialized only when the user explicitly opens the map
        if (modalMap === null) {
            if (shouldOpenModal) {
                $('#map-modal').css('display', 'flex');
            }
            initMapModal();
        }
        
        if (marker) {
            marker.setLatLng([parseFloat(lat), parseFloat(lng)]);
        } else {
            marker = L.marker([parseFloat(lat), parseFloat(lng)]).addTo(modalMap);
        }
        modalMap.setView([parseFloat(lat), parseFloat(lng)], modalMap.getZoom() || 15);
        $('#customer-lat').val(lat);
        $('#customer-lng').val(lng);

        reverseGeocode(lat, lng)
            .then((geo) => {
                const displayAddress = geo.display_name || 'Đã chọn vị trí trên bản đồ';
                $('#map-address').val(displayAddress);
                $('#map-address-preview').text('Địa chỉ từ bản đồ: ' + displayAddress);
                $('#address-search').val(displayAddress);
                persistCustomerAddress(displayAddress);
            })
            .catch(() => {
                $('#map-address').val('Đã chọn vị trí trên bản đồ');
                $('#map-address-preview').text('Đã chọn vị trí trên bản đồ (không đọc được địa chỉ chi tiết).');
            });

        requestShippingQuote(lat, lng)
            .done((resp) => {
                const res = (typeof resp === 'object') ? resp : JSON.parse(resp);
                if (res.status === 'success') {
                    shippingFee = Number(res.shipping_fee || 0);
                    $('#distance-preview').text('Khoảng cách tạm tính: ' + Number(res.distance_km).toFixed(2) + ' km');
                    updateTotals();
                } else {
                    Swal.fire({ icon: 'warning', title: 'Không thể tính phí', text: res.message || 'Không thể tính phí vận chuyển.' });
                }
            })
            .fail(() => {
                Swal.fire({ icon: 'error', title: 'Lỗi kết nối', text: 'Không thể kết nối máy chủ để tính phí vận chuyển.' });
            });
    }

    function initMapModal() {
        if (modalMap !== null) return;

        if (!ensureLeafletReady()) {
            return;
        }

        modalMap = L.map('modal-map', {
            zoomControl: true,
            scrollWheelZoom: true
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(modalMap);

        modalMap.setView([shopLocation.lat, shopLocation.lng], 15);

        modalMap.on('click', function(event) {
            setMarker(event.latlng.lat, event.latlng.lng);
        });

        marker = L.marker([shopLocation.lat, shopLocation.lng]).addTo(modalMap);
    }

    function initSavedAddress() {
        const savedAddress = $('#address-search').val().trim();
        if (!savedAddress) {
            return;
        }

        forwardGeocode(savedAddress).then((items) => {
            if (items.length > 0) {
                setMarker(parseFloat(items[0].lat), parseFloat(items[0].lon), false);
            } else {
                $('#map-address').val(savedAddress);
                $('#map-address-preview').text('Địa chỉ đã lưu: ' + savedAddress);
            }
        }).catch(() => {
            $('#map-address').val(savedAddress);
            $('#map-address-preview').text('Địa chỉ đã lưu: ' + savedAddress);
        });
    }

    function renderAddressSuggestions(items) {
        const dropdown = $('#address-suggestions');
        dropdown.empty();

        if (!Array.isArray(items) || items.length === 0) {
            dropdown.hide();
            return;
        }

        items.forEach((item) => {
            const button = $('<button type="button"></button>')
                .css({
                    display: 'block',
                    width: '100%',
                    textAlign: 'left',
                    padding: '10px 12px',
                    border: 'none',
                    background: '#fff',
                    borderBottom: '1px solid #eef2f7',
                    cursor: 'pointer',
                    fontSize: '13px',
                    lineHeight: '1.45',
                    color: '#334155'
                })
                .text(item.display_name)
                .on('click', function() {
                    $('#address-search').val(item.display_name);
                    dropdown.hide();
                    setMarker(parseFloat(item.lat), parseFloat(item.lon));
                    persistCustomerAddress(item.display_name);
                });

            dropdown.append(button);
        });

        dropdown.show();
    }

    $('#address-search').on('input', function() {
        const query = $(this).val().trim();
        clearTimeout(searchTimer);

        if (query.length >= 3) {
            persistCustomerAddress(query);
        }

        if (query.length < 3) {
            $('#address-suggestions').hide().empty();
            return;
        }

        searchTimer = setTimeout(function() {
            forwardGeocode(query).then((items) => {
                renderAddressSuggestions(items);
            }).catch(() => {
                $('#address-suggestions').hide().empty();
            });
        }, 300);
    });

    $('#address-search').on('focus', function() {
        if ($(this).val().trim().length >= 3 && $('#address-suggestions').children().length > 0) {
            $('#address-suggestions').show();
        }
    });

    $('#address-search').on('blur', function() {
        persistCustomerAddress($(this).val());
    });

    $('#btn-open-map').click(function() {
        $('#map-modal').css('display', 'flex');
        if (modalMap === null) {
            initMapModal();
        } else {
            modalMap.invalidateSize();
            modalMap.setView([
                parseFloat($('#customer-lat').val() || shopLocation.lat),
                parseFloat($('#customer-lng').val() || shopLocation.lng)
            ], modalMap.getZoom() || 15);
        }
    });

    $('#btn-close-map, #btn-cancel-map').click(function() {
        $('#map-modal').hide();
    });

    $('#btn-confirm-map').click(function() {
        if ($('#customer-lat').val() && $('#customer-lng').val()) {
            $('#map-modal').hide();
        } else {
            Swal.fire({ icon: 'warning', title: 'Thiếu vị trí', text: 'Vui lòng chọn một vị trí trên bản đồ.' });
        }
    });

    $('#btn-current-location-modal').click(function() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                setMarker(position.coords.latitude, position.coords.longitude);
            }, function(error) {
                Swal.fire({ icon: 'error', title: 'Không lấy được vị trí', text: 'Không thể lấy vị trí hiện tại. Vui lòng cho phép truy cập vị trí.' });
            });
        }
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#address-search').length) {
            $('#address-suggestions').hide();
        }
    });

    updateTotals();
    initSavedAddress();

    $('input[name="payment_method"]').change(function() {
        if ($(this).val() === 'banking') {
            $('#bank-info').slideDown();
        } else {
            $('#bank-info').slideUp();
            closeMomoQrModal();
        }
    });

    $('#btn-show-momo-qr').click(function() {
        const orderData = collectOrderData();
        openMomoQrModal(orderData);
    });

    $('#btn-close-momo-qr').click(function() {
        closeMomoQrModal();
    });

    $('#momo-qr-modal').click(function(e) {
        if (e.target === this) {
            closeMomoQrModal();
        }
    });

    $('#btn-confirm-momo-paid').click(function() {
        const orderData = pendingOrderData || collectOrderData();

        closeMomoQrModal();
        submitOrder(orderData);
    });

    $('#checkout-form').submit(function(e) {
        e.preventDefault();
        const orderData = collectOrderData();

        if (!orderData.customer_lat || !orderData.customer_lng) {
            Swal.fire({ icon: 'warning', title: 'Thiếu vị trí', text: 'Vui lòng tìm và chọn địa chỉ giao hàng từ ô tìm kiếm bản đồ.' });
            return;
        }

        if (!orderData.address) {
            Swal.fire({ icon: 'warning', title: 'Thiếu địa chỉ', text: 'Vui lòng chọn một địa chỉ hợp lệ từ gợi ý tìm kiếm.' });
            return;
        }

        if (orderData.payment_method === 'banking') {
            openMomoQrModal(orderData);
            return;
        }

        submitOrder(orderData);
    });
});

function changeQty(variantId, delta) {
    let qtyInput = $('#qty-' + variantId);
    let newQty = parseInt(qtyInput.val()) + delta;
    if (newQty < 1) return;

    $.post('../backend/cart_process.php', { action: 'update', id: variantId, qty: newQty }, function(response) {
        let res;
        try { res = (typeof response === 'object') ? response : JSON.parse(response); } 
        catch(e) { location.reload(); return; }

        if (res.status === 'success') { location.reload(); } 
        else { Swal.fire({ icon: 'error', title: 'Lỗi', text: 'Lỗi: ' + res.message }); location.reload(); }
    });
}
</script>

<?php include_once '../includes/footer.php'; ?>
