<?php
session_start();
include("db_connect.php");
$password = "faculty123";
$passworderror = "";
$event_message = "";

if (isset($_POST['check_password'])) {
    if ($_POST['password'] === $password) {
        $_SESSION['club_logged_in'] = true;
    } else {
        $passworderror = "Incorrect password!";
    }
}

$passwordcorrect = isset($_SESSION['club_logged_in']) && $_SESSION['club_logged_in'] === true;
$venues = $conn->query("SELECT * FROM venues");
$clubs = $conn->query("SELECT * FROM clubs");

if ($passwordcorrect && isset($_POST['event_name'])) {
    $event_name = $_POST['event_name'];
    $description = $_POST['description'];
    $event_date = $_POST['event_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $venue_id = $_POST['venue_id'];
    $club_id = $_POST['club_id'];
    $total_seats = $_POST['total_seats'];

    $venue_res = $conn->query("SELECT capacity FROM venues WHERE venue_id = $venue_id");
    $capacity = $venue_res->fetch_assoc()['capacity'];

    if ($total_seats > $capacity) {
        $event_message = " Total seats cannot exceed venue capacity ($capacity)";
    } else {

        $check_overlap = $conn->prepare("
            SELECT * FROM events 
            WHERE venue_id = ? AND event_date = ? 
            AND (
                (start_time <= ? AND end_time >= ?) OR 
                (start_time < ? AND end_time >= ?)
            )
        ");
        $check_overlap->bind_param("isssss", $venue_id, $event_date, $start_time, $start_time, $end_time, $end_time);
        $check_overlap->execute();
        $overlap_result = $check_overlap->get_result();

        if ($overlap_result->num_rows > 0) {
            $event_message = " Venue is already booked for the selected date and time!";
        } else {
            $stmt = $conn->prepare("
                INSERT INTO events 
                (event_name, description, event_date, start_time, end_time, venue_id, club_id, total_seats) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("sssssiii", $event_name, $description, $event_date, $start_time, $end_time, $venue_id, $club_id, $total_seats);

            if ($stmt->execute()) {
                $event_message = "Event added successfully!";
            } else {
                $event_message = "Error adding event: " . $stmt->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Club Dashboard</title>
    <style>
        body {
            background-image: url("https://dl.geu.ac.in/uploads/image/sGcygIQA-independence-day-celebration-8-jpg.webp");
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            padding-top: 40px;
        }
        .container {
            width: 100%;
            max-width: 700px;
            box-sizing: border-box;
        }
        .dashboard-form {
            background: rgba(255, 255, 255, 0.95);
            padding: 25px 30px;
            border-radius: 10px;
            width: 100%;
            box-sizing: border-box;
            margin-bottom: 30px;
        }
        h1, h2 { text-align: center; }
        input, select, button, textarea {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
            box-sizing: border-box;
            font-size: 1rem;
        }
        textarea { resize: vertical; min-height: 80px; }
        button {
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }
        button:hover { background-color: #45a049; }
        label { font-weight: bold; margin-top: 10px; display: block; }
        a { display: block; text-align: center; margin-bottom: 20px; color: #000; text-decoration: none; font-weight: bold; }
        a:hover { color: #4CAF50; }
        p.error { color: red; font-weight: bold; }
        p.success { color: green; font-weight: bold; text-align: center; }
    </style>
</head>
<body>
<div class="container">
    <h1>Club Dashboard</h1>
    <a href="student_dashboard.php">Switch to Student Dashboard</a>

    <?php if (!$passwordcorrect): ?>
        <form method="post" class="dashboard-form">
            <h2>Enter Club Password</h2>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="check_password">Submit</button>
            <?php if ($passworderror): ?>
                <p class="error"><?php echo $passworderror; ?></p>
            <?php endif; ?>
        </form>
    <?php else: ?>
        <form method="post" class="dashboard-form">
            <h2>Add New Event</h2>
            <?php if($event_message) echo "<p class='success'>$event_message</p>"; ?>

            <label>Event Name:</label>
            <input type="text" name="event_name" placeholder="Enter event name" required>

            <label>Event Description:</label>
            <textarea name="description" placeholder="Write about the event......." required></textarea>

            <label>Event Date:</label>
            <input type="date" name="event_date" required>

            <label>Start Time:</label>
            <input type="time" name="start_time" required>

            <label>End Time:</label>
            <input type="time" name="end_time" required>

            <label>Venue:</label>
            <select name="venue_id" required>
                <option value="">Select Venue</option>
                <?php
                if($venues && $venues->num_rows > 0){
                    $venues->data_seek(0);
                    while($v = $venues->fetch_assoc()){
                        echo "<option value='{$v['venue_id']}'>{$v['venue_name']} (Capacity: {$v['capacity']})</option>";
                    }
                }
                ?>
            </select>

            <label>Club:</label>
            <select name="club_id" required>
                <option value="">Select Club</option>
                <?php
                if($clubs && $clubs->num_rows > 0){
                    $clubs->data_seek(0);
                    while($c = $clubs->fetch_assoc()){
                        echo "<option value='{$c['club_id']}'>{$c['club_name']}</option>";
                    }
                }
                ?>
            </select>

            <label>Total Seats:</label>
            <input type="number" name="total_seats" placeholder="Enter total seats" min="1" required>

            <button type="submit">Add Event</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
