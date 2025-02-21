<?php
session_start();
header('Content-Type: application/json');

$dsn = "mysql:host=localhost;dbname=hotel_db;charset=utf8mb4";
$username = "customer_user";
$password = "passwordCUSto#33";

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        handlePostRequest($pdo);
    }
} catch (PDOException $e) {
    echo json_encode(["error" => "Database connection failed: " . $e->getMessage()]);
    exit;
}

function handlePostRequest($pdo) {
    $inputs = getUserInputs();
    validateInputs($inputs);
    $rooms = checkRoomAvailability($pdo, $inputs['num_rooms']);
    checkRoomReservations($pdo, $rooms, $inputs['check_in'], $inputs['check_out']);

    if (isset($_POST['check_availability'])) {
        echo json_encode(["available" => true]);
        exit;
    }

    $costDetails = calculateCost($pdo, $rooms, $inputs['num_children'], $inputs['check_in'], $inputs['check_out']);
    $confirmation_number = uniqid("CONF-");

    saveReservation($pdo, $inputs, $rooms, $costDetails['total_cost'], $confirmation_number);
    updateRoomStatus($pdo, $rooms);

    echo json_encode([
        "booking" => [
            "confirmation_number" => $confirmation_number,
            "total_cost" => $costDetails['total_cost'],
            "child_discount" => $costDetails['child_discount'],
            "final_cost" => $costDetails['final_cost']
        ]
    ]);
    exit;
}

function getUserInputs() {
    return [
        'check_in' => $_POST["check_in"] ?? '',
        'check_out' => $_POST["check_out"] ?? '',
        'num_rooms' => (int)($_POST["num_rooms"] ?? 0),
        'num_adults' => (int)($_POST["num_adults"] ?? 0),
        'num_children' => (int)($_POST["num_children"] ?? 0),
        'guest_name' => trim($_POST["guest_name"] ?? ''),
        'guest_phone' => trim($_POST["guest_phone"] ?? '')
    ];
}

function validateInputs($inputs) {
    if (strtotime($inputs['check_in']) < strtotime(date("Y-m-d"))) {
        echo json_encode(["error" => "Check-in date cannot be in the past."]);
        exit;
    }

    if (strtotime($inputs['check_out']) <= strtotime($inputs['check_in'])) {
        echo json_encode(["error" => "Check-out date must be later than check-in date."]);
        exit;
    }

    if ($inputs['num_adults'] <= 0) {
        echo json_encode(["error" => "Number of adults must be at least 1."]);
        exit;
    }
}

function checkRoomAvailability($pdo, $num_rooms) {
    $stmt = $pdo->query("SELECT room_type FROM rooms WHERE status = 'available' LIMIT $num_rooms");
    $rooms = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (count($rooms) < $num_rooms) {
        echo json_encode(["error" => "Not enough available rooms."]);
        exit;
    }
    return $rooms;
}

function checkRoomReservations($pdo, $rooms, $check_in, $check_out) {
    foreach ($rooms as $room) {
        $stmt = $pdo->prepare("SELECT * FROM reservations WHERE room_type = :room_type AND 
                               ((:check_in BETWEEN check_in_date AND check_out_date) OR 
                                (:check_out BETWEEN check_in_date AND check_out_date))");
        $stmt->execute([':room_type' => $room, ':check_in' => $check_in, ':check_out' => $check_out]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(["error" => "Room $room is already reserved for the selected dates."]);
            exit;
        }
    }
}

function calculateCost($pdo, $rooms, $num_children, $check_in, $check_out) {
    $num_nights = (strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24);
    $total_cost = 0;

    foreach ($rooms as $room) {
        $stmt = $pdo->prepare("SELECT price_per_night FROM rooms WHERE room_type = :room_type");
        $stmt->execute([':room_type' => $room]);
        $price_per_night = $stmt->fetchColumn();
        $total_cost += $price_per_night * $num_nights;
    }

    $child_discount = min($num_children * $price_per_night * 0.05, $total_cost);
    $final_cost = $total_cost - $child_discount;

    return [
        'total_cost' => $total_cost,
        'child_discount' => $child_discount,
        'final_cost' => $final_cost
    ];
}

function saveReservation($pdo, $inputs, $rooms, $total_cost, $confirmation_number) {
    $sql = "INSERT INTO reservations 
            (check_in_date, check_out_date, number_of_rooms, number_of_adults, number_of_children, room_type, total_cost, confirmation_number, guest_name, guest_phone) 
            VALUES 
            (:check_in, :check_out, :num_rooms, :num_adults, :num_children, :room_type, :total_cost, :confirmation_number, :guest_name, :guest_phone)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ":check_in" => $inputs['check_in'],
        ":check_out" => $inputs['check_out'],
        ":num_rooms" => $inputs['num_rooms'],
        ":num_adults" => $inputs['num_adults'],
        ":num_children" => $inputs['num_children'],
        ":room_type" => substr(implode(", ", $rooms), 0, 10),
        ":total_cost" => $total_cost,
        ":confirmation_number" => $confirmation_number,
        ":guest_name" => $inputs['guest_name'],
        ":guest_phone" => $inputs['guest_phone']
    ]);
}

function updateRoomStatus($pdo, $rooms) {
    foreach ($rooms as $room) {
        $stmt = $pdo->prepare("UPDATE rooms SET status = 'reserved' WHERE room_type = :room_type");
        $stmt->execute([':room_type' => $room]);
    }
}
?>