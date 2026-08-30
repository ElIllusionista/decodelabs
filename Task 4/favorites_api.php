<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$DB_HOST = 'localhost';
$DB_NAME = 'acorjlbn_abz_decodelabs';
$DB_USER = 'acorjlbn_abz';
$DB_PASS = 'YOUR_PASSWORD_HERE';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$input = json_decode(file_get_contents('php://input'), true);

switch ($action) {
    case 'list':
        handleList($conn);
        break;
    case 'create':
        handleCreate($conn, $input);
        break;
    case 'delete':
        handleDelete($conn);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown or missing action']);
        break;
}

$conn->close();

function handleList($conn) {
    $result = $conn->query('SELECT id, content, author, saved_at FROM favorites ORDER BY id DESC');
    $favorites = [];
    while ($row = $result->fetch_assoc()) {
        $favorites[] = $row;
    }
    echo json_encode($favorites);
}

function handleCreate($conn, $input) {
    $content = isset($input['content']) ? trim($input['content']) : '';
    $author  = isset($input['author']) ? trim($input['author']) : 'Unknown';

    if ($content === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Quote content is required']);
        return;
    }

    $stmt = $conn->prepare('INSERT INTO favorites (content, author) VALUES (?, ?)');
    $stmt->bind_param('ss', $content, $author);

    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        http_response_code(201);
        echo json_encode([
            'id' => $newId,
            'content' => $content,
            'author' => $author
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Could not save quote']);
    }

    $stmt->close();
}

function handleDelete($conn) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'A valid id is required']);
        return;
    }

    $stmt = $conn->prepare('DELETE FROM favorites WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Favorite not found']);
    } else {
        echo json_encode(['message' => 'Favorite deleted', 'id' => $id]);
    }

    $stmt->close();
}
