CREATE TABLE IF NOT EXISTS orders (
    orderId BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    orderCode VARCHAR(32) NOT NULL,
    checkoutToken CHAR(64) NOT NULL,
    userId INT(11) NULL,
    guestToken CHAR(64) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'pending_payment',
    totalAmount DECIMAL(12, 2) NOT NULL,
    createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (orderId),
    UNIQUE KEY uq_orders_order_code (orderCode),
    UNIQUE KEY uq_orders_checkout_token (checkoutToken),
    KEY idx_orders_user_created (userId, createdAt),
    KEY idx_orders_guest_created (guestToken, createdAt),
    CONSTRAINT fk_orders_user
        FOREIGN KEY (userId) REFERENCES users (userId)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
    orderItemId BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    orderId BIGINT UNSIGNED NOT NULL,
    productId INT(11) NULL,
    productName VARCHAR(200) NOT NULL,
    productImage VARCHAR(255) NOT NULL,
    unitPrice DECIMAL(12, 2) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    lineTotal DECIMAL(12, 2) NOT NULL,
    PRIMARY KEY (orderItemId),
    KEY idx_order_items_order (orderId),
    KEY idx_order_items_product (productId),
    CONSTRAINT fk_order_items_order
        FOREIGN KEY (orderId) REFERENCES orders (orderId)
        ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product
        FOREIGN KEY (productId) REFERENCES products (productId)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
