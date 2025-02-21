<?php
session_start();

function getDatabaseConnection() {
    $dsn = "mysql:host=localhost;dbname=hotel_db;charset=utf8mb4";
    $username = "admin_user";
    $password = "passwordADM@2348";

    try {
        return new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

function authenticateAdmin($username, $password) {
    $admin_username = 'admin_user';
    $admin_password = 'passwordADM@2348';
    return $username === $admin_username && $password === $admin_password;
}

function handleLogin() {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $admin_user = $_POST["username"];
        $admin_pass = $_POST["password"];

        if (authenticateAdmin($admin_user, $admin_pass)) {
            $_SESSION['admin_logged_in'] = true;
            header("Location: admin.php");
            exit;
        } else {
            echo "<p>Invalid login credentials. Please try again.</p>";
        }
    }
}

function checkAdminSession() {
    if (!isset($_SESSION['admin_logged_in'])) {
        displayLoginForm();
        exit;
    }
}

function handleLogout() {
    if (isset($_GET['action']) && $_GET['action'] === 'logout') {
        session_destroy();
        header("Location: index.html");
        exit;
    }
}

function getReservations() {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->query("SELECT * FROM reservations");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function cancelReservation($reservationId) {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("DELETE FROM reservations WHERE id = ?");
    $stmt->execute([$reservationId]);
}

function getRooms() {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->query("SELECT * FROM rooms");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function updateRoom($roomId, $roomData) {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("UPDATE rooms SET price_per_night = ?, status = ? WHERE room_number = ?");
    $stmt->execute([$roomData['price_per_night'], $roomData['status'], $roomId]);
}

function handleAdminActions() {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        if (isset($_POST['cancel_reservation'])) {
            cancelReservation($_POST['reservation_id']);
        } elseif (isset($_POST['update_room'])) {
            updateRoom($_POST['room_number'], $_POST);
        }
    }
}

function handleAjaxRequests() {
    if (isset($_GET['action'])) {
        if ($_GET['action'] === 'get_reservations') {
            $reservations = getReservations();
            foreach ($reservations as $reservation) {
                echo "<tr>
                        <td>{$reservation['id']}</td>
                        <td>{$reservation['guest_name']}</td>
                        <td>{$reservation['room_number']}</td>
                        <td>{$reservation['check_in_date']}</td>
                        <td>{$reservation['check_out_date']}</td>
                        <td>
                            <form class='cancel-reservation-form' method='POST'>
                                <input type='hidden' name='reservation_id' value='{$reservation['id']}'>
                                <button type='submit' name='cancel_reservation' class='btn btn-danger'>Cancel</button>
                            </form>
                        </td>
                      </tr>";
            }
            exit;
        } elseif ($_GET['action'] === 'get_rooms') {
            $rooms = getRooms();
            foreach ($rooms as $room) {
                echo "<tr>
                        <td>{$room['room_number']}</td>
                        <td>{$room['price_per_night']}</td>
                        <td>{$room['status']}</td>
                        <td>
                            <form class='update-room-form' method='POST'>
                                <input type='hidden' name='room_number' value='{$room['room_number']}'>
                                <input type='number' name='price_per_night' value='{$room['price_per_night']}' required>
                                <select name='status' required>
                                    <option value='available' " . ($room['status'] === 'available' ? 'selected' : '') . ">Available</option>
                                    <option value='reserved' " . ($room['status'] === 'reserved' ? 'selected' : '') . ">Reserved</option>
                                </select>
                                <button type='submit' name='update_room' class='btn btn-primary'>Update</button>
                            </form>
                        </td>
                      </tr>";
            }
            exit;
        }
    }
}

function displayAdminPage() {
    if (!isset($_SESSION['admin_logged_in'])) {
        displayLoginForm();
        return;
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Dashboard</title>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
        <link rel="stylesheet" href="styles.css"> 
    </head>
    <body>
        <?php displayNavbar(); ?>
        <div class="container mt-5">
            <?php displayReservationsManagement(); ?>
            <?php displayRoomManagement(); ?>
        </div>
        <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    </body>
    </html>
    <?php
}

function displayLoginForm() {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login</title>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    </head>
    <body class="admin">
        <div class="container mt-5">
            <h2 class="text-center">Admin Login</h2>
            <form method="POST" action="admin.php">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required />
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required />
                </div>
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
}

function displayNavbar() {
    ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <a class="navbar-brand" href="#">Admin Dashboard</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="?action=logout">Logout</a>
                </li>
            </ul>
        </div>
    </nav>
    <?php
}

function displayReservationsManagement() {
    $reservations = getReservations();
    ?>
    <h2>Reservations Management</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Guest Name</th>
                <th>Room</th>
                <th>Check-in</th>
                <th>Check-out</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reservations as $reservation): ?>
                <tr>
                    <td><?= $reservation['id'] ?></td>
                    <td><?= $reservation['guest_name'] ?></td>
                    <td><?= $reservation['room_number'] ?></td>
                    <td><?= $reservation['check_in_date'] ?></td>
                    <td><?= $reservation['check_out_date'] ?></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="reservation_id" value="<?= $reservation['id'] ?>">
                            <button type="submit" name="cancel_reservation" class="btn btn-danger">Cancel</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function displayRoomManagement() {
    $rooms = getRooms();
    ?>
    <h2>Room Management</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Room Number</th>
                <th>Price per Night</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rooms as $room): ?>
                <tr>
                    <td><?= $room['room_number'] ?></td>
                    <td><?= $room['price_per_night'] ?></td>
                    <td><?= $room['status'] ?></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="room_number" value="<?= $room['room_number'] ?>">
                            <input type="number" name="price_per_night" value="<?= $room['price_per_night'] ?>" required>
                            <select name="status" required>
                                <option value="available" <?= $room['status'] === 'available' ? 'selected' : '' ?>>Available</option>
                                <option value="reserved" <?= $room['status'] === 'reserved' ? 'selected' : '' ?>>Reserved</option>
                            </select>
                            <button type="submit" name="update_room" class="btn btn-primary">Update</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

handleLogin();
checkAdminSession();
handleLogout();
handleAdminActions();
handleAjaxRequests();
displayAdminPage();
?>