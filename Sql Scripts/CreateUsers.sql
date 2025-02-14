USE hotel_db;
CREATE USER 'customer_user'@'localhost' IDENTIFIED BY 'passwordCUSto#33';
GRANT SELECT, INSERT,UPDATE ON hotel_db.reservations TO 'customer_user'@'localhost';
GRANT SELECT ON hotel_db.rooms TO 'customer_user'@'localhost';


CREATE USER 'admin_user'@'localhost' IDENTIFIED BY 'passwordADM@2348';
GRANT ALL PRIVILEGES ON hotel_db.* TO 'admin_user'@'localhost';
FLUSH PRIVILEGES;
