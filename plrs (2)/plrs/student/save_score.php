<?php
include("../config/db.php");
header('Content-Type: application/json');

if(!isset($_SESSION['uid'])){
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit();
}

// Auto-create table if not exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS test_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    file_path VARCHAR(255),
    score INT NOT NULL,
    total INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$data = json_decode(file_get_contents("php://input"), true);
$uid = $_SESSION['uid'];
$score = (int)($data['score'] ?? 0);
$total = (int)($data['total'] ?? 0);
$file_path = mysqli_real_escape_string($conn, $data['file_path'] ?? '');

if($total > 0){
    $q = mysqli_query($conn, "INSERT INTO test_results (user_id, file_path, score, total) VALUES ('$uid', '$file_path', '$score', '$total')");
    if($q){
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid total."]);
}
?>
