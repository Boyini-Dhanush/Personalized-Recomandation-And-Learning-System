<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION['uid']) || $_SESSION['role'] != 'faculty') {
    header("Location: ../auth/login.php");
    exit();
}

$uid = $_SESSION['uid'];
$faculty = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT * FROM users WHERE id='$uid'")
);

/* Function to save faculty reply */
function saveFacultyReply($conn, $feedback_id, $reply)
{
    $reply = mysqli_real_escape_string($conn, $reply);
    $query = "UPDATE feedback SET faculty_reply='$reply' WHERE id='$feedback_id'";
    return mysqli_query($conn, $query);
}

/* Function to fetch feedback data */
function getFeedbackData($conn)
{
    $query = "
    SELECT
        u.name AS student,
        sr.subject,
        sr.weak_topic,
        r.title,
        r.resource_link,
        r.resource_type,
        r.file_path,
        f.feedback,
        f.faculty_reply,
        f.id
    FROM student_requests sr
    JOIN users u ON sr.user_id = u.id
    LEFT JOIN resources r ON (sr.id=r.request_id) OR (r.request_id IS NULL AND LOWER(sr.subject)=LOWER(r.subject) AND (LOWER(sr.weak_topic)=LOWER(r.sub_topic) OR LOWER(sr.weak_topic)=LOWER(r.title)))
    LEFT JOIN feedback f ON sr.id = f.request_id
    ORDER BY sr.id DESC
    ";
    $result = mysqli_query($conn, $query);

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    return $data;
}

/* Function to get analytics data */
function getAnalyticsData($data)
{

    $analytics = [
        'total_feedbacks' => 0,
        'subjects' => [],
        'topics' => [],
        'replied_feedbacks' => 0,
        'pending_replies' => 0
    ];

    foreach ($data as $row) {

        $analytics['total_feedbacks']++;

        $subject = $row['subject'];
        if (!isset($analytics['subjects'][$subject])) {
            $analytics['subjects'][$subject] = 0;
        }
        $analytics['subjects'][$subject]++;

        $topic = $row['weak_topic'];
        if (!isset($analytics['topics'][$topic])) {
            $analytics['topics'][$topic] = 0;
        }
        $analytics['topics'][$topic]++;

        if (!empty($row['faculty_reply'])) {
            $analytics['replied_feedbacks']++;
        }
        else {
            $analytics['pending_replies']++;
        }
    }

    return $analytics;
}

/* SAVE FACULTY REPLY */
if (isset($_POST['reply'])) {

    $id = $_POST['feedback_id'];
    $reply = $_POST['faculty_reply'];

    saveFacultyReply($conn, $id, $reply);

    header("Location: resource_feedback.php");
    exit();
}

/* Fetch data */
$data = getFeedbackData($conn);
$analytics = getAnalyticsData($data);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Resource & Feedback | EduSmart</title>

<script src="https://cdn.tailwindcss.com"></script>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>

