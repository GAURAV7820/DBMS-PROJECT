<?php
include("db_connect.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Faculty Event Report</title>
<style>
  body {
    font-family: "Segoe UI", Arial, sans-serif;
    background: linear-gradient(120deg, #f0f4ff, #e7f0ff);
    margin: 0;
    padding: 0;
    color: #333;
  }

  header {
    background-color:
    color: white;
    padding: 20px 0;
    text-align: center;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
  }

  header h1 {
    margin: 0;
    font-size: 2rem;
    letter-spacing: 1px;
  }

  .container {
    max-width: 1100px;
    margin: 40px auto;
    padding: 0 20px;
  }

  .section {
    background: #fff;
    border-radius: 12px;
    padding: 25px 30px;
    margin-bottom: 30px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
  }

  .section:hover {
    transform: scale(1.01);
    box-shadow: 0 6px 16px rgba(0,0,0,0.1);
  }

  h2 {
    color: #0056b3;
    border-left: 6px solid #007BFF;
    padding-left: 10px;
    font-size: 1.3rem;
    margin-bottom: 10px;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
    border-radius: 8px;
    overflow: hidden;
  }

  th, td {
    padding: 12px 15px;
    text-align: center;
    font-size: 15px;
  }

  th {
    background: #000;
    color: white;
    font-weight: 600;
    text-transform: uppercase;
  }

  tr:nth-child(even) {
    background-color: #f8f9fa;
  }

  tr:hover {
    background-color: #e9f2ff;
    transition: 0.2s ease;
  }

  .no-events {
    text-align: center;
    padding: 20px;
    color: #888;
    font-style: italic;
  }

  .back-btn {
    display: inline-block;
    text-decoration: none;
    background-color: #007BFF;
    color: white;
    padding: 10px 20px;
    border-radius: 6px;
    margin: 25px auto;
    transition: background-color 0.3s ease;
  }

  .back-btn:hover {
    background-color: #0056b3;
  }

  footer {
    text-align: center;
    padding: 15px;
    font-size: 0.9rem;
    color: #666;
  }

  footer span {
    color: #007BFF;
    font-weight: 600;
  }
</style>
</head>
<body>

<header>
  <h1> Past Event Summary</h1>
</header>

<div class="container">
  <div class="section">
  <?php
  $today = date("Y-m-d");
  $oneMonthAgo = date("Y-m-d", strtotime("-1 month"));
  $twoMonthsAgo = date("Y-m-d", strtotime("-2 months"));
  $sixMonthsAgo = date("Y-m-d", strtotime("-6 months"));

  function showEvents($conn, $fromDate, $toDate, $title) {
      echo "<h2>$title</h2>";
      $query = "
        SELECT e.event_name, e.event_date, 
               COUNT(DISTINCT r.student_id) AS total_participants,
               ROUND(AVG(f.rating),1) AS avg_rating
        FROM events e
        LEFT JOIN registrations r ON e.event_id = r.event_id
        LEFT JOIN feedback f ON e.event_id = f.event_id
        WHERE e.event_date BETWEEN '$fromDate' AND '$toDate'
        GROUP BY e.event_id
        ORDER BY e.event_date DESC
      ";
      $result = $conn->query($query);

      if ($result && $result->num_rows > 0) {
          echo "<table>
                  <tr>
                    <th>Event Name</th>
                    <th>Date</th>
                    <th>Participants</th>
                    <th>Average Rating</th>
                  </tr>";
          while ($row = $result->fetch_assoc()) {
              echo "<tr>
                      <td>{$row['event_name']}</td>
                      <td>{$row['event_date']}</td>
                      <td>{$row['total_participants']}</td>
                      <td>" . ($row['avg_rating'] ?? '-') . "</td>
                    </tr>";
          }
          echo "</table><br>";
      } else {
          echo "<div class='no-events'>No events found in this period.</div>";
      }
  }

  showEvents($conn, $oneMonthAgo, $today, " Events in the Last 1 Month");
  showEvents($conn, $twoMonthsAgo, $oneMonthAgo, "Events from 1–2 Months Ago");
  showEvents($conn, $sixMonthsAgo, $twoMonthsAgo, "Events from 2–6 Months Ago");
  ?>
  </div>

  <div style="text-align:center;">
    <a href="faculty_dashboard.php" class="back-btn">⬅ Back to Faculty Dashboard</a>
  </div>
</div>

<footer>
  © <?php echo date("Y"); ?> <span>UniVerse Event Portal</span> | Faculty Analytics Panel
</footer>

</body>
</html>
