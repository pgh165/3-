<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$conn = new mysqli('localhost', 'jiho', 'qwer1234', 'tempdb');

if ($conn->connect_error) {
    echo json_encode(['error' => 'DB 연결 실패: ' . $conn->connect_error]);
    exit;
}

$conn->set_charset('utf8');

$sql = "SELECT id, temperature, created_at FROM temp ORDER BY created_at DESC LIMIT 30";
$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
$conn->close();
