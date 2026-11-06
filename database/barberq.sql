CREATE DATABASE IF NOT EXISTS barberq CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE barberq;

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS reservations;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE services (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  description TEXT NULL,
  price INT UNSIGNED NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE reservations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  service_id INT UNSIGNED NOT NULL,
  reservation_date DATE NOT NULL,
  reservation_time TIME NOT NULL,
  status ENUM('BOOKED','WAITING','IN_SERVICE','COMPLETED','CANCELLED') NOT NULL DEFAULT 'BOOKED',
  queue_number INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_reservation_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_reservation_service FOREIGN KEY (service_id) REFERENCES services(id),
  UNIQUE KEY uq_queue_per_day (reservation_date, queue_number),
  KEY idx_reservation_slot (reservation_date, reservation_time),
  KEY idx_reservation_status (status),
  KEY idx_reservation_user (user_id)
) ENGINE=InnoDB;
INSERT INTO users(name,email,password_hash,role) VALUES
('Admin Barbershop','admin@barbershop.local','$2y$12$eIomOZdrAKtgqMSBhPQ3uOG2C4268K1tf7U4JHB9KZ4ruRol2QUue','admin');

INSERT INTO services(name,description,price,active) VALUES
('Haircut','Potong rambut reguler.',35000,1),
('Haircut + Wash','Potong rambut dan cuci rambut.',50000,1),
('Shaving','Perapihan kumis dan jenggot.',20000,1);
