<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guest') {
  header("Location: index.php");
  exit();
}
if (isset($_GET['logout'])) {
  session_destroy();
  header("Location: index.php");
  exit();
}
$email = $_SESSION['email'];
$message = "";
$redirectToTest2 = false;
$stayOnQuiz = false;

$conn = new mysqli("localhost", "root", "", "dvine_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Check status
$test1_done = $test2_done = false;
$test1_score = $test2_score = null;
$test1_percent = $test2_percent = null;

$check = $conn->prepare("SELECT test1_score, test1_percentage, test2_score, test2_percentage FROM quiz_results WHERE user_email=?");
$check->bind_param("s", $email);
$check->execute();
$check->bind_result($t1_score, $t1_percent, $t2_score, $t2_percent);
$check->fetch();
$check->close();

if ($t1_score !== null) {
  $test1_done = true;
  $test1_score = $t1_score;
  $test1_percent = $t1_percent;
}
if ($t2_score !== null) {
  $test2_done = true;
  $test2_score = $t2_score;
  $test2_percent = $t2_percent;
}
$both_done = $test1_done && $test2_done;

if ($_SERVER["REQUEST_METHOD"] === "POST" && !$both_done) {
  $test = $_POST["test"];
  $score = $_POST["score"];
  $percentage = $_POST["percentage"];
  $date = date("Y-m-d");

  $scoreCol = $test . "_score";
  $percentCol = $test . "_percentage";
  $dateCol = $test . "_date";

  $check = $conn->prepare("SELECT id FROM quiz_results WHERE user_email=?");
  $check->bind_param("s", $email);
  $check->execute();
  $check->store_result();
  $exists = $check->num_rows > 0;
  $check->close();

  if ($exists) {
    $check = $conn->prepare("SELECT $scoreCol FROM quiz_results WHERE user_email=?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->bind_result($existingScore);
    $check->fetch();
    $check->close();

    if ($existingScore !== null) {
      $message = "⚠️ You already submitted $test.";
    } else {
      $update = $conn->prepare("UPDATE quiz_results SET $scoreCol=?, $percentCol=?, $dateCol=? WHERE user_email=?");
      $update->bind_param("ddss", $score, $percentage, $date, $email);
      $update->execute();
      $message = "✅ $test submitted successfully.";
    }
  } else {
    $insert = $conn->prepare("INSERT INTO quiz_results (user_email, $scoreCol, $percentCol, $dateCol) VALUES (?, ?, ?, ?)");
    $insert->bind_param("sdds", $email, $score, $percentage, $date);
    $insert->execute();
    $message = "✅ $test submitted successfully.";
  }

  // Refresh test status
  $check = $conn->prepare("SELECT test1_score, test1_percentage, test2_score, test2_percentage FROM quiz_results WHERE user_email=?");
  $check->bind_param("s", $email);
  $check->execute();
  $check->bind_result($t1_score, $t1_percent, $t2_score, $t2_percent);
  $check->fetch();
  $check->close();

  if ($t1_score !== null) {
    $test1_done = true;
    $test1_score = $t1_score;
    $test1_percent = $t1_percent;
  }
  if ($t2_score !== null) {
    $test2_done = true;
    $test2_score = $t2_score;
    $test2_percent = $t2_percent;
  }
  $both_done = $test1_done && $test2_done;

  if ($test === 'test1' && $test1_done && !$test2_done) $redirectToTest2 = true;
  if ($test === 'test2' && $test2_done) $stayOnQuiz = true;
}
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Guest Quiz</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    * { box-sizing: border-box; }
    body {
      margin: 0; font-family: Arial; background: #f4f6f9;
      display: flex; flex-direction: row; min-height: 100vh;
    }
    .sidebar {
      width: 220px; background: #2c3e50; color: white;
      padding-top: 20px; flex-shrink: 0;
      position: fixed; left: 0; top: 0; bottom: 0;
    }
    .sidebar a {
      display: block; padding: 15px 20px;
      text-decoration: none; color: white;
      border-bottom: 1px solid #1a252f;
    }
    .sidebar a:hover { background-color: #1abc9c; }
    .main {
      flex-grow: 1; padding: 30px; margin-left: 220px;
    }
    .container {
      background: #fff; border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      padding: 30px; max-width: 700px; margin: auto;
    }
    h2 { text-align: center; }
    label { margin-top: 10px; display: block; }
    select, button {
      width: 100%; padding: 10px; font-size: 16px;
      margin: 10px 0; border-radius: 5px; border: 1px solid #ccc;
    }
    .question {
      background: #f9f9f9; margin-bottom: 15px;
      padding: 10px; border-radius: 5px;
    }
    .message {
      font-weight: bold; text-align: center; margin-bottom: 15px;
    }
    .success { color: green; }
    .error { color: red; }
    button {
      background: green; color: white; border: none;
      cursor: pointer;
    }

    .result-box {
      margin-top: 20px;
      border-top: 1px solid #ccc;
      padding-top: 15px;
      font-size: 16px;
    }

    @media (max-width: 768px) {
      body { flex-direction: column; }
      .sidebar {
        width: 100%; height: 60px;
        display: flex; flex-direction: row;
        justify-content: space-around;
        position: fixed; bottom: 0; top: auto; left: 0;
        padding: 0; z-index: 999;
      }
      .sidebar a {
        flex: 1; text-align: center;
        padding: 15px 0; border: none;
        font-size: 16px;
      }
      .main {
        margin-left: 0; padding-top: 20px; padding-bottom: 100px;
      }
    }
  </style>
</head>
<body>

<div class="sidebar">
  <a href="#home" onclick="showSection('home')">🏠 Home</a>
  <a href="#quiz" onclick="showSection('quiz')">📝 Test</a>
  <a href="?logout=true">🚪 Logout</a>
</div>

<div class="main">
  <div id="homeSection" class="container">
    <h2>Welcome Guest</h2>
    <p style="text-align:center;">You can take two quizzes only once.</p>
  </div>

  <div id="quizSection" class="container" style="display:none;">
    <h2>Take Quiz</h2>
    <?php if ($message): ?>
      <div class="message <?= strpos($message, '✅') !== false ? 'success' : 'error' ?>">
        <?= $message ?>
      </div>
    <?php endif; ?>

    <?php if (!$both_done): ?>
    <form method="post" onsubmit="return calculateScore()">
      <label>Select Test:</label>
      <select name="test" id="test" onchange="loadQuestions()" required>
        <?php if (!$test1_done): ?><option value="test1">Test 1</option><?php endif; ?>
        <?php if (!$test2_done): ?><option value="test2">Test 2</option><?php endif; ?>
      </select>
      <div id="questionsContainer"></div>
      <input type="hidden" name="score" id="scoreInput" />
      <input type="hidden" name="percentage" id="percentageInput" />
      <button type="submit">Submit Quiz</button>
    </form>
    <?php endif; ?>

    <?php if ($test1_done || $test2_done): ?>
      <div class="result-box">
     <center>   <h3>📊 Your Quiz Results:</h3> </center>
        <ul>
          <?php if ($test1_done): ?>
            <li>🧪 Test 1: Score = <?= $test1_score ?>/3, Percentage = <?= $test1_percent ?>%</li><br>
          <?php endif; ?>
          <?php if ($test2_done): ?>
            <li>🧪 Test 2: Score = <?= $test2_score ?>/3, Percentage = <?= $test2_percent ?>%</li>
          <?php endif; ?>
        </ul>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
const quizData = {
  test1: [
    { q: "Capital of India?", options: ["Delhi", "Mumbai", "Kolkata"], answer: "Delhi" },
    { q: "2 + 2 = ?", options: ["3", "4", "5"], answer: "4" },
    { q: "HTML stands for?", options: ["Hot Mail", "Hyper Text Markup Language", "HighText Machine Language"], answer: "Hyper Text Markup Language" }
  ],
  test2: [
    { q: "Red planet?", options: ["Earth", "Mars", "Venus"], answer: "Mars" },
    { q: "CSS means?", options: ["Cascading Style Sheets", "Creative Style Syntax", "Computer Style Settings"], answer: "Cascading Style Sheets" },
    { q: "3 x 3 = ?", options: ["6", "9", "12"], answer: "9" }
  ]
};

function loadQuestions() {
  const test = document.getElementById("test").value;
  const container = document.getElementById("questionsContainer");
  container.innerHTML = "";
  quizData[test].forEach((q, i) => {
    const div = document.createElement("div");
    div.className = "question";
    div.innerHTML = `<p><strong>${i + 1}. ${q.q}</strong></p>` + q.options.map(opt => `
      <label><input type="radio" name="q${i}" value="${opt}"> ${opt}</label>
    `).join("");
    container.appendChild(div);
  });
}

function calculateScore() {
  const test = document.getElementById("test").value;
  const questions = quizData[test];
  let score = 0;
  let allAnswered = true;
  questions.forEach((q, i) => {
    const selected = document.querySelector(`input[name="q${i}"]:checked`);
    if (!selected) allAnswered = false;
    else if (selected.value === q.answer) score++;
  });

  if (!allAnswered) {
    alert("❗ Please answer all questions.");
    return false;
  }

  const percentage = ((score / questions.length) * 100).toFixed(2);
  document.getElementById("scoreInput").value = score;
  document.getElementById("percentageInput").value = percentage;
  return true;
}

function showSection(section) {
  document.getElementById("homeSection").style.display = (section === 'home') ? 'block' : 'none';
  document.getElementById("quizSection").style.display = (section === 'quiz') ? 'block' : 'none';
}
window.addEventListener("DOMContentLoaded", () => {
  showSection('home');
  loadQuestions();
});
</script>

<?php if ($redirectToTest2 || $stayOnQuiz): ?>
<script>
  window.addEventListener("DOMContentLoaded", () => {
    showSection('quiz');
    <?php if ($redirectToTest2): ?>
    document.getElementById("test").value = "test2";
    loadQuestions();
    <?php endif; ?>
  });
</script>
<?php endif; ?>

</body>
</html>





