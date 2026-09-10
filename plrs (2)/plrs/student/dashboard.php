<?php
include("../config/db.php");

/* Check login */
if(!isset($_SESSION['uid'])){
header("Location: ../auth/login.php");
exit();
}

$uid = $_SESSION['uid'];

/* =====================
   USER INFO
===================== */

$user = mysqli_fetch_assoc(
mysqli_query($conn,"SELECT * FROM users WHERE id='$uid'")
);

/* =====================
   RELEVANT STATS
===================== */

// Total Requests
$req_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM student_requests WHERE user_id='$uid'");
$total_requests = mysqli_fetch_assoc($req_res)['total'] ?? 0;

// Resources Received (Matching resources found)
$received_res = mysqli_query($conn, "
    SELECT COUNT(DISTINCT sr.id) as total 
    FROM student_requests sr
    JOIN resources r ON (sr.id=r.request_id) OR (r.request_id IS NULL AND LOWER(sr.subject)=LOWER(r.subject) AND (LOWER(sr.weak_topic)=LOWER(r.sub_topic) OR LOWER(sr.weak_topic)=LOWER(r.title)))
    WHERE sr.user_id='$uid'
");
$resources_received = mysqli_fetch_assoc($received_res)['total'] ?? 0;

// Feedback Given
$feedback_res = mysqli_query($conn, "
    SELECT COUNT(*) as total 
    FROM feedback f 
    JOIN student_requests sr ON f.request_id = sr.id 
    WHERE sr.user_id='$uid'
");
$feedback_given = mysqli_fetch_assoc($feedback_res)['total'] ?? 0;

// Recent Requests Activity
$recent_requests = mysqli_query($conn, "
    SELECT sr.*, 
    CASE WHEN EXISTS (
        SELECT 1 FROM resources r 
        WHERE (sr.id=r.request_id) 
        OR (r.request_id IS NULL AND LOWER(sr.subject)=LOWER(r.subject) AND (LOWER(sr.weak_topic)=LOWER(r.sub_topic) OR LOWER(sr.weak_topic)=LOWER(r.title)))
    ) THEN 'Uploaded' ELSE 'Pending' END as status
    FROM student_requests sr 
    WHERE sr.user_id='$uid' 
    ORDER BY sr.id DESC LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>EduSmart Dashboard</title>

<script src="https://cdn.tailwindcss.com"></script>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>
body { 
font-family: 'Plus Jakarta Sans', sans-serif; 
background: #f8fafc;
}

.sidebar-glass {
background: rgba(255, 255, 255, 0.8);
backdrop-filter: blur(10px);
border-right: 1px solid rgba(226,232,240,0.8);
}

.course-card {
transition: all 0.3s ease;
}

.course-card:hover {
transform: translateY(-5px);
box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
}
</style>
</head>

<body class="min-h-screen flex">

<!-- SIDEBAR -->
<aside class="w-64 sidebar-glass hidden lg:flex flex-col p-6 fixed h-full">

<div class="flex items-center gap-3 mb-10 px-2">

<div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center">
📘
</div>

<span class="text-xl font-bold text-slate-800">
EduSmart
</span>

</div>

<nav class="space-y-2 overflow-y-auto flex-1">

<a href="dashboard.php"
class="flex items-center gap-3 px-4 py-3 bg-indigo-50 text-indigo-600 rounded-xl font-semibold transition">
Dashboard
</a>


<a href="resources.php"
class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:bg-slate-50 rounded-xl font-medium transition">
Learning Resources
</a>


<a href="search_resource.php"
class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:bg-slate-50 rounded-xl font-medium">

Search Resources

</a>

<a href="analytics.php"
class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:bg-slate-50 rounded-xl font-medium">

Analytics

</a>

<a href="take_test.php"
class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:bg-slate-50 rounded-xl font-medium">

Take Test

</a>

</nav>

<div class="mt-auto p-4 bg-slate-900 rounded-2xl text-white">

<p class="text-xs text-slate-400 mb-1">
Current Role
</p>

<p class="font-bold text-sm">
Student
</p>

</div>

</aside>


<!-- MAIN -->
<main class="flex-1 lg:ml-64 p-8">

<!-- HEADER -->
<header class="flex justify-between items-center mb-10">

<div>
<h2 class="text-3xl font-bold text-slate-800">
Welcome, <?php echo isset($user['name']) ? htmlspecialchars($user['name']) : 'Student'; ?> 👋
</h2>

<p class="text-slate-500 mt-1">
Your personalized learning journey
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
    <p class="text-slate-500 text-sm font-medium mb-2">My Requests</p>
    <h3 class="text-4xl font-bold text-slate-800"><?php echo $total_requests; ?></h3>
    <p class="text-xs text-slate-400 mt-2">Total learning requests</p>
</div>

<div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200 hover:shadow-md transition">
    <p class="text-slate-500 text-sm font-medium mb-2">Resources Received</p>
    <h3 class="text-4xl font-bold text-slate-800"><?php echo $resources_received; ?></h3>
    <p class="text-xs text-slate-400 mt-2">Uploads matching requests</p>
</div>

<div class="bg-indigo-50 p-6 rounded-3xl shadow-sm border border-indigo-100 hover:shadow-md transition">
    <p class="text-indigo-600 text-sm font-medium mb-2">Feedback Done</p>
    <h3 class="text-4xl font-bold text-indigo-700"><?php echo $feedback_given; ?></h3>
    <p class="text-xs text-indigo-500 mt-2">Responses contributed</p>
</div>

</section>


<!-- RECENT ACTIVITY -->
<section>

<div class="flex justify-between items-end mb-6">
    <h3 class="text-2xl font-bold text-slate-800">Recent Activity</h3>
    <a href="resources.php" class="text-indigo-600 text-sm font-bold hover:underline">View All →</a>
</div>

<div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
    <table class="w-full text-left border-collapse">
        <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest">Subject</th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest">Topic</th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest text-center">Status</th>
                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest text-right">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php if(mysqli_num_rows($recent_requests) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($recent_requests)): ?>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-6 py-4">
                            <span class="font-semibold text-slate-800"><?php echo htmlspecialchars($row['subject']); ?></span>
                        </td>
                        <td class="px-6 py-4 text-slate-600">
                            <?php echo htmlspecialchars($row['weak_topic']); ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <?php if($row['status'] == 'Uploaded'): ?>
                                <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold">Uploaded</span>
                            <?php else: ?>
                                <span class="bg-amber-100 text-amber-700 px-3 py-1 rounded-full text-xs font-bold">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="resources.php" class="text-indigo-600 font-bold text-sm hover:underline">Check →</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="px-6 py-12 text-center text-slate-400 italic">
                        No requests submitted yet. <a href="dashboard.php" class="text-indigo-600 not-italic font-bold">Request help →</a>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</section>

</main>

</body>
</html>
