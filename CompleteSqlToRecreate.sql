CREATE DATABASE hotel_db;
USE hotel_db;

CREATE TABLE reservations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  check_in_date DATE NOT NULL,
  check_out_date DATE NOT NULL,
  number_of_rooms INT NOT NULL,
  number_of_adults INT NOT NULL,
  number_of_children INT DEFAULT 0,
  room_number VARCHAR(10) NOT NULL,
  total_cost DECIMAL(10,2) NOT NULL,
  confirmation_number VARCHAR(50) NOT NULL,
  guest_name VARCHAR(100) NOT NULL,
  guest_phone VARCHAR(20) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE rooms (
    room_number VARCHAR(10) PRIMARY KEY,
    price_per_night DECIMAL(10,2) NOT NULL,
    status ENUM('available', 'reserved') DEFAULT 'available'
);

INSERT INTO rooms (room_number, price_per_night, status) VALUES
('Room1', 100.00, 'available'),
('Room2', 100.00, 'available'),
('Room3', 100.00, 'available'),
('Room4', 100.00, 'available'),
('Room5', 100.00, 'available'),
('Room6', 100.00, 'available'),
('Room7', 100.00, 'available'),
('Room8', 100.00, 'available'),
('Room9', 100.00, 'available'),
('Room10', 100.00, 'available');

CREATE USER 'customer_user'@'localhost' IDENTIFIED BY 'passwordCUSto#33';
GRANT SELECT, INSERT, UPDATE ON hotel_db.reservations TO 'customer_user'@'localhost';
GRANT SELECT, UPDATE ON hotel_db.rooms TO 'customer_user'@'localhost';

CREATE USER 'admin_user'@'localhost' IDENTIFIED BY 'passwordADM@2348';
GRANT ALL PRIVILEGES ON hotel_db.* TO 'admin_user'@'localhost';
FLUSH PRIVILEGES;
