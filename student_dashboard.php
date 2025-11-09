<?php
session_start();
include("db_connect.php");

if (!isset($_SESSION['student_email'])) {
    header("Location: student_login.php");
    exit();
}

$reg_message = "";


if (isset($_POST['register'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $event_id = $_POST['event_id'];

    
    $student_insert = $conn->prepare("INSERT IGNORE INTO students (student_name,email) VALUES (?,?)");
    $student_insert->bind_param("ss", $name, $email);
    $student_insert->execute();

    
    $student_query = $conn->prepare("SELECT student_id FROM students WHERE email=?");
    $student_query->bind_param("s", $email);
    $student_query->execute();
    $student_id = $student_query->get_result()->fetch_assoc()['student_id'];

   
    $student_check = $conn->prepare("SELECT * FROM registrations WHERE student_id=? AND event_id=?");
    $student_check->bind_param("ii", $student_id, $event_id);
    $student_check->execute();

    if ($student_check->get_result()->num_rows > 0) {
        $reg_message = "<p style='color:red;'>You are already registered for this event!</p>";
    } else {
        $stmt_reg = $conn->prepare("INSERT INTO registrations (student_id,event_id) VALUES (?,?)");
        $stmt_reg->bind_param("ii", $student_id, $event_id);
        $stmt_reg->execute();
        $reg_message = "<p style='color:green;'>Registered successfully!</p>";
    }

    
    $_SESSION['student_email'] = $email;
}


$email = isset($_SESSION['student_email']) ? $_SESSION['student_email'] : ($_POST['email'] ?? null);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Dashboard</title>
  <style>
    body {  
        font-family: Arial, sans-serif;
        margin: 30px;
        background-image: url('https://dl.geu.ac.in/uploads/image/qXNfrGmJ-unleash-your-potential-at-graphic-era-university.jpg');
        background-size: cover;
        background-repeat: no-repeat;
        background-attachment: fixed;
        background-position: center;
        color: #333;
    }
    .section { background:rgba(245,245,245,0.9); padding:20px; border-radius:8px; margin-top:30px; }
    input, select, button, textarea { width:100%; padding:8px; margin-top:5px; border-radius:4px; border:1px solid #ccc; }
    button { background-color:#4CAF50; color:white; border:none; cursor:pointer; }
    button:hover { background-color:#45a049; }
    #event_description { padding:10px; background:#e0e0e0; border-radius:4px; min-height:50px; margin-top:5px; }
    table { border-collapse: collapse; width:100%; margin-top:10px; }
    th, td { border:1px solid #ddd; padding:8px; text-align:left; }
    th { background-color:#007BFF; color:white; }
  </style>
</head>
<body>
  <h1>🎓 Student Dashboard</h1>
  <p>Welcome, <?php echo htmlspecialchars($_SESSION['student_name']); ?>! 
  <a href="logout.php" style="color:red; text-decoration:none; font-weight:bold;">Logout</a>
</p>

  <?php if ($reg_message) echo $reg_message; ?>

  <div class="section">
    <h2>Register for Event</h2>
    <form method="POST">
      <label>Name:</label>
      <input type="text" name="name" required>

      <label>Email:</label>
      <input type="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>

      <label>Select Event:</label>
      <select name="event_id" required>
        <option value="">-- Please select an event --</option>
        <?php
        $events = $conn->query("
            SELECT e.event_id, e.event_name, e.description, v.venue_name, e.total_seats, e.start_time, e.end_time, e.event_date
            FROM events e 
            JOIN venues v ON e.venue_id=v.venue_id
            WHERE TIMESTAMP(e.event_date, e.start_time) >= NOW()
            ORDER BY e.event_date 
        ");
        if ($events && $events->num_rows > 0) {
            while ($row = $events->fetch_assoc()) {
                $count_result = $conn->query("SELECT COUNT(reg_id) AS c FROM registrations WHERE event_id=".$row['event_id']);
                $count = $count_result->fetch_assoc()['c'];
                $remaining = $row['total_seats'] - $count;
                if ($remaining > 0) {
                    $start = !empty($row['start_time']) ? $row['start_time'] : "N/A";
                    $end = !empty($row['end_time']) ? $row['end_time'] : "N/A";
                    echo "<option value='{$row['event_id']}' data-desc='".htmlspecialchars($row['description'])."'>
                              {$row['event_name']} at {$row['venue_name']} ({$row['event_date']} {$start}-{$end}) Seats left: $remaining
                          </option>";
                }
            }
        } else {
            echo "<option disabled>No upcoming events available</option>";
        }
        ?>
      </select>

      <label>Event Details:</label>
      <div id="event_description">Select an event to see details.</div>

      <button type="submit" name="register">Register</button>
    </form>
  </div>

  <?php
  if ($email) {
      $today = date("Y-m-d");

      $student_query = $conn->prepare("SELECT student_id FROM students WHERE email=?");
      $student_query->bind_param("s", $email);
      $student_query->execute();
      $student_id = $student_query->get_result()->fetch_assoc()['student_id'];

      $feedback_events = $conn->query("
          SELECT e.event_id, e.event_name, e.event_date, 
                 COALESCE(f.feedback_id, 0) AS feedback_given
          FROM registrations r
          JOIN events e ON e.event_id = r.event_id
          LEFT JOIN feedback f ON f.event_id = e.event_id AND f.student_id = r.student_id
          WHERE r.student_id = $student_id AND e.event_date < '$today'
          ORDER BY e.event_date DESC
      ");

      if ($feedback_events && $feedback_events->num_rows > 0) {
          echo "<div class='section'>";
          echo "<h2>📝 Submit Feedback for Completed Events</h2>";
          echo "<table>
                  <tr><th>Event</th><th>Date</th><th>Status</th></tr>";
          while ($row = $feedback_events->fetch_assoc()) {
              echo "<tr>
                      <td>{$row['event_name']}</td>
                      <td>{$row['event_date']}</td>";
              if ($row['feedback_given']) {
                  echo "<td style='color:green;'> Feedback Submitted</td>";
              } else {
                  echo "<td><button onclick=\"window.location.href='http://localhost:3000/feedback.html?student_id=$student_id&event_id={$row['event_id']}'\">Give Feedback</button></td>";
              }
              echo "</tr>";
          }
          echo "</table></div>";
      }
  }
  ?>

  <script>
    document.querySelector('select[name="event_id"]').addEventListener('change', function(){
        const desc = this.selectedOptions[0].dataset.desc || "No description available.";
        document.getElementById('event_description').innerText = desc;
    });
  </script>
</body>
</html>