body{
font-family:'Plus Jakarta Sans',sans-serif;
background:linear-gradient(135deg,#f8fafc 0%,#f1f5f9 100%);
}

.sidebar-glass{
background:rgba(255,255,255,0.95);
backdrop-filter:blur(10px);
border-right:1px solid rgba(226,232,240,0.5);
}

.stat-card:hover{
transform:translateY(-4px);
}

</style>

</head>

<body class="min-h-screen flex">

<!-- SIDEBAR -->

<aside class="w-64 sidebar-glass hidden lg:flex flex-col p-6 fixed h-full">

<div class="flex items-center gap-3 mb-10">

<div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center text-white font-bold">
ES
</div>

<span class="text-xl font-bold text-slate-800">
EduSmart
</span>

</div>

<nav class="space-y-2 flex-1">

<a href="dashboard.php"
class="block px-4 py-3 text-slate-600 hover:bg-slate-50 rounded-xl font-medium">
Dashboard
</a>

<a href="upload_resource.php"
class="block px-4 py-3 text-slate-600 hover:bg-slate-50 rounded-xl font-medium">
Upload Resources
</a>

<a href="resource_feedback.php"
class="block px-4 py-3 bg-indigo-50 text-indigo-600 rounded-xl font-semibold">
Resource & Feedback
</a>

<a href="analytics.php"
class="block px-4 py-3 text-slate-600 hover:bg-slate-50 rounded-xl font-medium">
Analytics
</a>

</nav>

<div class="mt-auto bg-slate-900 text-white p-4 rounded-2xl">

<p class="text-xs text-slate-400">Role</p>
<p class="font-bold">Faculty</p>

</div>

</aside>


<!-- MAIN -->

<main class="flex-1 lg:ml-64 p-8">

<header class="flex justify-between items-center mb-12">

<div>

<h2 class="text-4xl font-bold text-slate-800">
Resource & Feedback Management
</h2>

<p class="text-slate-500 mt-2 text-lg">
Track resources and respond to student feedback
</p>

</div>

<a href="../auth/logout.php"
class="bg-red-600 text-white px-6 py-2 rounded-xl font-semibold hover:bg-red-700">
Logout
</a>

</header>


<!-- ANALYTICS -->

<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">

<div class="bg-white rounded-2xl shadow border p-6 stat-card">

<p class="text-sm text-slate-500">Total Feedback</p>

<p class="text-4xl font-bold text-indigo-600">
<?php echo $analytics['total_feedbacks']; ?>
</p>

</div>

<div class="bg-white rounded-2xl shadow border p-6 stat-card">

<p class="text-sm text-slate-500">Replied</p>

<p class="text-4xl font-bold text-green-600">
<?php echo $analytics['replied_feedbacks']; ?>
</p>

</div>

<div class="bg-white rounded-2xl shadow border p-6 stat-card">

<p class="text-sm text-slate-500">Pending</p>

<p class="text-4xl font-bold text-orange-600">
<?php echo $analytics['pending_replies']; ?>
</p>

</div>

<div class="bg-white rounded-2xl shadow border p-6 stat-card">

<p class="text-sm text-slate-500">Subjects</p>

<p class="text-4xl font-bold text-indigo-700">
<?php echo count($analytics['subjects']); ?>
</p>

</div>

</div>


<!-- TABLE -->

<div class="bg-white rounded-2xl shadow border overflow-x-auto">

<table class="w-full text-sm">

<thead class="bg-slate-100">

<tr>

<th class="p-4 text-left">Student</th>
<th class="p-4 text-left">Subject</th>
<th class="p-4 text-left">Topic</th>
<th class="p-4 text-left">Resource</th>
<th class="p-4 text-left">View</th>
<th class="p-4 text-left">Feedback</th>
<th class="p-4 text-center">Reply</th>

</tr>

</thead>

<tbody>

<?php if (count($data) > 0) { ?>

<?php foreach ($data as $row) { ?>

<tr class="border-t">

<td class="p-4"><?php echo htmlspecialchars($row['student']); ?></td>

<td class="p-4"><?php echo htmlspecialchars($row['subject']); ?></td>

<td class="p-4"><?php echo htmlspecialchars($row['weak_topic']); ?></td>

<td class="p-4">

<?php if ($row['title']) { ?>

<span class="bg-green-100 text-green-700 px-3 py-1 rounded text-xs">
<?php echo htmlspecialchars($row['title']); ?>
</span>

<?php
        }
        else { ?>

<span class="text-orange-600 text-xs">Pending</span>

<?php
        }?>

</td>


<td class="p-4">

<?php if ($row['resource_type'] == "youtube") { ?>

<a href="<?php echo $row['resource_link']; ?>"
target="_blank"
class="bg-red-600 text-white px-3 py-1 rounded text-xs">
Watch
</a>

<?php
        }
        elseif ($row['resource_type'] == "pdf") { ?>

<a href="<?php echo $row['file_path']; ?>"
target="_blank"
class="bg-green-600 text-white px-3 py-1 rounded text-xs">
PDF
</a>

<?php
        }?>

</td>

<td class="p-4">
<?php echo htmlspecialchars($row['feedback'] ?? "No feedback"); ?>
</td>

<td class="p-4 text-center">

<?php if ($row['faculty_reply']) { ?>

<span class="text-green-600 text-xs">
<?php echo htmlspecialchars($row['faculty_reply']); ?>
</span>

<?php
        }
        else if ($row['id']) { ?>

<form method="post" class="flex gap-2">

<input type="hidden" name="feedback_id"
value="<?php echo $row['id']; ?>">

<input type="text"
name="faculty_reply"
placeholder="Reply..."
class="border p-1 text-xs rounded"
required>

<button name="reply"
class="bg-indigo-600 text-white px-2 py-1 rounded text-xs">
Send
</button>

</form>

<?php
        }
        else { ?>

<span class="text-slate-400">-</span>

<?php
        }?>

</td>

</tr>

<?php
    }?>

<?php
}
else { ?>

<tr>

<td colspan="7" class="text-center p-8 text-slate-500">
No feedback received yet
</td>

</tr>

<?php
}?>

</tbody>

</table>

</div>

</main>

</body>
</html>