<?php
// ============================================
// contacts_api.php - Backend API for the Contact Book
// Handles: list, create, update, delete via ?action=...
// ============================================

// Allow the frontend to call this from the browser
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight browser check (sent automatically before some requests)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ---- Database connection details ----
// Since this PHP file lives on the same server as the database,
// the host is simply "localhost"
$DB_HOST = 'localhost';
$DB_NAME = 'acorjlbn_abz_decodelabs';
$DB_USER = 'acorjlbn_abz';
$DB_PASS = 'YOUR_PASSWORD_HERE'; // <-- replace with your actual password

// Connect to MySQL using MySQLi
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

// If the connection fails, stop here and return an error instead of crashing silently
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// Figure out which action was requested
$action = isset($_GET['action']) ? $_GET['action'] : '';

// For POST requests, read the JSON body sent by the frontend
$input = json_decode(file_get_contents('php://input'), true);

switch ($action) {

    case 'list':
        handleList($conn);
        break;

    case 'create':
        handleCreate($conn, $input);
        break;

    case 'update':
        handleUpdate($conn, $input);
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

// ============================================
// GET ?action=list - Returns every contact, newest first
// ============================================
function handleList($conn) {
    $result = $conn->query('SELECT id, name, email, phone, notes, created_at FROM contacts ORDER BY id DESC');

    $contacts = [];
    while ($row = $result->fetch_assoc()) {
        $contacts[] = $row;
    }

    echo json_encode($contacts);
}

// ============================================
// POST ?action=create - Body: { name, email, phone, notes }
// ============================================
function handleCreate($conn, $input) {
    $name  = isset($input['name']) ? trim($input['name']) : '';
    $email = isset($input['email']) ? trim($input['email']) : '';
    $phone = isset($input['phone']) ? trim($input['phone']) : '';
    $notes = isset($input['notes']) ? trim($input['notes']) : '';

    // ---- Validation ----
    if ($name === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Name is required']);
        return;
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'A valid email is required']);
        return;
    }

    // Prepared statement - prevents SQL injection by treating
    // user input as data, never as part of the SQL command itself
    $stmt = $conn->prepare('INSERT INTO contacts (name, email, phone, notes) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('ssss', $name, $email, $phone, $notes);

    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        http_response_code(201);
        echo json_encode([
            'id' => $newId,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'notes' => $notes
        ]);
    } else {
        // MySQL error code 1062 = duplicate entry (violates our UNIQUE email constraint)
        if ($conn->errno === 1062) {
            http_response_code(400);
            echo json_encode(['error' => 'A contact with this email already exists']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Could not create contact']);
        }
    }

    $stmt->close();
}

// ============================================
// POST ?action=update&id=5 - Body: { name, email, phone, notes }
// ============================================
function handleUpdate($conn, $input) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'A valid id is required']);
        return;
    }

    $name  = isset($input['name']) ? trim($input['name']) : '';
    $email = isset($input['email']) ? trim($input['email']) : '';
    $phone = isset($input['phone']) ? trim($input['phone']) : '';
    $notes = isset($input['notes']) ? trim($input['notes']) : '';

    if ($name === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Name is required']);
        return;
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'A valid email is required']);
        return;
    }

    $stmt = $conn->prepare('UPDATE contacts SET name = ?, email = ?, phone = ?, notes = ? WHERE id = ?');
    $stmt->bind_param('ssssi', $name, $email, $phone, $notes, $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Contact not found']);
        } else {
            echo json_encode([
                'id' => $id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'notes' => $notes
            ]);
        }
    } else {
        if ($conn->errno === 1062) {
            http_response_code(400);
            echo json_encode(['error' => 'A contact with this email already exists']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Could not update contact']);
        }
    }

    $stmt->close();
}

// ============================================
// POST ?action=delete&id=5
// ============================================
function handleDelete($conn) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'A valid id is required']);
        return;
    }

    $stmt = $conn->prepare('DELETE FROM contacts WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Contact not found']);
    } else {
        echo json_encode(['message' => 'Contact deleted', 'id' => $id]);
    }

    $stmt->close();
}
