<?php
session_start();
include('../db.php'); // Kết nối cơ sở dữ liệu

// Kiểm tra nếu người dùng chưa đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Kiểm tra nếu không có bộ từ vựng nào được chọn
if (!isset($_GET['vocabularySet_id'])) {
    echo "Vui lòng chọn một bộ từ vựng trước khi chơi!";
    exit();
}

// Lấy bộ từ vựng được chọn
$vocabularySet_id = intval($_GET['vocabularySet_id']);

// Truy vấn từ vựng từ bộ từ vựng đã chọn, giới hạn tối đa 10 từ
$query = "
    SELECT vocab 
    FROM flashcard 
    WHERE vocabularySet_id = ? 
    ORDER BY RAND() 
    LIMIT 10
";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $vocabularySet_id);
$stmt->execute();
$result = $stmt->get_result();

$words = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $words[] = strtoupper($row['vocab']);
    }
} else {
    echo "Không có từ vựng nào trong bộ từ vựng này!";
    exit();
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Word Game</title>
    <link rel="stylesheet" href="../css/game2.css">
</head>
<body>
    <div class="game-container">
        <h1>Find Word Game</h1>
        <div id="word-list">
            <h3>Find These Words:</h3>
            <ul>
                <?php foreach ($words as $word): ?>
                    <li id="word-<?php echo $word; ?>"><?php echo $word; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div id="grid"></div>
        <button id="reset" class="btn btn-primary">Reset Game</button>
    </div>

    <script>
        const words = <?php echo json_encode($words); ?>;
    </script>
    <script src="../js/game2.js"></script>
</body>
</html>
