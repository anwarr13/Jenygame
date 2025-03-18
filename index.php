<?php
session_start();
require_once 'db.php';

// 
if (!isset($_SESSION['game_started']) && isset($_POST['username'])) {
    $_SESSION['username'] = $_POST['username']; // ngan
    $_SESSION['score'] = 0; // iyang score
    $_SESSION['question_count'] = 0; // pila ka pangutana 
    $_SESSION['game_started'] = true; // mag sugod ug duwa
    $_SESSION['time_started'] = date('Y-m-d H:i:s'); // kung unsa siya oras ga sugod ug duwa
    $_SESSION['used_fruits'] = []; // para di balik2 ang mga fruits
}

// Game completion logic
function endGame($conn) {
    $timeEnded = date('Y-m-d H:i:s');
    $timeStarted = $_SESSION['time_started'];
    $duration = strtotime($timeEnded) - strtotime($timeStarted);
    $finalScore = $_SESSION['score']; // Store the final score
    $datePlayed = date('Y-m-d'); // Get current date
    
    $stmt = $conn->prepare("INSERT INTO players (username, score, time_started, time_ended, duration_seconds, date_played) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sissss", $_SESSION['username'], $_SESSION['score'], $_SESSION['time_started'], $timeEnded, $duration, $datePlayed);
    $stmt->execute();
    $stmt->close();
    
    session_destroy();
    session_start(); // Start a new session
    $_SESSION['final_score'] = $finalScore; // Save the final score in the new session
}

// Handle answer submission
if (isset($_POST['answer'])) {
    if ($_POST['answer'] == $_SESSION['correct_fruit']) {
        $_SESSION['score']++;
    }
    $_SESSION['question_count']++;
    
    if ($_SESSION['question_count'] >= 10) {
        endGame($conn);
        header('Location: index.php?show_results=1');
        exit();
    }
}

