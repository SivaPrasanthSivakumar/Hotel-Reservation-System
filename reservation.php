<?php
session_start();
header('Content-Type: application/json');

$dsn = "mysql:host=localhost;dbname=hotel_db;charset=utf8mb4";
$dbUsername = "customer_user"; 
$dbPassword = "passwordCUSto#33"; 

try {
    $pdo = new PDO($dsn, $dbUsername, $dbPassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Process form
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        // Get user inputs
        $checkInDate = $_POST["check_in"] ?? '';
        $checkOutDate = $_POST["check_out"] ?? '';
        $numberOfRooms = (int)($_POST["num_rooms"] ?? 0);
        $numberOfAdults = (int)($_POST["num_adults"] ?? 0);
        $numberOfChildren = (int)($_POST["num_children"] ?? 0);
        $guestName = trim($_POST["guest_name"] ?? '');
        $guestPhone = trim($_POST["guest_phone"] ?? '');

        // check-in date > today
        if (strtotime($checkInDate) < strtotime(date("Y-m-d"))) {
            echo json_encode(["error" => "Check-in date cannot be in the past."]);
            exit;
        }

        // check out date > check in date
        if (strtotime($checkOutDate) <= strtotime($checkInDate)) {
            echo json_encode(["error" => "Check-out date must be later than check-in date."]);
            exit;
        }

        // Ensure numbers are positive
        if ($numberOfRooms <= 0 || $numberOfAdults <= 0) {
            echo json_encode(["error" => "Number of rooms and adults must be at least 1."]);
            exit;
        }

        // Check room availability
        $stmt = $pdo->query("SELECT room_number FROM rooms WHERE status = 'available' LIMIT $numberOfRooms");
        $availableRooms = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (count($availableRooms) < $numberOfRooms) {
            echo json_encode(["error" => "Not enough available rooms."]);
            exit;
        }

        // Room availability for guest stay
        foreach ($availableRooms as $roomNumber) {
            $stmt = $pdo->prepare("SELECT * FROM reservations WHERE room_number = :room_number AND 
                                   ((:check_in BETWEEN check_in_date AND check_out_date) OR 
                                    (:check_out BETWEEN check_in_date AND check_out_date))");
            $stmt->execute([':room_number' => $roomNumber, ':check_in' => $checkInDate, ':check_out' => $checkOutDate]);
            if ($stmt->rowCount() > 0) {
                echo json_encode(["error" => "Room $roomNumber is already reserved for the selected dates."]);
                exit;
            }
        }

        // checking availability
        if (isset($_POST['check_availability'])) {
            echo json_encode(["available" => true]);
            exit;
        }

        // total nights
        $numberOfNights = (strtotime($checkOutDate) - strtotime($checkInDate)) / (60 * 60 * 24);

        // Base price per night per room
        $pricePerNight = 100.00;
        
        // Child discount 5% for each child
        $childDiscount = ($numberOfChildren * $pricePerNight * 0.05);

        //total cost
        $totalCost = ($numberOfRooms * $pricePerNight * $numberOfNights);
        $finalCost = $totalCost - $childDiscount;
        if ($finalCost < 0) {
            $finalCost = 0;
        }

        // Generate a unique confirmation number
        $confirmationNumber = uniqid("CONF-");

        // Insert to database
        $sql = "INSERT INTO reservations 
                (check_in_date, check_out_date, number_of_rooms, number_of_adults, number_of_children, room_number, total_cost, confirmation_number, guest_name, guest_phone) 
                VALUES 
                (:check_in, :check_out, :num_rooms, :num_adults, :num_children, :room_number, :total_cost, :confirmation_number, :guest_name, :guest_phone)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":check_in" => $checkInDate,
            ":check_out" => $checkOutDate,
            ":num_rooms" => $numberOfRooms,
            ":num_adults" => $numberOfAdults,
            ":num_children" => $numberOfChildren,
            ":room_number" => substr(implode(", ", $availableRooms), 0, 10), // Limit room_number to 10 characters
            ":total_cost" => $totalCost,
            ":confirmation_number" => $confirmationNumber,
            ":guest_name" => $guestName,
            ":guest_phone" => $guestPhone
        ]);

        // Update room status to reserved
        foreach ($availableRooms as $roomNumber) {
            $stmt = $pdo->prepare("UPDATE rooms SET status = 'reserved' WHERE room_number = :room_number");
            $stmt->execute([':room_number' => $roomNumber]);
        }

        // Return booking details to the guest
        echo json_encode([
            "booking" => [
                "confirmation_number" => $confirmationNumber,
                "total_cost" => $totalCost,
                "child_discount" => $childDiscount,
                "final_cost" => $finalCost
            ]
        ]);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(["error" => "Database connection failed: " . $e->getMessage()]);
    exit;
}
?>