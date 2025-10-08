<?php
include("db_connect.php");


$password = "faculty123";
$passwordcorrect = false;


if(isset($_POST['check_password'])){
    if($_POST['password'] === $password){
        $passwordcorrect = true;
    } else {
        $passworderror = "Incorrect password!";
    }
}

if(isset($_POST['register'])){
    $name = $_POST['name'];
    $email = $_POST['email'];
    $event_id = $_POST['event_id'];

    $stmt_student = $conn->prepare("INSERT IGNORE INTO students (student_name,email) VALUES (?,?)");
    $stmt_student->bind_param("ss", $name, $email);
    $stmt_student->execute();

    $stmt_get = $conn->prepare("SELECT student_id FROM students WHERE email=?");
    $stmt_get->bind_param("s", $email);
    $stmt_get->execute();
    $student_id = $stmt_get->get_result()->fetch_assoc()['student_id'];

    $stmt_check = $conn->prepare("SELECT * FROM registrations WHERE student_id=? AND event_id=?");
    $stmt_check->bind_param("ii", $student_id, $event_id);
    $stmt_check->execute();

    if($stmt_check->get_result()->num_rows > 0){
        $reg_message = "<p style='color:red;'>You are already registered for this event!</p>";
    } else {

        $stmt_reg = $conn->prepare("INSERT INTO registrations (student_id,event_id) VALUES (?,?)");
        $stmt_reg->bind_param("ii", $student_id, $event_id);
        $stmt_reg->execute();
        $reg_message = "<p style='color:green;'>🎉 Registered successfully!</p>";
    }
}

if(isset($_POST['add_event']) && isset($_POST['event_name'])){
    $event_name = $conn->real_escape_string($_POST['event_name']);
    $event_date = $_POST['event_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $venue_id = (int)$_POST['venue_id'];
    $faculty_id = (int)$_POST['faculty_id'];
    $total_seats = (int)$_POST['total_seats'];

    $venue_result = $conn->query("SELECT capacity FROM venues WHERE venue_id=$venue_id");
    $venue_capacity = $venue_result->fetch_assoc()['capacity'];
    if($total_seats > $venue_capacity){
        $event_message = "<p style='color:red;'>Total seats cannot exceed venue capacity ($venue_capacity)!</p>";
    } else {
        $stmt_check = $conn->prepare("SELECT * FROM events WHERE event_name=? AND event_date=? AND start_time=?");
        $stmt_check->bind_param("sss", $event_name, $event_date, $start_time);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if($result_check->num_rows > 0){
            $event_message = "<p style='color:red;'>This event already exists on the selected date and time!</p>";
        } else {
            $stmt_insert = $conn->prepare("INSERT INTO events (event_name, event_date, start_time, end_time, venue_id, faculty_id, total_seats) VALUES (?,?,?,?,?,?,?)");
            $stmt_insert->bind_param("ssssiii", $event_name, $event_date, $start_time, $end_time, $venue_id, $faculty_id, $total_seats);
            if($stmt_insert->execute()){
                $event_message = "<p style='color:green;'>🎉 Event added successfully!</p>";
            } else {
                $event_message = "<p style='color:red;'>Error adding event: ".$conn->error."</p>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Event Dashboard</title>
    <style>
        body{ font-family: Arial; margin:30px; }
        .tab{ margin-bottom:20px; }
        .tab button{ padding:10px 20px; cursor:pointer; }
        .section{ display:none; background:#f5f5f5; padding:20px; border-radius:8px; margin-top:10px; }
        input, select, button{ width:100%; padding:8px; margin-top:5px; border-radius:4px; border:1px solid #ccc; }
        button{ background-color: #4CAF50; color:white; border:none; cursor:pointer }
        button:hover{ background-color:#45a049; }
        h2{ margin-top:0; }
    </style>
    <script>
        function showSection(id){
            document.getElementById('home').style.display='none';
            document.getElementById('add_event').style.display='none';
            document.getElementById(id).style.display='block';
        }
    </script>
</head>
<body>

<div class="tab">
    <button onclick="showSection('home')">Home (Student)</button>
    <button onclick="showSection('add_event')">Add New Event (only for faculty)</button>
</div>

<div id="home" class="section" style="display:block;">
    <h2>Register for Event</h2>
    <?php if(isset($reg_message)) echo $reg_message; ?>
    <form method="POST">
        <label>Name:</label>
        <input type="text" name="name" required>
        <label>Email:</label>
        <input type="email" name="email" required>
        <label>Select Event:</label>
        <select name="event_id" required>
            <option value="">-- Please select an event --</option>
            <?php
            $events = $conn->query("SELECT e.event_id,e.event_name,v.venue_name,e.total_seats,e.start_time,e.end_time,e.event_date
                                    FROM events e JOIN venues v ON e.venue_id=v.venue_id
                                    WHERE CONCAT(e.event_date,' ',e.start_time) >= NOW()
                                    ORDER BY e.event_date ASC");
            if($events && $events->num_rows > 0){
                while($row = $events->fetch_assoc()){
                    $count_result = $conn->query("SELECT COUNT(*) AS c FROM registrations WHERE event_id=".$row['event_id']);
                    $count = $count_result->fetch_assoc()['c'];
                    $remaining = $row['total_seats'] - $count;
                    if($remaining > 0){
                        $start = !empty($row['start_time']) ? $row['start_time'] : "N/A";
                        $end = !empty($row['end_time']) ? $row['end_time'] : "N/A";
                        echo "<option value='{$row['event_id']}'>{$row['event_name']} at {$row['venue_name']} ({$row['event_date']} {$start}-{$end}) Seats left: $remaining</option>";
                    }
                }
            } else {
                echo "<option disabled>No upcoming events available</option>";
            }
            ?>
        </select>
        <button type="submit" name="register">Register</button>
    </form>
</div>

<div id="add_event" class="section">
    <?php if(!$passwordcorrect): ?>
        <h2>Enter Admin Password</h2>
        <?php if(isset($password_error)) echo "<p style='color:red;'>$password_error</p>"; ?>
        <form method="POST">
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="check_password">Submit</button>
        </form>
    <?php else: ?>
        <h2>Add New Event</h2>
        <?php if(isset($event_message)) echo $event_message; ?>
        <form method="POST">
            <label>Event Name:</label>
            <input type="text" name="event_name" required>
            <label>Event Date:</label>
            <input type="date" name="event_date" required>
            <label>Start Time:</label>
            <input type="time" name="start_time" required>
            <label>End Time:</label>
            <input type="time" name="end_time" required>
            <label>Venue:</label>
            <select name="venue_id" required>
                <?php
                $venues = $conn->query("SELECT * FROM venues");
                if($venues && $venues->num_rows > 0){
                    while($v = $venues->fetch_assoc()){
                        echo "<option value='{$v['venue_id']}'>{$v['venue_name']} (Capacity: {$v['capacity']})</option>";
                    }
                }
                ?>
            </select>
            <label>Faculty:</label>
            <select name="faculty_id" required>
                <?php
                $faculty = $conn->query("SELECT * FROM faculty");
                if($faculty && $faculty->num_rows > 0){
                    while($f = $faculty->fetch_assoc()){
                        echo "<option value='{$f['faculty_id']}'>{$f['faculty_name']}</option>";
                    }
                }
                ?>
            </select>
            <label>Total Seats:</label>
            <input type="number" name="total_seats" min="1" required>
            <button type="submit" name="add_event">Add Event</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
