<?php
session_start();
$conn = new mysqli("localhost", "root", "", "dvine_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $email = $_POST['email'];
  $password = $_POST['password'];

  $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password'])) {
      $_SESSION['username'] = $user['username'];
      $_SESSION['role'] = $user['role'];
      $_SESSION['email'] = $user['email'];

      if ($user['role'] === 'admin') {
        header("Location: admin-dashboard.php"); exit();
      } elseif ($user['role'] === 'member') {
        header("Location: member-dashboard.php"); exit();
      } elseif ($user['role'] === 'guest') {
        header("Location: guest-dashboard.php"); exit();
      } else {
        $error = "Invalid role assigned.";
      }
    } else {
      $error = "Invalid password.";
    }
  } else {
    $error = "User not found.";
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>D'Vine Login</title>
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      font-family: Arial, sans-serif;
      background: #f4f4f4;
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }

    .login-container {
      width: 100%;
      max-width: 400px;
      padding: 20px;
    }

    form {
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }

    .logo-container {
      text-align: center;
      margin-bottom: 20px;
    }

    .logo {
      max-width: 200px;
      height: auto;
    }

    h2 {
      text-align: center;
      margin-bottom: 20px;
      font-size: 24px;
    }

    input, button {
      width: 100%;
      padding: 12px;
      margin: 10px 0;
      font-size: 16px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }

    button {
      background-color: #28a745;
      color: white;
      font-weight: bold;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    button:hover {
      background-color: #218838;
    }

    .error {
      color: red;
      text-align: center;
      margin-bottom: 10px;
      font-size: 15px;
    }

    @media (max-width: 600px) {
      form {
        padding: 20px;
      }

      .logo {
        max-width: 200px;
      }

      h2 {
        font-size: 20px;
      }

      input, button {
        font-size: 15px;
      }
    }
  </style>
</head>
<body>
  <div class="login-container">
    <form method="POST">
      <div class="logo-container">
        <img src="Screenshot 2024-12-14 155126.png" alt="D'Vine Logo" class="logo">
      </div>
    <center>  <h3>Login</h3>  </center>
      <?php if ($error): ?><p class="error"><?= $error ?></p><?php endif; ?>
      <input type="email" name="email" placeholder="Email" required
        value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>
    </form>
  </div>
</body>
</html>

