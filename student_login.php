<?php
session_start();
include("db_connect.php");

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $name = trim($_POST['name']);

    if (!empty($email) && !empty($name)) {
        $stmt = $conn->prepare("SELECT student_id FROM students WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            $insert = $conn->prepare("INSERT INTO students (student_name, email) VALUES (?, ?)");
            $insert->bind_param("ss", $name, $email);
            $insert->execute();
        }

        $_SESSION['student_email'] = $email;
        $_SESSION['student_name'] = $name;
        header("Location: student_dashboard.php");
        exit();
    } else {
        $message = "Please enter your name and email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Login - UniVerse</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: linear-gradient(to right, #007bff, #00c6ff);
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }
    .login-container {
      background: white;
      padding: 40px;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
      width: 350px;
      text-align: center;
    }
    h1 { color: #007bff; margin-bottom: 20px; }
    input {
      width: 100%;
      padding: 10px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 5px;
    }
    button {
      width: 100%;
      padding: 10px;
      background: #007bff;
      border: none;
      border-radius: 5px;
      color: white;
      font-size: 16px;
      cursor: pointer;
    }
    button:hover {
      background: #0056b3;
    }
    .message {
      color: red;
      margin-top: 10px;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <h1> Student Login</h1>
    <form method="POST">
      <input type="text" name="name" placeholder="Enter your name" required>
      <input type="email" name="email" placeholder="Enter your email" required>
      <button type="submit">Login</button>
    </form>
    <?php if($message) echo "<p class='message'>$message</p>"; ?>
  </div>
</body>
</html>
