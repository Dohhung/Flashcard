<?php
session_start();
include('../db.php'); // Kết nối cơ sở dữ liệu

// Kiểm tra nếu người dùng chưa đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Lấy ID bộ từ vựng từ URL
$vocabularySet_id = isset($_GET['vocabularySet_id']) ? (int)$_GET['vocabularySet_id'] : 0;

// Kiểm tra xem bộ từ vựng có tồn tại
$query = "SELECT * FROM vocabulary_set WHERE vocabularySet_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $vocabularySet_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<h1>Bộ từ vựng không tồn tại!</h1>";
    exit;
}

$vocabulary_set = $result->fetch_assoc();

// Kiểm tra nếu bộ từ vựng thuộc loại 'personal'
$is_personal_list = $vocabulary_set['vocabulary_type'] === 'personal';

// Lấy danh sách các flashcard thuộc bộ từ vựng
$query = "
    SELECT f.vocab, f.meaning, f.image_path, f.ipa
    FROM flashcard f
    WHERE f.vocabularySet_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $vocabularySet_id);
$stmt->execute();
$flashcards = $stmt->get_result();

// Đưa tất cả các flashcard vào mảng để xử lý
$flashcards_data = [];
while ($row = $flashcards->fetch_assoc()) {
    $flashcards_data[] = $row;
}

// Xác định vị trí hiện tại (dựa trên query parameter hoặc mặc định là 0)
$current_index = isset($_GET['index']) ? (int)$_GET['index'] : 0;

// Đảm bảo index hợp lệ
if ($current_index < 0) $current_index = 0;
if ($current_index >= count($flashcards_data)) $current_index = count($flashcards_data) - 1;

// Lấy flashcard hiện tại 
$current_flashcard = $flashcards_data[$current_index];


$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Học Flashcard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/flashcards.css">
</head>
<body>
    <?php include('../templates/header.php'); ?>
    <div class="container mt-5">
        <h1 class="text-center mb-4">Học bộ từ vựng: <?php echo htmlspecialchars($vocabulary_set['vocabulary_name']); ?></h1>

        <?php if ($is_personal_list): ?>
        <div class="text-end mb-3">
            <a href="add_flashcard.php?vocabularySet_id=<?php echo $vocabularySet_id; ?>" class="btn btn-success">
                Thêm từ mới
            </a>
        </div>
        <?php endif; ?>

        <div class="card mx-auto shadow-sm" style="max-width: 400px;">
            <?php if (!empty($current_flashcard['image_path'])): ?>
                <img src="../images/<?php echo htmlspecialchars($current_flashcard['image_path']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($current_flashcard['vocab']); ?>">
            <?php endif; ?>
            <div class="card-body text-center">
                <h5 class="card-title text-primary"><?php echo htmlspecialchars($current_flashcard['vocab']); ?></h5>
                <?php if (!empty($current_flashcard['ipa'])): ?>
                    <p class="card-text"><strong>Phát âm:</strong> <?php echo htmlspecialchars($current_flashcard['ipa']); ?></p>
                <?php endif; ?>
                <p class="card-text"><strong>Nghĩa:</strong> <?php echo htmlspecialchars($current_flashcard['meaning']); ?></p>
            </div>
        </div>
        <div class="d-flex justify-content-between mt-4">
            <a href="flashcards.php?vocabularySet_id=<?php echo $vocabularySet_id; ?>&index=<?php echo max(0, $current_index - 1); ?>" 
               class="btn btn-secondary">Trước</a>
            <a href="flashcards.php?vocabularySet_id=<?php echo $vocabularySet_id; ?>&index=<?php echo min(count($flashcards_data) - 1, $current_index + 1); ?>" 
               class="btn btn-primary">Tiếp theo</a>
            
        </div>
    </div>
    <?php include('../templates/footer.php'); ?>
</body>
</html>
