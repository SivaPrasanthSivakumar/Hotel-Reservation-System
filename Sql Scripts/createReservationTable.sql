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
