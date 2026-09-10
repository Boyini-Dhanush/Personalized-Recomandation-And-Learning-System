<?php
include("../config/db.php");

/* Check login */
if(!isset($_SESSION['uid']) || $_SESSION['role'] != 'student'){
    header('Content-Type: application/json');
    echo json_encode(['error' => 'not_authenticated']);
    exit();
}

$uid = $_SESSION['uid'];

/* Helper functions */

function getResourceAnalytics($conn, $uid) {
    $result = mysqli_query($conn, "
        SELECT sr.id, sr.subject, sr.weak_topic, sr.created_at, f.id as feedback_id
        FROM student_requests sr
        LEFT JOIN feedback f ON sr.id = f.request_id
        WHERE sr.user_id = '$uid'
        ORDER BY sr.created_at DESC
    ");
    
    $analytics = [
        'total_requests' => 0,
        'subjects_requested' => [],
        'topics_requested' => [],
        'total_feedback_given' => 0,
        'pending_resources' => 0
    ];
    
    while($row = mysqli_fetch_assoc($result)) {
        $analytics['total_requests']++;
        
        $subject = $row['subject'];
        if(!isset($analytics['subjects_requested'][$subject])) {
            $analytics['subjects_requested'][$subject] = 0;
        }
        $analytics['subjects_requested'][$subject]++;
        
        $topic = $row['weak_topic'];
        if(!isset($analytics['topics_requested'][$topic])) {
            $analytics['topics_requested'][$topic] = 0;
        }
        $analytics['topics_requested'][$topic]++;
        
        if($row['feedback_id']) {
            $analytics['total_feedback_given']++;
        } else {
            $analytics['pending_resources']++;
        }
    }
    
    return $analytics;
}

function getTestAnalytics($conn, $uid) {
    // Check if table exists
    $check = mysqli_query($conn, "SHOW TABLES LIKE 'test_results'");
    if(mysqli_num_rows($check) == 0) {
        return ['tests_taken' => 0, 'avg_score' => 0];
    }
    
    $q = mysqli_query($conn, "SELECT COUNT(*) as tests_taken, AVG(score/total*100) as avg_score FROM test_results WHERE user_id='$uid'");
    $r = mysqli_fetch_assoc($q);
    return [
        'tests_taken' => (int)$r['tests_taken'],
        'avg_score' => round((float)$r['avg_score'], 1)
    ];
}

/* Get all analytics data */
$data = [
    'resource' => getResourceAnalytics($conn, $uid),
    'tests' => getTestAnalytics($conn, $uid)
];

header('Content-Type: application/json');
echo json_encode($data);
?>
