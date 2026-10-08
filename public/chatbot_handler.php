<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/resident_auth.php';
require_once __DIR__ . '/../config/gemini.php';

header('Content-Type: application/json');

const CHATBOT_RATE_LIMIT_PER_HOUR = 20;
const CHATBOT_MAX_MESSAGE_LENGTH = 1000;
const CHATBOT_HISTORY_TURNS = 10; // messages (both roles) of context to send back to Gemini

function chatbot_respond(array $data, int $statusCode = 200): never
{
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    chatbot_respond(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

if (!resident_is_logged_in()) {
    chatbot_respond(['ok' => false, 'error' => 'Please log in to use the chat assistant.'], 401);
}

$residentId = (int)$_SESSION['resident_id'];

$input = json_decode(file_get_contents('php://input'), true);
$message = trim((string)($input['message'] ?? ''));

if ($message === '') {
    chatbot_respond(['ok' => false, 'error' => 'Please type a message.'], 400);
}

if (mb_strlen($message) > CHATBOT_MAX_MESSAGE_LENGTH) {
    chatbot_respond(['ok' => false, 'error' => 'Message is too long (max ' . CHATBOT_MAX_MESSAGE_LENGTH . ' characters).'], 400);
}

// Rate limit: max N user messages per resident per rolling hour.
$countStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM chatbot_messages WHERE resident_id = ? AND role = 'user' AND created_at >= (NOW() - INTERVAL 1 HOUR)"
);
$countStmt->execute([$residentId]);
if ((int)$countStmt->fetchColumn() >= CHATBOT_RATE_LIMIT_PER_HOUR) {
    chatbot_respond([
        'ok' => false,
        'error' => "You've reached the chat limit for this hour. Please try again later.",
    ], 429);
}

// Pull recent history for short-term context, then log the new user message.
$historyStmt = $pdo->prepare(
    "SELECT role, message FROM chatbot_messages WHERE resident_id = ? ORDER BY id DESC LIMIT ?"
);
$historyStmt->bindValue(1, $residentId, PDO::PARAM_INT);
$historyStmt->bindValue(2, CHATBOT_HISTORY_TURNS, PDO::PARAM_INT);
$historyStmt->execute();
$history = array_reverse($historyStmt->fetchAll());

$pdo->prepare("INSERT INTO chatbot_messages (resident_id, role, message) VALUES (?, 'user', ?)")
    ->execute([$residentId, $message]);

$conversation = array_map(
    static fn(array $row) => ['role' => $row['role'], 'text' => $row['message']],
    $history
);
$conversation[] = ['role' => 'user', 'text' => $message];

$reply = gemini_generate_reply($conversation);

if ($reply === null) {
    $reply = "Sorry, I'm having trouble responding right now. Please try again in a moment, "
        . "or contact the Barangay Hall directly for urgent concerns.";
    $pdo->prepare("INSERT INTO chatbot_messages (resident_id, role, message) VALUES (?, 'assistant', ?)")
        ->execute([$residentId, $reply]);
    chatbot_respond(['ok' => false, 'reply' => $reply]);
}

$pdo->prepare("INSERT INTO chatbot_messages (resident_id, role, message) VALUES (?, 'assistant', ?)")
    ->execute([$residentId, $reply]);

chatbot_respond(['ok' => true, 'reply' => $reply]);
