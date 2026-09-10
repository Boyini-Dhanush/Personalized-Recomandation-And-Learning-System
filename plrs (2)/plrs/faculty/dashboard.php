<?php
include("../config/db.php");

if(!isset($_SESSION['uid']) || $_SESSION['role'] !== 'faculty'){
    header("Location: ../auth/login.php");
    exit();
}

/* Faculty Info */
$faculty = mysqli_fetch_assoc(
    mysqli_query($conn,"SELECT * FROM users WHERE id='{$_SESSION['uid']}'")
);

/* Stats */
$total_students = mysqli_fetch_assoc(
    mysqli_query($conn,"SELECT COUNT(*) AS total FROM users WHERE role='student'")
)['total'];

/* Weak Students */
$weak_students = mysqli_fetch_assoc(
    mysqli_query($conn,"
        SELECT COUNT(*) AS weak 
        FROM student_requests 
        WHERE study_hours < 2
    ")
)['weak'];

/* Average Study Hours */
$avg_score = mysqli_fetch_assoc(
    mysqli_query($conn,"
        SELECT ROUND(AVG(study_hours)) AS avg_hours 
        FROM student_requests
    ")
)['avg_hours'];

/* Student Requests + Resources */
$students = mysqli_query($conn,"
SELECT sr.*, u.name
FROM student_requests sr
JOIN users u ON sr.user_id = u.id
ORDER BY sr.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EduSmart | Faculty Dashboard</title>

<script src="https://cdn.tailwindcss.com"></script>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>
body{
    font-family: 'Plus Jakarta Sans', sans-serif;
    background:#f8fafc;
}
.sidebar-glass{
    background:rgba(255,255,255,0.85);
    backdrop-filter:blur(10px);
    border-right:1px solid #e5e7eb;
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
    <span class="text-xl font-bold text-slate-800">EduSmart</span>
</div>

<nav class="space-y-2 flex-1">

<a href="dashboard.php"
class="block px-4 py-3 bg-indigo-50 text-indigo-600 rounded-xl font-semibold transition">
Dashboard
</a>

<a href="upload_resource.php"
class="block px-4 py-3 text-slate-600 hover:bg-slate-50 rounded-xl transition font-medium">
Upload Resources
</a>

<a href="resource_feedback.php"
class="block px-4 py-3 text-slate-600 hover:bg-slate-50 rounded-xl transition font-medium">
Resource & Feedback
</a>

<a href="analytics.php"
class="block px-4 py-3 text-slate-600 hover:bg-slate-50 rounded-xl transition font-medium">
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

<header class="flex justify-between items-center mb-10">
    <div>
        <h2 class="text-3xl font-bold text-slate-800">
            Welcome, <?php echo htmlspecialchars($faculty['name']); ?> 👋
        </h2>
        <p class="text-slate-500 mt-1">
            Faculty Performance Overview
        </p>
    </div>

    <a href="../auth/logout.php"
       class="bg-red-500 text-white px-4 py-2 rounded-lg font-semibold hover:bg-red-600 transition">
        Logout
    </a>
</header>

<!-- STATS -->
<section class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">

<div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200 hover:shadow-md transition">
<p class="text-slate-500 text-sm font-medium mb-2">Total Students</p>
<h3 class="text-4xl font-bold text-slate-800"><?php echo $total_students; ?></h3>
<p class="text-xs text-slate-400 mt-2">Currently enrolled</p>
</div>

<div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200 hover:shadow-md transition">
<p class="text-slate-500 text-sm font-medium mb-2">Weak Students</p>
<h3 class="text-4xl font-bold text-red-500"><?php echo $weak_students; ?></h3>
<p class="text-xs text-slate-400 mt-2">Need support</p>
</div>

<div class="bg-indigo-50 p-6 rounded-3xl shadow-sm border border-indigo-100 hover:shadow-md transition">
<p class="text-indigo-600 text-sm font-medium mb-2">Average Study Hours</p>
<h3 class="text-4xl font-bold text-indigo-700"><?php echo $avg_score; ?></h3>
<p class="text-xs text-indigo-500 mt-2">hours per student</p>
</div>

</section>

<!-- STUDENT REQUEST TABLE -->
<section>

<h3 class="text-2xl font-bold text-slate-800 mb-4">
Student Learning Requests
</h3>

<?php if(isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-xl">
    Resource uploaded successfully!
</div>
<?php elseif(isset($_GET['msg']) && $_GET['msg'] == 'error'): ?>
<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-xl">
    Something went wrong during upload.
</div>
<?php endif; ?>

<div class="bg-white rounded-3xl shadow-sm border overflow-x-auto">

<table class="w-full text-sm">

<thead class="bg-slate-100 sticky top-0">

<tr>
<th class="p-4 text-left font-semibold text-slate-700">Student</th>
<th class="p-4 text-left font-semibold text-slate-700">Subject</th>
<th class="p-4 text-left font-semibold text-slate-700">Weak Topic</th>
<th class="p-4 text-left font-semibold text-slate-700">Learning Goal</th>
<th class="p-4 text-center font-semibold text-slate-700">Study Hours</th>
<th class="p-4 text-center font-semibold text-slate-700">Requested At</th>
<th class="p-4 text-center font-semibold text-slate-700">Resource</th>
</tr>

</thead>

<tbody>

<?php while($row = mysqli_fetch_assoc($students)){ 

/* Get the first resource for this request */
$resource_result = mysqli_query($conn,"
SELECT * FROM resources 
WHERE request_id = '{$row['id']}' 
OR (request_id IS NULL AND LOWER(subject)=LOWER('".mysqli_real_escape_string($conn, $row['subject'])."') AND (LOWER(sub_topic)=LOWER('".mysqli_real_escape_string($conn, $row['weak_topic'])."') OR LOWER(title)=LOWER('".mysqli_real_escape_string($conn, $row['weak_topic'])."')))
LIMIT 1
");

$resource = mysqli_fetch_assoc($resource_result);
$title = $resource['title'] ?? null;
$type = $resource['resource_type'] ?? null;
$link = $resource['resource_link'] ?? null;
$file = $resource['file_path'] ?? null;

?>

<tr class="border-t hover:bg-slate-50 transition-colors">

<td class="p-4 text-left text-slate-800"><?php echo htmlspecialchars($row['name']); ?></td>

<td class="p-4 text-left text-slate-700"><?php echo htmlspecialchars($row['subject']); ?></td>

<td class="p-4 text-left text-slate-700"><?php echo htmlspecialchars($row['weak_topic']); ?></td>

<td class="p-4 text-left text-slate-700"><?php echo htmlspecialchars($row['learning_goal']); ?></td>

<td class="p-4 text-center text-slate-700 font-medium"><?php echo $row['study_hours']; ?> hrs</td>

<td class="p-4 text-center text-slate-600 text-xs"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>

<td class="p-4 text-center">

<?php if($title){ ?>

<div class="inline-block">
<div class="text-green-600 font-semibold text-xs mb-1">
✓ Uploaded
</div>

<div class="text-xs text-slate-600 mb-2"><?php echo htmlspecialchars($title); ?></div>

<?php if($type=="youtube"){ ?>

<a href="<?php echo htmlspecialchars($link); ?>"
target="_blank"
class="inline-block bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs font-medium hover:bg-blue-200 transition">
▶ Watch Video
</a>

<?php } ?>

<?php if($type=="pdf"){ ?>

<a href="<?php echo htmlspecialchars($file); ?>"
target="_blank"
class="inline-block bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs font-medium hover:bg-blue-200 transition">
📄 Download PDF
</a>

<?php } ?>
</div>

<?php } else { ?>

<form action="quick_upload.php" method="POST" enctype="multipart/form-data" id="form_<?php echo $row['id']; ?>" class="inline-block">
    <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
    <input type="hidden" name="subject" value="<?php echo htmlspecialchars($row['subject']); ?>">
    <input type="hidden" name="topic" value="<?php echo htmlspecialchars($row['weak_topic']); ?>">
    <input type="hidden" name="title" value="Resource: <?php echo htmlspecialchars($row['weak_topic']); ?>">
    <input type="file" name="pdf_file" id="file_<?php echo $row['id']; ?>" class="hidden" onchange="document.getElementById('form_<?php echo $row['id']; ?>').submit()">
    <button type="button" onclick="document.getElementById('file_<?php echo $row['id']; ?>').click()"
            class="inline-block bg-indigo-600 text-white px-3 py-2 rounded-lg text-xs font-medium hover:bg-indigo-700 transition">
        + Upload
    </button>
</form>

<?php } ?>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</section>

</main>

</body>
</html>