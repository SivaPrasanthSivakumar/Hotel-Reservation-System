CREATE DATABASE hotel_db;
USE hotel_db;

CREATE TABLE reservations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  check_in_date DATE NOT NULL,
  check_out_date DATE NOT NULL,
  number_of_rooms INT NOT NULL,
  number_of_adults INT NOT NULL,
  number_of_children INT DEFAULT 0,
  room_type VARCHAR(500) NOT NULL,
  total_cost DECIMAL(10,2) NOT NULL,
  confirmation_number VARCHAR(50) NOT NULL,
  guest_name VARCHAR(100) NOT NULL,
  guest_phone VARCHAR(20) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE rooms (
    room_type VARCHAR(50) PRIMARY KEY,
    price_per_night DECIMAL(10,2) NOT NULL,
    status ENUM('available', 'reserved') DEFAULT 'available'
);

INSERT INTO rooms (room_type, price_per_night, status) VALUES
('Economy Room 1', 50.00, 'available'),
('Economy Room 2', 50.00, 'available'),
('Economy Room 3', 50.00, 'available'),
('Economy Room 4', 50.00, 'available'),
('Regular Room 5', 100.00, 'available'),
('Regular Room 6', 100.00, 'available'),
('Regular Room 7', 100.00, 'available'),
('Luxury Room 8', 1000.00, 'available'),
('Luxury Room 9', 1000.00, 'available'),
('Luxury Room 10', 1000.00, 'available');

CREATE USER 'customer_user'@'localhost' IDENTIFIED BY 'passwordCUSto#33';
GRANT SELECT, INSERT, UPDATE ON hotel_db.reservations TO 'customer_user'@'localhost';
GRANT SELECT, UPDATE ON hotel_db.rooms TO 'customer_user'@'localhost';

CREATE USER 'admin_user'@'localhost' IDENTIFIED BY 'passwordADM@2348';
GRANT ALL PRIVILEGES ON hotel_db.* TO 'admin_user'@'localhost';
FLUSH PRIVILEGES;
