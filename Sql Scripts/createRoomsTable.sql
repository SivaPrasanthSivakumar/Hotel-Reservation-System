USE hotel_db;
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
('Room5', 100.00, 'available');
