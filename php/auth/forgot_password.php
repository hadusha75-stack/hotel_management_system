<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
ob_start();
header('Content-Type: application/json');
ob_clean();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request.']);
    exit;
}

$step  = $_POST['step']  ?? '';
$email = trim($_POST['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['error' => 'Please enter a valid email address.']);
    exit;
}

$conn = getDB();
$em   = $conn->real_escape_string($email);

// ── STEP 1: Get security hint ─────────────────────────────────
if ($step === 'get_hint') {
    $result = $conn->query("SELECT security_hint FROM customer_login WHERE email='$em' LIMIT 1");
    if (!$result || $result->num_rows === 0) {
        // Don't reveal if email exists — return a generic hint
        echo json_encode(['hint' => "What is your mother's maiden name?"]);
        exit;
    }
    $row  = $result->fetch_assoc();
    $hint = $row['security_hint'] ?? "What is your mother's maiden name?";
    echo json_encode(['hint' => $hint]);
    exit;
}

// ── STEP 2: Verify answer and reset password ──────────────────
if ($step === 'verify_and_reset') {
    $answer = strtolower(trim($_POST['answer'] ?? ''));

    if (empty($answer)) {
        echo json_encode(['error' => 'Please provide your security answer.']);
        exit;
    }

    $result = $conn->query("SELECT id, username, security_answer FROM customer_login WHERE email='$em' LIMIT 1");

    if (!$result || $result->num_rows === 0) {
        echo json_encode(['error' => 'No account found with this email address.']);
        exit;
    }

    $user         = $result->fetch_assoc();
    $storedAnswer = strtolower(trim($user['security_answer'] ?? ''));

    if ($answer !== $storedAnswer) {
        echo json_encode(['error' => 'Incorrect answer. Please try again.']);
        exit;
    }

    // Generate temp password
    $chars        = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $tempPassword = substr(str_shuffle(str_repeat($chars, 3)), 0, 10);

    $conn->query("UPDATE customer_login SET password='$tempPassword', must_change_password=1 WHERE email='$em'");

    // Try to send email
    $subject = "Sabawyan Hotel - Password Reset";
    $message = "Hello {$user['username']},\n\nYour temporary password is:\n\n  $tempPassword\n\nLogin: http://localhost/php/html/public/auth.html\n\nChange your password after logging in.\n\nSabawyan Hotel";
    $headers = "From: noreply@sabawyan.com\r\nX-Mailer: PHP/" . phpversion();
    $sent    = @mail($email, $subject, $message, $headers);

    if ($sent) {
        echo json_encode(['success' => true, 'message' => 'A temporary password has been sent to your email.']);
    } else {
        // Email failed — show password on screen (for localhost/development)
        echo json_encode([
            'success' => true,
            'message' => 'Your temporary password is: <strong>' . $tempPassword . '</strong><br><small>Email could not be sent — copy this password now.</small>'
        ]);
    }
    exit;
}

echo json_encode(['error' => 'Invalid step. Use step=get_hint or step=verify_and_reset.']);
?>
