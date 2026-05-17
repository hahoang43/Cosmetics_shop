-- ==========================================
-- 1. BẢNG PHÂN QUYỀN VÀ NGƯỜI DÙNG
-- ==========================================
CREATE TABLE Role (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(20) NOT NULL
);
-- ==========================================
-- DỮ LIỆU CẤU HÌNH HỆ THỐNG GỐC (BẮT BUỘC)
-- ==========================================

CREATE TABLE User (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(50) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone_number VARCHAR(20),
    address VARCHAR(200),
    password VARCHAR(100) NOT NULL,
    role INT DEFAULT 0,
    role_id INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted TINYINT DEFAULT 0,
    FOREIGN KEY (role_id) REFERENCES Role(id)
);

-- Đã vá lại cấu trúc bảng Token bị lỗi cú pháp trước đó
CREATE TABLE User_Token (
    user_id INT,
    token VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, token),
    FOREIGN KEY (user_id) REFERENCES User(id)
);
INSERT INTO Role (id, name) VALUES
(1, 'admin'),
(2, 'user');

INSERT INTO User (fullname, email, phone_number, address, password, role, role_id)
VALUES
('Admin Quản Trị', 'admin@gmail.com', '0123456789', '123 Admin St', '$2y$10$fkPEcG1dQXdpCBqMlcWBnO6Q3rozNyO50qQH6FZJD8CcEFSJMYdmm', 1, 1);

-- ==========================================
-- 2. BẢNG DANH MỤC VÀ SẢN PHẨM CHUNG
-- ==========================================
CREATE TABLE Category (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

CREATE TABLE Product (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    title VARCHAR(250) NOT NULL,
    price INT NOT NULL,       -- Giá hiển thị mặc định (VD: Giá từ...)
    old_price INT DEFAULT 0,  
    thumbnail VARCHAR(500),
    description LONGTEXT,
    brand VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted TINYINT DEFAULT 0,
    FOREIGN KEY (category_id) REFERENCES Category(id)
);

-- ==========================================
-- 3. BẢNG BIẾN THỂ (GỘP CHUNG SIZE VÀ MÀU SẮC)
-- ==========================================
CREATE TABLE Variant (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL -- Lưu giá trị như '30ml', 'Ruby Woo', 'Tone 21'
);

CREATE TABLE Product_Variant (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    variant_id INT,
    price INT NOT NULL,       -- Giá cho MỖI phân loại (Vì 15ml giá sẽ khác 50ml)
    old_price INT DEFAULT 0,
    quantity INT DEFAULT 0,   
    thumbnail VARCHAR(500) DEFAULT NULL,  -- Hình ảnh riêng cho MỖI phân loại màu sắc
    FOREIGN KEY (product_id) REFERENCES Product(id),
    FOREIGN KEY (variant_id) REFERENCES Variant(id)
);

-- ==========================================
-- 4. CÁC BẢNG TƯƠNG TÁC SẢN PHẨM (ẢNH, ĐÁNH GIÁ, QA)
-- ==========================================
CREATE TABLE Galery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    thumbnail VARCHAR(500),
    FOREIGN KEY (product_id) REFERENCES Product(id)
);

CREATE TABLE Product_Review (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    image VARCHAR(255) DEFAULT NULL, -- Đã tích hợp: Đường dẫn hình ảnh swatch thực tế
    video VARCHAR(255) DEFAULT NULL, -- Đã tích hợp: Đường dẫn video unboxing sản phẩm
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES Product(id),
    FOREIGN KEY (user_id) REFERENCES User(id)
);

CREATE TABLE Product_QA (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT, 
    question TEXT NOT NULL,
    answer TEXT, 
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES Product(id)
);

-- ==========================================
-- 5. BẢNG ĐƠN HÀNG VÀ CHI TIẾT ĐƠN HÀNG
-- ==========================================
-- Quy ước định danh các trạng thái đơn hàng (status) mới:
-- 0: Chờ xác nhận | 1: Đang giao | 2: Đã giao | 3: Đã hủy
-- 4: Chờ duyệt trả hàng | 5: Đã hoàn trả thành công (Hoàn kho) | 6: Từ chối trả hàng
CREATE TABLE Orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    fullname VARCHAR(50),
    email VARCHAR(150),
    phone_number VARCHAR(20),
    address VARCHAR(200),
    note VARCHAR(1000),
    payment_method VARCHAR(50) DEFAULT 'cod',
    order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status INT DEFAULT 0,
    return_reason TEXT DEFAULT NULL, -- Đã tích hợp: Lưu lý do khách gửi yêu cầu hoàn trả
    total_money INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES User(id)
);

CREATE TABLE Order_Details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    product_variant_id INT, -- Liên kết tới biến thể khách đã chọn (vd: Son màu Đỏ Thuần)
    price INT NOT NULL,
    num INT NOT NULL,
    total_money INT NOT NULL,
    FOREIGN KEY (order_id) REFERENCES Orders(id),
    FOREIGN KEY (product_id) REFERENCES Product(id),
    FOREIGN KEY (product_variant_id) REFERENCES Product_Variant(id)
);

-- ==========================================
-- 6. CÁC BẢNG PHỤ KHÁC (FEEDBACK, BANNER)
-- ==========================================
CREATE TABLE FeedBack (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firstname VARCHAR(30),
    lastname VARCHAR(30),
    email VARCHAR(250),
    phone_number VARCHAR(20),
    subject_name VARCHAR(350),
    note VARCHAR(1000),
    status INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE Banner (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image VARCHAR(255) NOT NULL
);

COMMIT;