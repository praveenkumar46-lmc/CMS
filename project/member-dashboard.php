<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'member') {
  header("Location: login.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Member Dashboard</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f6f9fc;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      color: #333;
    }
    .box {
      text-align: center;
      padding: 40px;
      background: white;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    h1 {
      color: #2c3e50;
    }
    form {
      margin-top: 20px;
    }
    button {
      background: #e74c3c;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
    button:hover {
      background: #c0392b;
    }
  </style>
</head>
<body>
  <div class="box">
    <h1>Welcome, Member!</h1>
    <p>You are logged in as a member. Limited access granted.</p>
    
    <form method="post" action="logout.php">
      <button type="submit">Logout</button>
    </form>
  </div>
</body>
</html>
