<?php
session_start();

// ==============================================
// DATABASE CONFIGURATION
// ==============================================
define('DB_HOST', 'localhost');
define('DB_USER', 'xxxx');
define('DB_PASS', 'xxxx');
define('DB_NAME', 'xxx');

define('RECAPTCHA_SITE_KEY', 'xxxx'); // Google test key
define('RECAPTCHA_SECRET_KEY', 'xxxx'); // Google test key

// ==============================================
// SMTP CONFIGURATION - UPDATE WITH YOUR CREDENTIALS
// ==============================================
define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_USER', 'xxx');
define('SMTP_PASS', 'xxx');
define('SMTP_FROM', 'xxx');
define('SMTP_TO', 'xxx');
define('SMTP_FROM_NAME', 'B2B'); // From name

// ==============================================
// DATABASE CONNECTION
// ==============================================
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// ==============================================
// PHPMailer Autoload - Adjust path as needed
// ==============================================

// Option 1: If using Composer (recommended)
// require_once __DIR__ . '/vendor/autoload.php';

// Option 2: Manual includes (if you downloaded PHPMailer manually)
require_once __DIR__ . '/vendor/PHPMailer/Exception.php';
require_once __DIR__ . '/vendor/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/vendor/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ==============================================
// EMAIL FUNCTION WITH PHPMailer
// ==============================================
function sendEmail($to, $subject, $message, $isHTML = true) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
        // Timeout settings (optional)
        $mail->Timeout = 30;
        
        // Recipients
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($to);
        
        // Optional: Add reply-to
        $mail->addReplyTo(SMTP_FROM, SMTP_FROM_NAME);
        
        // Content
        $mail->isHTML($isHTML);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        
        // Plain text version for email clients that don't support HTML
        if ($isHTML) {
            $mail->AltBody = strip_tags($message);
        }
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log error for debugging
        error_log("Email could not be sent. Error: {$mail->ErrorInfo}");
        return false;
    }
}


// ==============================================
// GOOGLE reCAPTCHA VERIFICATION FUNCTION
// ==============================================
function verifyRecaptcha($recaptchaResponse) {
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $recaptchaResponse,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $response = json_decode($result, true);
    
    return $response['success'] === true;
}


// ==============================================
// ALTERNATIVE: Simple mail function as fallback
// ==============================================
function sendSimpleMail($to, $subject, $message) {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SMTP_FROM . "\r\n";
    $headers .= "Reply-To: " . SMTP_FROM . "\r\n";
    
    return mail($to, $subject, $message, $headers);
}

// ==============================================
// GET MENU ITEMS FUNCTION
// ==============================================
function getMenuItems($conn) {
    $result = $conn->query("SELECT title, slug FROM pages WHERE is_published=1 ORDER BY menu_order ASC");
    $items = [];
    while($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    return $items;
}

// ==============================================
// SANITIZE INPUT FUNCTION
// ==============================================
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

?>