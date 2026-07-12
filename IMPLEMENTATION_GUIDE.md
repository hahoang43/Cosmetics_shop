## Hướng Dẫn Cập Nhật Tính Năng Mã Đơn Hàng và Tìm Kiếm

### 1. Cập Nhật Cơ Sở Dữ Liệu

Chạy file migration SQL để thêm cột `order_code` vào bảng Orders:

```
File: config/migration_add_order_code.sql

Cách thực hiện:
- Mở phpMyAdmin
- Chọn database "db_mypham"
- Chọn tab "SQL"
- Copy toàn bộ nội dung từ file migration_add_order_code.sql vào ô nhập SQL
- Nhấn "Thực hiện" (Execute)
```

### 2. Các Tính Năng Đã Thêm

#### A. Mã Đơn Hàng Tự Động (7 ký tự)
- **Vị trí**: Khi khách hàng tạo đơn hàng mới
- **Cách hoạt động**: Mã được sinh ngẫu nhiên gồm 7 ký tự (chữ hoa, thường + số)
- **Ví dụ mã**: `aB3xYz9`, `Km7pLw2`
- **File liên quan**: `backend/checkout_process.php`

#### B. Tìm Kiếm Đơn Hàng (Admin)
- **Vị trí**: Trang quản lý đơn hàng (`admin/orders.php`)
- **Chức năng**: Tìm kiếm theo:
  - Mã đơn hàng (VD: tìm "aB3xYz9")
  - Tên khách hàng (VD: tìm "Nguyễn Văn A")
- **Giao diện**: Thanh tìm kiếm phía trên bảng danh sách đơn hàng
- **Tính năng bổ sung**: Nút "Xóa lọc" để hiển thị tất cả đơn hàng

#### C. Tìm Kiếm Người Dùng (Admin)
- **Vị trí**: Trang quản lý khách hàng (`admin/users.php`)
- **Chức năng**: Tìm kiếm theo:
  - Họ và tên
  - Email
  - Số điện thoại
- **Giao diện**: Thanh tìm kiếm phía trên bảng danh sách người dùng
- **Tính năng bổ sung**: Nút "Xóa lọc" để hiển thị tất cả người dùng

### 3. File Được Sửa Đổi

1. **config/database.php**
   - Thêm hàm `generateOrderCode()` - sinh mã 7 ký tự ngẫu nhiên

2. **backend/checkout_process.php**
   - Sử dụng `generateOrderCode()` khi tạo đơn hàng mới
   - Lưu mã vào cột `order_code`

3. **admin/orders.php**
   - Thêm form tìm kiếm
   - Cập nhật hiển thị mã đơn hàng từ cột `order_code`
   - Lọc dữ liệu theo tên khách hàng hoặc mã đơn hàng

4. **admin/users.php**
   - Thêm form tìm kiếm
   - Lọc dữ liệu theo tên, email hoặc số điện thoại

### 4. Kiểm Tra Hoạt Động

1. **Test Mã Đơn Hàng**:
   - Đặt một đơn hàng mới từ phía khách hàng
   - Vào `admin/orders.php`
   - Kiểm tra xem mã đơn hàng có hiển thị (VD: #aB3xYz9)

2. **Test Tìm Kiếm Đơn Hàng**:
   - Trên `admin/orders.php`, nhập mã đơn hàng vào ô tìm kiếm
   - Nhấn "Tìm kiếm"
   - Hoặc nhập tên khách hàng để tìm các đơn hàng của họ

3. **Test Tìm Kiếm Người Dùng**:
   - Trên `admin/users.php`, nhập tên/email/số điện thoại vào ô tìm kiếm
   - Nhấn "Tìm kiếm"

### 5. Ghi Chú Quan Trọng

- **Các đơn hàng cũ**: Nếu có, sẽ không có mã `order_code`, nhưng vẫn sử dụng được ID (#123)
- **Tìm kiếm**: Hỗ trợ tìm kiếm "mờ" - ví dụ tìm "Nguyễn" sẽ hiển thị tất cả khách hàng có tên chứa "Nguyễn"
- **An toàn**: Tất cả dữ liệu tìm kiếm được xử lý an toàn với prepared statements
