DROP DATABASE IF EXISTS volvo_dealership;

CREATE DATABASE volvo_dealership CHARACTER
SET
    utf8mb4 COLLATE utf8mb4_unicode_ci;

USE volvo_dealership;

-- 1. users — все пользователи (admin / manager / client)
CREATE TABLE
    users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        phone VARCHAR(30) NULL,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM ('admin', 'manager', 'client') NOT NULL DEFAULT 'client',
        is_active TINYINT (1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_users_role (role)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- 2. car_models — справочник моделей Volvo
CREATE TABLE
    car_models (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(80) NOT NULL UNIQUE,
        body_type VARCHAR(40) NULL
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- 3. cars — автомобили
CREATE TABLE
    cars (
        id INT AUTO_INCREMENT PRIMARY KEY,
        model_id INT NOT NULL,
        vin VARCHAR(17) NOT NULL UNIQUE,
        year SMALLINT NOT NULL,
        price DECIMAL(12, 2) NOT NULL,
        mileage INT NOT NULL DEFAULT 0,
        color VARCHAR(40) NULL,
        description TEXT NULL,
        status ENUM ('available', 'reserved', 'sold', 'service') NOT NULL DEFAULT 'available',
        main_image VARCHAR(255) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_cars_model FOREIGN KEY (model_id) REFERENCES car_models (id) ON DELETE RESTRICT,
        INDEX idx_cars_status (status),
        INDEX idx_cars_model (model_id)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- 4. car_config — комплектация (1:1 с cars)
CREATE TABLE
    car_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        car_id INT NOT NULL UNIQUE,
        engine VARCHAR(80) NULL,
        engine_volume DECIMAL(3, 1) NULL,
        power_hp SMALLINT NULL,
        transmission ENUM ('auto', 'manual', 'robot') DEFAULT 'auto',
        fuel ENUM ('petrol', 'diesel', 'hybrid', 'electric'),
        drive ENUM ('fwd', 'rwd', 'awd') NULL,
        trim VARCHAR(60) NULL,
        options TEXT NULL,
        CONSTRAINT fk_config_car FOREIGN KEY (car_id) REFERENCES cars (id) ON DELETE CASCADE
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- 5. car_images — галерея фотографий (1:N с cars)
CREATE TABLE
    car_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        car_id INT NOT NULL,
        path VARCHAR(255) NOT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        CONSTRAINT fk_images_car FOREIGN KEY (car_id) REFERENCES cars (id) ON DELETE CASCADE,
        INDEX idx_images_car (car_id),
        INDEX idx_images_sort (car_id, sort_order)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- 6. bookings — брони автомобилей
CREATE TABLE
    bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        car_id INT NOT NULL,
        user_id INT NULL,
        comment TEXT NULL,
        status ENUM ('new', 'confirmed', 'canceled', 'expired') NOT NULL DEFAULT 'new',
        expires_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_book_car FOREIGN KEY (car_id) REFERENCES cars (id) ON DELETE RESTRICT,
        CONSTRAINT fk_book_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
        INDEX idx_book_car (car_id),
        INDEX idx_book_user (user_id),
        INDEX idx_book_status (status)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- 7. test_drives — заявки на тест-драйв
CREATE TABLE
    test_drives (
        id INT AUTO_INCREMENT PRIMARY KEY,
        car_id INT NOT NULL,
        user_id INT NULL,
        preferred_date DATETIME NOT NULL,
        comment TEXT NULL,
        status ENUM ('new', 'in_progress', 'done', 'canceled') NOT NULL DEFAULT 'new',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_td_car FOREIGN KEY (car_id) REFERENCES cars (id) ON DELETE RESTRICT,
        CONSTRAINT fk_td_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
        INDEX idx_td_car (car_id),
        INDEX idx_td_user (user_id),
        INDEX idx_td_status (status)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- 8. sales — продажи (1 авто = 1 продажа)
CREATE TABLE
    sales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        car_id INT NOT NULL UNIQUE,
        client_id INT NOT NULL,
        manager_id INT NOT NULL,
        price DECIMAL(8, 2) NOT NULL,
        payment_type ENUM ('cash', 'credit', 'leasing', 'card') NOT NULL DEFAULT 'card',
        contract_number VARCHAR(50) NOT NULL UNIQUE,
        comment TEXT NULL,
        sold_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_sales_car FOREIGN KEY (car_id) REFERENCES cars (id) ON DELETE RESTRICT,
        CONSTRAINT fk_sales_client FOREIGN KEY (client_id) REFERENCES users (id) ON DELETE RESTRICT,
        CONSTRAINT fk_sales_manager FOREIGN KEY (manager_id) REFERENCES users (id) ON DELETE RESTRICT,
        INDEX idx_sales_client (client_id),
        INDEX idx_sales_manager (manager_id),
        INDEX idx_sales_sold_at (sold_at)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- 9. activity_log — журнал действий
CREATE TABLE
    activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        action VARCHAR(100) NOT NULL,
        entity VARCHAR(50) NULL,
        entity_id INT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
        INDEX idx_log_user (user_id),
        INDEX idx_log_created (created_at),
        INDEX idx_log_entity (entity, entity_id)
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ПРЕДСТАВЛЕНИЕ ДЛЯ ОТЧЁТА О ПРОДАЖАХ (опционально)
CREATE
OR REPLACE VIEW sales_report AS
SELECT
    s.id AS sale_id,
    s.contract_number,
    s.sold_at,
    s.price AS sale_price,
    s.payment_type,
    c.id AS car_id,
    c.vin,
    m.name AS model_name,
    c.year AS car_year,
    cl.name AS client_name,
    cl.email AS client_email,
    mg.name AS manager_name
FROM
    sales s
    JOIN cars c ON c.id = s.car_id
    JOIN car_models m ON m.id = c.model_id
    JOIN users cl ON cl.id = s.client_id
    JOIN users mg ON mg.id = s.manager_id;