<?php
session_start();

/** @var PDO|null $conn */
require_once '../config/database.php';
require_once '../config/settings.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

// Lấy settings hiện tại
$stmt = $conn->prepare("SELECT setting_key, setting_value FROM Settings LIMIT 10");
$stmt->execute();
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Xử lý form cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $shop_lat = floatval($_POST['shop_lat']);
        $shop_lng = floatval($_POST['shop_lng']);
        $shop_name = trim($_POST['shop_name']);
        $shop_address = trim($_POST['shop_address']);
        $shipping_base_fee = intval($_POST['shipping_base_fee']);
        $shipping_per_km_fee = intval($_POST['shipping_per_km_fee']);
        $shipping_max_fee = intval($_POST['shipping_max_fee']);

        // Kiểm tra giá trị hợp lệ
        if ($shop_lat < -90 || $shop_lat > 90 || $shop_lng < -180 || $shop_lng > 180) {
            throw new InvalidArgumentException('Tọa độ không hợp lệ (lat: -90 đến 90, lng: -180 đến 180)');
        }
        if (empty($shop_name)) {
            throw new InvalidArgumentException('Tên shop không được để trống');
        }
        if (empty($shop_address)) {
            throw new InvalidArgumentException('Địa chỉ shop không được để trống');
        }
        if ($shipping_base_fee < 0 || $shipping_per_km_fee < 0 || $shipping_max_fee < 0) {
            throw new InvalidArgumentException('Phí vận chuyển không được âm');
        }

        // Cập nhật settings
        $updates = [
            'shop_lat' => $shop_lat,
            'shop_lng' => $shop_lng,
            'shop_name' => $shop_name,
            'shop_address' => $shop_address,
            'shipping_base_fee' => $shipping_base_fee,
            'shipping_per_km_fee' => $shipping_per_km_fee,
            'shipping_max_fee' => $shipping_max_fee
        ];

        foreach ($updates as $key => $value) {
            $stmt = $conn->prepare("INSERT INTO Settings (setting_key, setting_value) VALUES (?, ?)
                                   ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }

        $_SESSION['success'] = 'Cập nhật cài đặt shop thành công!';
        header('Location: settings.php');
        exit;
    } catch (Throwable $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Giá trị mặc định
$shop_lat = isset($settings['shop_lat']) ? floatval($settings['shop_lat']) : 10.776889;
$shop_lng = isset($settings['shop_lng']) ? floatval($settings['shop_lng']) : 106.700806;
$shop_name = isset($settings['shop_name']) ? htmlspecialchars($settings['shop_name']) : 'Lumina Cosmetics';
$shop_address = isset($settings['shop_address']) ? htmlspecialchars($settings['shop_address']) : 'Số 2, Võ Oanh, P.25, Bình Thạnh, TP.HCM';
$shipping_base_fee = isset($settings['shipping_base_fee']) ? intval($settings['shipping_base_fee']) : 15000;
$shipping_per_km_fee = isset($settings['shipping_per_km_fee']) ? intval($settings['shipping_per_km_fee']) : 3500;
$shipping_max_fee = isset($settings['shipping_max_fee']) ? intval($settings['shipping_max_fee']) : 80000;
?>
<?php include_once '../includes/admin_header.php'; ?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<main style="max-width: 900px; margin: 40px auto; padding: 0 20px;">
    <h2 style="font-family: 'Playfair Display', serif; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 30px;">
        ⚙️ Cài đặt Shop
    </h2>

    <?php if (isset($_SESSION['success'])): ?>
        <div style="background: #d4edda; color: #155724; padding: 12px; margin-bottom: 20px; border-radius: 5px; border: 1px solid #c3e6cb;">
            ✓ <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 12px; margin-bottom: 20px; border-radius: 5px; border: 1px solid #f5c6cb;">
            ✕ <?= htmlspecialchars($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <form method="POST" style="background: #fdfaf6; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        
        <h3 style="margin-top: 0; margin-bottom: 20px; color: #333;">Thông tin Shop</h3>
        
        <div style="margin-bottom: 15px;">
            <label for="shop_name" style="display: block; margin-bottom: 5px; font-weight: 500;">Tên Shop:</label>
            <input id="shop_name" type="text" name="shop_name" value="<?= $shop_name ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" required>
        </div>

        <div style="margin-bottom: 15px;">
            <label for="shop_address" style="display: block; margin-bottom: 5px; font-weight: 500;">Địa chỉ Shop (chọn trên bản đồ):</label>
            <input id="shop_address" type="text" name="shop_address" value="<?= $shop_address ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" required readonly>
            <small style="color: #666; display: block; margin-top: 3px;">Vui lòng chọn vị trí bằng nút "Chọn vị trí shop trên bản đồ" bên dưới. Địa chỉ sẽ được lấy từ bản đồ.</small>
        </div>

        <h3 style="margin-top: 25px; margin-bottom: 20px; color: #333;">Vị trí Shop (Tọa độ)</h3>
        
        <input type="hidden" name="shop_lat" value="<?= $shop_lat ?>">
        <input type="hidden" name="shop_lng" value="<?= $shop_lng ?>">
        <div style="margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
            <button type="button" id="btn-open-shop-map" style="background: #2c3e50; color: #fff; border: none; padding: 12px 18px; border-radius: 5px; cursor: pointer; font-weight: 500;">
                <i class="fa-solid fa-map-location-dot"></i> Chọn vị trí shop trên bản đồ
            </button>
            <p id="shop-map-preview" style="margin: 0; color: #666; font-size: 13px;">
                Vị trí hiện tại: <?= htmlspecialchars($shop_lat) ?>, <?= htmlspecialchars($shop_lng) ?>
            </p>
        </div>
        <p style="color: #666; font-size: 13px; margin-top: 10px;">
            💡 Có thể chọn trực tiếp trên bản đồ hoặc dùng vị trí hiện tại của trình duyệt.
        </p>

        <h3 style="margin-top: 25px; margin-bottom: 20px; color: #333;">Phí Vận Chuyển</h3>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div>
                <label for="shipping_base_fee" style="display: block; margin-bottom: 5px; font-weight: 500;">Phí cơ bản (đ):</label>
                <input id="shipping_base_fee" type="number" name="shipping_base_fee" value="<?= $shipping_base_fee ?>" min="0" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" required>
                <small style="color: #666; display: block; margin-top: 3px;">Phí cố định cho mỗi đơn hàng</small>
            </div>
            <div>
                <label for="shipping_per_km_fee" style="display: block; margin-bottom: 5px; font-weight: 500;">Phí mỗi km (đ):</label>
                <input id="shipping_per_km_fee" type="number" name="shipping_per_km_fee" value="<?= $shipping_per_km_fee ?>" min="0" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" required>
                <small style="color: #666; display: block; margin-top: 3px;">Phí cho mỗi km khoảng cách</small>
            </div>
        </div>

        <div style="margin-bottom: 15px; margin-top: 15px;">
            <label for="shipping_max_fee" style="display: block; margin-bottom: 5px; font-weight: 500;">Phí vận chuyển tối đa (đ):</label>
            <input id="shipping_max_fee" type="number" name="shipping_max_fee" value="<?= $shipping_max_fee ?>" min="0" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" required>
            <small style="color: #666; display: block; margin-top: 3px;">Giới hạn tối đa phí vận chuyển</small>
        </div>

        <div style="margin-top: 30px; display: flex; gap: 10px;">
            <button type="submit" style="background: #D4A373; color: #fff; border: none; padding: 12px 30px; border-radius: 5px; cursor: pointer; font-weight: 500;">
                💾 Lưu cài đặt
            </button>
            <a href="index.php" style="background: #95a5a6; color: #fff; padding: 12px 30px; border-radius: 5px; text-decoration: none; font-weight: 500; display: inline-block;">
                ← Quay lại
            </a>
        </div>
    </form>
</main>

<div id="shop-map-modal" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #fff; border-radius: 12px; width: 100%; max-width: 860px; height: min(90vh, 720px); display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.25);">
        <div style="padding: 18px 20px; border-bottom: 1px solid #eee; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
            <div>
                <h3 style="margin: 0; font-size: 18px; color: #333;">Chọn vị trí shop</h3>
                <p style="margin: 4px 0 0; color: #666; font-size: 13px;">Click trên bản đồ để đặt tọa độ hoặc dùng vị trí hiện tại.</p>
            </div>
            <button type="button" id="btn-close-shop-map" style="background: none; border: none; font-size: 24px; line-height: 1; cursor: pointer; color: #999;">&times;</button>
        </div>
        <div style="padding: 14px 20px 0; position: relative;">
            <div style="position: relative;">
                <input type="text" id="shop-map-search" placeholder="Tìm địa điểm giao hàng..." autocomplete="off" style="width: 100%; padding: 12px 14px; border: 1px solid #dbe4ef; border-radius: 8px; font-size: 14px; outline: none;">
                <div id="shop-map-suggestions" style="display: none; position: absolute; top: calc(100% + 6px); left: 0; right: 0; background: #fff; border: 1px solid #dbe4ef; border-radius: 8px; box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12); max-height: 260px; overflow-y: auto; z-index: 10000;"></div>
            </div>
        </div>
        <div style="flex: 1; min-height: 0; position: relative;">
            <div id="shop-map" style="width: 100%; height: 100%;"></div>
        </div>
        <div style="padding: 14px 20px; border-top: 1px solid #eee; display: flex; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
            <button type="button" id="btn-current-shop-location" style="background: #2c3e50; color: #fff; border: none; padding: 10px 16px; border-radius: 5px; cursor: pointer;">
                <i class="fa-solid fa-location-crosshairs"></i> Vị trí hiện tại
            </button>
            <div style="display: flex; gap: 10px; margin-left: auto;">
                <button type="button" id="btn-cancel-shop-map" style="background: #95a5a6; color: #fff; border: none; padding: 10px 18px; border-radius: 5px; cursor: pointer;">Hủy</button>
                <button type="button" id="btn-confirm-shop-map" style="background: #D4A373; color: #fff; border: none; padding: 10px 18px; border-radius: 5px; cursor: pointer;">Xác nhận</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const modal = document.getElementById('shop-map-modal');
    const openButton = document.getElementById('btn-open-shop-map');
    const closeButton = document.getElementById('btn-close-shop-map');
    const cancelButton = document.getElementById('btn-cancel-shop-map');
    const confirmButton = document.getElementById('btn-confirm-shop-map');
    const currentLocationButton = document.getElementById('btn-current-shop-location');
    const latInput = document.querySelector('input[name="shop_lat"]');
    const lngInput = document.querySelector('input[name="shop_lng"]');
    const addressInput = document.getElementById('shop_address');
    const preview = document.getElementById('shop-map-preview');
    const searchInput = document.getElementById('shop-map-search');
    const suggestionsBox = document.getElementById('shop-map-suggestions');

    let shopMap = null;
    let shopMarker = null;
    let searchDebounce = null;

    function ensureLeafletReady() {
        if (typeof window.L === 'undefined') {
            alert('Không tải được thư viện bản đồ Leaflet.');
            return false;
        }

        return true;
    }

    function updatePreview(lat, lng) {
        preview.textContent = 'Vị trí hiện tại: ' + Number(lat).toFixed(6) + ', ' + Number(lng).toFixed(6);
    }

    function hideSuggestions() {
        suggestionsBox.style.display = 'none';
        suggestionsBox.innerHTML = '';
    }

    function renderSuggestions(items) {
        suggestionsBox.innerHTML = '';

        if (!Array.isArray(items) || items.length === 0) {
            hideSuggestions();
            return;
        }

        items.forEach(function(item) {
            const button = document.createElement('button');
            button.type = 'button';
            button.style.cssText = 'display:block;width:100%;text-align:left;padding:10px 12px;border:none;background:#fff;border-bottom:1px solid #eef2f7;cursor:pointer;font-size:13px;line-height:1.45;color:#334155;';
            button.textContent = item.display_name;
            button.addEventListener('click', function() {
                searchInput.value = item.display_name;
                hideSuggestions();
                setShopLocation(parseFloat(item.lat), parseFloat(item.lon), item.display_name);
            });
            suggestionsBox.appendChild(button);
        });

        suggestionsBox.style.display = 'block';
    }

    function searchOpenStreetMap(query) {
        const url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&limit=5&q=' + encodeURIComponent(query);

        return fetch(url, {
            headers: {
                'Accept-Language': 'vi'
            }
        }).then(function(response) {
            if (!response.ok) {
                throw new Error('Search failed');
            }

            return response.json();
        });
    }

    function reverseGeocode(lat, lng) {
        const url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&lat=' + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng);

        return fetch(url, {
            headers: {
                'Accept-Language': 'vi'
            }
        }).then(function(response) {
            if (!response.ok) {
                throw new Error('Reverse geocode failed');
            }

            return response.json();
        });
    }

    function setShopLocation(lat, lng, displayName) {
        latInput.value = Number(lat).toFixed(6);
        lngInput.value = Number(lng).toFixed(6);
        updatePreview(lat, lng);

        if (shopMarker) {
            shopMarker.setLatLng([parseFloat(lat), parseFloat(lng)]);
        } else if (shopMap) {
            shopMarker = L.marker([parseFloat(lat), parseFloat(lng)]).addTo(shopMap);
        }

        if (shopMap) {
            shopMap.setView([parseFloat(lat), parseFloat(lng)], shopMap.getZoom() || 15);
        }

        if (displayName) {
            searchInput.value = displayName;
            addressInput.value = displayName;
            return;
        }

        reverseGeocode(lat, lng).then(function(place) {
            if (place && place.display_name) {
                searchInput.value = place.display_name;
                addressInput.value = place.display_name;
            }
        }).catch(function() {});
    }

    function initShopMap() {
        if (shopMap) return;

        if (!ensureLeafletReady()) {
            return;
        }

        const initialLat = parseFloat(latInput.value);
        const initialLng = parseFloat(lngInput.value);

        shopMap = L.map('shop-map', {
            zoomControl: true,
            scrollWheelZoom: true
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(shopMap);

        shopMap.setView([initialLat, initialLng], 15);

        shopMarker = L.marker([initialLat, initialLng]).addTo(shopMap);

        shopMap.on('click', function(event) {
            setShopLocation(event.latlng.lat, event.latlng.lng);
        });

        setTimeout(function() {
            shopMap.invalidateSize();
        }, 50);
    }

    function openModal() {
        modal.style.display = 'flex';
        initShopMap();
        searchInput.value = searchInput.value || '';
        hideSuggestions();
        setTimeout(function() {
            if (shopMap) {
                shopMap.invalidateSize();
                shopMap.setView([parseFloat(latInput.value), parseFloat(lngInput.value)], shopMap.getZoom() || 15);
            }
        }, 100);
    }

    function closeModal() {
        modal.style.display = 'none';
    }

    openButton.addEventListener('click', openModal);
    closeButton.addEventListener('click', closeModal);
    cancelButton.addEventListener('click', closeModal);
    confirmButton.addEventListener('click', closeModal);

    currentLocationButton.addEventListener('click', function() {
        if (!navigator.geolocation) {
            alert('Trình duyệt không hỗ trợ định vị vị trí.');
            return;
        }

        navigator.geolocation.getCurrentPosition(function(position) {
            setShopLocation(position.coords.latitude, position.coords.longitude);
        }, function() {
            alert('Không thể lấy vị trí hiện tại. Vui lòng cho phép truy cập vị trí.');
        });
    });

    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        clearTimeout(searchDebounce);

        if (query.length < 3) {
            hideSuggestions();
            return;
        }

        searchDebounce = setTimeout(function() {
            searchOpenStreetMap(query).then(function(items) {
                const normalized = (Array.isArray(items) ? items : []).map(function(item) {
                    return {
                        display_name: item.display_name,
                        lat: item.lat,
                        lon: item.lon
                    };
                });

                renderSuggestions(normalized);
            }).catch(function() {
                hideSuggestions();
            });
        }, 300);
    });

    searchInput.addEventListener('focus', function() {
        if (this.value.trim().length >= 3 && suggestionsBox.children.length > 0) {
            suggestionsBox.style.display = 'block';
        }
    });

    document.addEventListener('click', function(event) {
        if (!event.target.closest('#shop-map-search') && !event.target.closest('#shop-map-suggestions')) {
            hideSuggestions();
        }
    });

    updatePreview(latInput.value, lngInput.value);

    window.addEventListener('resize', function() {
        if (shopMap) {
            shopMap.invalidateSize();
        }
    });
})();
</script>

<?php include_once '../includes/admin_footer.php'; ?>
