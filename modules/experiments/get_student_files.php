<?php
session_start();
require_once '../../config/db.php';

// Set JSON header first
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

$experiment_id = intval($_GET['experiment_id'] ?? 0);
$student_id = intval($_GET['student_id'] ?? 0);

if (!$experiment_id || !$student_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

try {
    $files = [];
    
    // First try to get files from the new multiple files table
    $stmt = $conn->prepare("
        SELECT eof.*, es.id as submission_id
        FROM experiment_submissions es
        LEFT JOIN experiment_output_files eof ON es.id = eof.submission_id
        WHERE es.experiment_id = ? AND es.student_id = ?
        ORDER BY eof.uploaded_at ASC
    ");
    $stmt->bind_param("ii", $experiment_id, $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $files = [];
    while ($row = $result->fetch_assoc()) {
        if ($row['file_path']) {
            $files[] = [
                'id' => $row['id'],
                'submission_id' => $row['submission_id'],
                'file_path' => $row['file_path'],
                'file_name' => $row['file_name'],
                'uploaded_at' => $row['uploaded_at']
            ];
        }
    }
    
    // If no files found in new system, check old system for backward compatibility
    if (empty($files)) {
        $stmt = $conn->prepare("
            SELECT output_file, submitted_at 
            FROM experiment_submissions 
            WHERE experiment_id = ? AND student_id = ? 
            AND output_file IS NOT NULL AND output_file != '' AND output_file != 'placeholder_'
        ");
        $stmt->bind_param("ii", $experiment_id, $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            if ($row['output_file'] && !str_starts_with($row['output_file'], 'placeholder_')) {
                $files[] = [
                    'id' => 0,
                    'submission_id' => 0,
                    'file_path' => $row['output_file'],
                    'file_name' => basename($row['output_file']),
                    'uploaded_at' => $row['submitted_at'] ?? date('Y-m-d H:i:s')
                ];
            }
        }
    }
    
    echo json_encode($files);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}
?>
