<?php
session_start();
require_once 'db.php';

if ($_SESSION['role'] != 'admin') die("Access Denied");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $evaluator_id = $_SESSION['user_id'];
    $score = $_POST['score'];
    $comment = $_POST['comment'];

    $sql = "INSERT INTO evaluations (user_id, evaluator_id, score, comments) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiis", $user_id, $evaluator_id, $score, $comment);
    
    if ($stmt->execute()) {
        echo "<script>alert('Evaluation Saved!'); window.location='dashboard_admin.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>