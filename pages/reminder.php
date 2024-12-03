<?php
session_start();
include('../db.php'); // Kết nối cơ sở dữ liệu
require '../vendor/autoload.php'; // Đảm bảo bạn đã cài PHPMailer nếu muốn sử dụng

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


// Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    // Nếu chưa đăng nhập, chuyển hướng người dùng về trang đăng nhập
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id']; // Lấy user_id từ session

// Kiểm tra lần đăng nhập gần nhất của người dùng
$query = "SELECT MAX(activity_date) AS last_activity FROM history WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($last_activity);
$stmt->fetch();
$stmt->close();

// Kiểm tra xem người dùng có hoạt động trong 48 giờ qua không
$last_activity_time = new DateTime($last_activity);
$current_time = new DateTime();
$interval = $last_activity_time->diff($current_time);

// Nếu quá 48 giờ, gửi email nhắc nhở
if ($interval->h >= 48 || $interval->days > 0) {
    // Lấy email của người dùng
    $email_query = "SELECT email FROM user WHERE user_id = ?";
    $stmt = $conn->prepare($email_query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($email);
    $stmt->fetch();
    $stmt->close();

    // Gửi email nhắc nhở
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();  // Sử dụng giao thức SMTP
        $mail->Host = 'smtp.gmail.com';  // Địa chỉ SMTP của Gmail
        $mail->SMTPAuth = true;  // Kích hoạt xác thực SMTP
        $mail->Username = 'hungdo.230518@gmail.com';  // Địa chỉ email người gửi
        $mail->Password = '0816952719';  // Mật khẩu ứng dụng hoặc mật khẩu Gmail của bạn
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;  // Mã hóa TLS
        $mail->Port = 587;  // Cổng SMTP sử dụng TLS (587)
        
        // Người gửi và người nhận
        $mail->setFrom('hungdo.230518@gmail.com', 'Hệ thống học tập');
        $mail->addAddress($email); // Email người dùng

        // Nội dung email
        $mail->isHTML(true);
        $mail->Subject = 'Nhắc nhở học tập';
        $mail->Body    = 'Đã 48 giờ bạn chưa truy cập hệ thống. Hãy quay lại học tập với Apple nhé!';

        $mail->send();
        echo 'Email đã được gửi đi';
    } catch (Exception $e) {
        echo "Lỗi khi gửi email: {$mail->ErrorInfo}";
    }

    // Thêm dữ liệu vào bảng reminder
    $reminder_query = "INSERT INTO reminder (user_id, reminder_date) VALUES (?, NOW())";
    $stmt = $conn->prepare($reminder_query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->close();
} else {
    echo 'Không cần nhắc nhở, người dùng đã đăng nhập trong vòng 48 giờ qua.';
}

// Kiểm tra khi người dùng đăng nhập lại và cập nhật trạng thái reminder
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    // Cập nhật trạng thái reminder thành Complete khi người dùng đăng nhập lại
    $update_reminder_query = "UPDATE reminder SET status = 'Complete' WHERE user_id = ? AND status = 'Pending' AND reminder_date <= NOW()";
    $stmt = $conn->prepare($update_reminder_query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->close();
}
?>