// Get high scores
$highScores = [];
$result = $conn->query("SELECT username, score, duration_seconds as time, DATE_FORMAT(date_played, '%m/%d/%Y') as date_played FROM players ORDER BY score DESC, duration_seconds ASC LIMIT 100");
if ($result) {
    while ($row = $result->fetch_object()) {
        $highScores[] = $row;
    }
    $result->free();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Vegetable Quiz Game</title>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f0f2f5;
            color: #333;
        }

        .game-container {
            background: white;
            padding: 30px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }

        h1 {
            color: #1a73e8;
            text-align: center;
            font-size: 28px;
            margin: 0 0 20px 0;
        }

        .game-info {
            text-align: center;
            color: #666;
            margin-bottom: 25px;
            font-size: 26px;
        }

        .choices {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            max-width: 500px;
            margin: 20px auto;
        }

        .choice-btn {
            background: #f8f9fa;
            border: 1px solid #dadce0;
            padding: 12px 20px;
            font-size: 15px;
            cursor: pointer;
            color: #202124;
        }

        .choice-btn:hover {
            background: #e8f0fe;
            border-color: #1a73e8;
        }

        .score {
            font-size: 20px;
            color: #1a73e8;
            text-align: center;
            margin: 15px 0;
        }

        .question-count {
            text-align: center;
            color: #5f6368;
            margin-bottom: 20px;
            font-size: 15px;
        }

        input[type="text"] {
            width: 100%;
            max-width: 300px;
            padding: 8px 12px;
            margin: 0 auto 15px;
            display: block;
            border: 1px solid #dadce0;
            font-size: 15px;
        }

        input[type="text"]:focus {
            outline: none;
            border-color: #1a73e8;
        }

        button, .button {
            background: #1a73e8;
            color: white;
            border: none;
            padding: 10px 24px;
            font-size: 15px;
            cursor: pointer;
            display: inline-block;
            text-decoration: none;
        }

        button:hover, .button:hover {
            background: #1557b0;
        }

        .high-scores {
            margin-top: 30px;
        }

        .high-scores h2 {
            color: #202124;
            font-size: 20px;
            margin-bottom: 15px;
            text-align: center;
        }

        table {
            width: 100%;
            border-spacing: 0;
            font-size: 14px;
            color: #202124;
        }

        th {
            background: #f8f9fa;
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #dadce0;
            font-weight: 500;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #dadce0;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .result-message {
            background: #e8f0fe;
            color: #1a73e8;
            padding: 15px;
            text-align: center;
            margin: 20px 0;
        }

        .fruit-image {
            display: block;
            max-width: 200px;
            margin: 20px auto;
            border: 1px solid #dadce0;
        }

        .text-center {
            text-align: center;
        }

        .actions {
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <?php if (!isset($_SESSION['game_started']) && !isset($_GET['show_results'])): ?>
        <div class="game-container">
            <h1>Vegetable Quiz Game</h1>
         <center><em>Created by: Jeny Pentecase</em></center>
         <br>
            <div class="game-info">
                Identify the fruits to test your knowledge
            </div>
            <form method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <div class="actions">
                    <button type="submit">Start Quiz</button>
                </div>
            </form>
        </div>

        <div class="game-container">
            <h2>High Scores</h2>
            <table>
                <tr>
                    <th>Rank</th>
                    <th>Player</th>
                    <th>Score</th>
                    <th>Time</th>
                </tr>
                <?php 
                $rank = 1;
                foreach ($highScores as $score): 
                ?>
                <tr>
                    <td><?php echo $rank++; ?></td>
                    <td><?php echo htmlspecialchars($score->username); ?></td>
                    <td><?php echo $score->score; ?>/10</td>
                    <td><?php echo $score->time; ?> seconds</td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

    <?php elseif (isset($_GET['show_results'])): ?>
        <div class="game-container">
            <h1>Quiz Complete</h1>
            <div class="result-message">
                Score: <?php echo $_SESSION['final_score']; ?>/10
            </div>
            <div class="actions">
                <a href="index.php" class="button">Play Again</a>
            </div>
        </div>

        <div class="game-container">
            <h2>High Scores</h2>
            <table>
                <tr>
                    <th>Rank</th>
                    <th>Player</th>
                    <th>Score</th>
                    <th>Time</th>
                </tr>
                <?php 
                $rank = 1;
                foreach ($highScores as $score): 
                ?>
                <tr>
                    <td><?php echo $rank++; ?></td>
                    <td><?php echo htmlspecialchars($score->username); ?></td>
                    <td><?php echo $score->score; ?>/10</td>
                    <td><?php echo $score->time; ?>s</td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

    <?php else: ?>
        <div class="game-container">
            <div class="score">Score: <?php echo $_SESSION['score']; ?>/10</div>
            <div class="question-count">Question <?php echo $_SESSION['question_count'] + 1; ?> of 10</div>
            
            <?php
            $fruits = ['Apple', 'Banana', 'Orange', 'Grape', 'Strawberry', 'Mango', 'Pineapple', 'Watermelon', 'Kiwi', 'Peach', 'Pear', 'Plum'];
            
            $available_fruits = array_diff($fruits, $_SESSION['used_fruits']);
            
            if (empty($available_fruits)) {
                $_SESSION['used_fruits'] = [];
                $available_fruits = $fruits;
            }
            
            $correct_fruit = array_rand(array_flip($available_fruits));
            $_SESSION['used_fruits'][] = $correct_fruit;
            $_SESSION['correct_fruit'] = $correct_fruit;
            
            $wrong_fruits = array_diff($fruits, [$correct_fruit]);
            shuffle($wrong_fruits);
            $wrong_fruits = array_slice($wrong_fruits, 0, 3);
            
            $choices = array_merge([$correct_fruit], $wrong_fruits);
            shuffle($choices);
            ?>
            
            <img src="images/<?php echo strtolower($correct_fruit); ?>.jpg" alt="Fruit" class="fruit-image">
            
            <form method="POST" class="choices">
                <?php foreach ($choices as $fruit): ?>
                <button type="submit" name="answer" value="<?php echo $fruit; ?>" class="choice-btn">
                    <?php echo $fruit; ?>
                </button>
                <?php endforeach; ?>
            </form>
        </div>
    <?php endif; ?>
</body>
</html>
