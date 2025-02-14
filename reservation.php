<?php
session_start();
header('Content-Type: application/json');

// Database connection using PDO
$dsn = "mysql:host=localhost;dbname=hotel_db;charset=utf8mb4";
$username = "customer_user"; // Change to a user with appropriate permissions
$password = "passwordCUSto#33"; // Change to the corresponding password

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Process form submission
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        // Get user inputs
        $check_in = $_POST["check_in"] ?? '';
        $check_out = $_POST["check_out"] ?? '';
        $num_rooms = (int)($_POST["num_rooms"] ?? 0);
        $num_adults = (int)($_POST["num_adults"] ?? 0);
        $num_children = (int)($_POST["num_children"] ?? 0);
        $guest_name = trim($_POST["guest_name"] ?? '');
        $guest_phone = trim($_POST["guest_phone"] ?? '');

        // Ensure check-in date is today or later
        if (strtotime($check_in) < strtotime(date("Y-m-d"))) {
            echo json_encode(["error" => "Check-in date cannot be in the past."]);
            exit;
        }

        // Validate dates
        if (strtotime($check_out) <= strtotime($check_in)) {
            echo json_encode(["error" => "Check-out date must be later than check-in date."]);
            exit;
        }

        // Ensure numbers are positive
        if ($num_rooms <= 0 || $num_adults <= 0) {
            echo json_encode(["error" => "Number of rooms and adults must be at least 1."]);
            exit;
        }

        // Check room availability
        $stmt = $pdo->query("SELECT room_number FROM rooms WHERE status = 'available' LIMIT $num_rooms");
        $rooms = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (count($rooms) < $num_rooms) {
            echo json_encode(["error" => "Not enough available rooms."]);
            exit;
        }

        // Room availability check
        foreach ($rooms as $room) {
            $stmt = $pdo->prepare("SELECT * FROM reservations WHERE room_number = :room_number AND 
                                   ((:check_in BETWEEN check_in_date AND check_out_date) OR 
                                    (:check_out BETWEEN check_in_date AND check_out_date))");
            $stmt->execute([':room_number' => $room, ':check_in' => $check_in, ':check_out' => $check_out]);
            if ($stmt->rowCount() > 0) {
                echo json_encode(["error" => "Room $room is already reserved for the selected dates."]);
                exit;
            }
        }

        // If checking availability, return success
        if (isset($_POST['check_availability'])) {
            echo json_encode(["available" => true]);
            exit;
        }

        // Calculate total nights
        $num_nights = (strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24);

        // Base price per night per room
        $price_per_night = 100.00;
        
        $child_discount = ($num_children * $price_per_night * 0.05);

        // Calculate total cost
        $total_cost = ($num_rooms * $price_per_night * $num_nights);
        $final_cost = $total_cost - $child_discount;
        if ($final_cost < 0) {
            $final_cost = 0;
        }

        // Generate a unique confirmation number
        $confirmation_number = uniqid("CONF-");

        // Insert into the database
        $sql = "INSERT INTO reservations 
                (check_in_date, check_out_date, number_of_rooms, number_of_adults, number_of_children, room_number, total_cost, confirmation_number, guest_name, guest_phone) 
                VALUES 
                (:check_in, :check_out, :num_rooms, :num_adults, :num_children, :room_number, :total_cost, :confirmation_number, :guest_name, :guest_phone)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":check_in" => $check_in,
            ":check_out" => $check_out,
            ":num_rooms" => $num_rooms,
            ":num_adults" => $num_adults,
            ":num_children" => $num_children,
            ":room_number" => substr(implode(", ", $rooms), 0, 10), // Limit room_number to 10 characters
            ":total_cost" => $total_cost,
            ":confirmation_number" => $confirmation_number,
            ":guest_name" => $guest_name,
            ":guest_phone" => $guest_phone
        ]);

        // Update room status to reserved
        foreach ($rooms as $room) {
            $stmt = $pdo->prepare("UPDATE rooms SET status = 'reserved' WHERE room_number = :room_number");
            $stmt->execute([':room_number' => $room]);
        }

        // Return booking details
        echo json_encode([
            "booking" => [
                "confirmation_number" => $confirmation_number,
                "total_cost" => $total_cost,
                "child_discount" => $child_discount,
                "final_cost" => $final_cost
            ]
        ]);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(["error" => "Database connection failed: " . $e->getMessage()]);
    exit;
}
?>