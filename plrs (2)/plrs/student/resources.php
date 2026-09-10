<?php
include("../config/db.php");

if(!isset($_SESSION['uid'])){
    header("Location: ../auth/login.php");
    exit();
}

$uid = $_SESSION['uid'];

/* USER INFO */
$user = mysqli_fetch_assoc(
    mysqli_query($conn,"SELECT * FROM users WHERE id='$uid'")
);

/* SAVE REQUEST RESOURCE */
if(isset($_POST['request_resource'])){
    $req_subject = mysqli_real_escape_string($conn, $_POST['req_subject']);
    $req_topic = mysqli_real_escape_string($conn, $_POST['req_topic']);
    $req_goal = mysqli_real_escape_string($conn, $_POST['req_goal']);
    $req_hours = (int)$_POST['req_hours'];

    mysqli_query($conn, "
        INSERT INTO student_requests (user_id, subject, weak_topic, learning_goal, study_hours)
        VALUES ('$uid', '$req_subject', '$req_topic', '$req_goal', '$req_hours')
    ");

    $request_success = "Learning resource requested successfully!";
}

/* DELETE REQUEST */
if(isset($_POST['delete_request'])){
    $del_id = mysqli_real_escape_string($conn, $_POST['delete_request_id']);
    
    mysqli_query($conn, "DELETE FROM feedback WHERE request_id='$del_id'");
    mysqli_query($conn, "DELETE FROM student_requests WHERE id='$del_id' AND user_id='$uid'");
    
    $delete_success = "Request deleted successfully!";
}

/* SAVE FEEDBACK */
if(isset($_POST['submit_feedback'])){

$request_id = $_POST['request_id'];
$feedback = $_POST['feedback'];
$faculty_name = $_POST['faculty_name'];

mysqli_query($conn,"
INSERT INTO feedback(user_id,request_id,faculty_name,feedback)
VALUES('$uid','$request_id','$faculty_name','$feedback')
");

}

/* FETCH RESOURCES */

$resources = mysqli_query($conn,"
SELECT DISTINCT sr.id as request_id,
sr.subject,
sr.weak_topic,
r.title,
r.faculty_name,
r.resource_link,
f.feedback,
f.faculty_reply

FROM student_requests sr

LEFT JOIN resources r
ON (sr.id=r.request_id) OR (r.request_id IS NULL AND LOWER(sr.subject)=LOWER(r.subject) AND (LOWER(sr.weak_topic)=LOWER(r.sub_topic) OR LOWER(sr.weak_topic)=LOWER(r.title)))

LEFT JOIN feedback f
ON sr.id=f.request_id
AND f.user_id='$uid'

WHERE sr.user_id='$uid'

ORDER BY sr.id DESC
");
?>

<!DOCTYPE html>
<html>
<head>

<title>Learning Resources</title>

<script src="https://cdn.tailwindcss.com"></script>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>

body{
font-family:'Plus Jakarta Sans',sans-serif;
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

<span class="text-xl font-bold text-slate-800">
EduSmart
</span>

</div>


<nav class="space-y-2 flex-1">

<a href="dashboard.php"
class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:bg-slate-50 rounded-xl font-medium">
Dashboard
</a>


<a href="resources.php"
class="flex items-center gap-3 px-4 py-3 bg-indigo-50 text-indigo-600 rounded-xl font-semibold">
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

<header class="flex justify-between items-center mb-10">

<div>

<h2 class="text-3xl font-bold text-slate-800">

Welcome, <?php echo $user['name']; ?>

</h2>

<p class="text-slate-500">

Your personalized learning journey

</p>

</div>


<a href="../auth/logout.php"
class="bg-red-500 text-white px-4 py-2 rounded-lg font-semibold hover:bg-red-600">

Logout

</a>

</header>



<!-- PAGE TITLE & ACTIONS -->
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <h3 class="text-2xl font-bold text-slate-800">
        Available Learning Resources
    </h3>
    <button onclick="document.getElementById('requestModal').classList.remove('hidden')" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-indigo-700 transition flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
        </svg>
        Request New Resource
    </button>
</div>

<?php if(isset($request_success)): ?>
<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6">
    <strong class="font-bold">Success!</strong>
    <span class="block sm:inline"><?php echo htmlspecialchars($request_success); ?></span>
</div>
<?php endif; ?>

<?php if(isset($delete_success)): ?>
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6">
    <strong class="font-bold">Deleted!</strong>
    <span class="block sm:inline"><?php echo htmlspecialchars($delete_success); ?></span>
</div>
<?php endif; ?>



<!-- TABLE -->

<div class="bg-white rounded-3xl shadow-sm border overflow-x-auto">

<table class="w-full text-sm">

<thead class="bg-slate-100">

<tr>

<th class="p-3 text-left">Subject</th>
<th class="p-3">Topic</th>
<th class="p-3">Title</th>
<th class="p-3">Faculty</th>
<th class="p-3">Access</th>
<th class="p-3">Feedback</th>
<th class="p-3">Action</th>

</tr>

</thead>


<tbody>

<?php while($r=mysqli_fetch_assoc($resources)){ ?>

<tr class="border-t hover:bg-slate-50">

<td class="p-3">
<?php echo $r['subject']; ?>
</td>

<td class="p-3 text-center">
<?php echo $r['weak_topic']; ?>
</td>


<td class="p-3 text-center">

<?php
if($r['title'])
echo $r['title'];
else
echo "<span class='text-orange-500 font-semibold'>Pending</span>";
?>

</td>


<td class="p-3 text-center">

<?php
if($r['faculty_name'])
echo "<span class='text-indigo-600 font-semibold'>".$r['faculty_name']."</span>";
else
echo "-";
?>

</td>


<td class="p-3 text-center">

<?php if($r['resource_link']){ ?>

<a href="<?php echo $r['resource_link']; ?>"
target="_blank"
class="text-blue-600 underline">

Open Resource

</a>

<?php }else{

echo "<span class='text-red-500 font-semibold'>Pending</span>";

} ?>

</td>



<td class="p-3 text-center">

<?php if($r['resource_link']){ ?>


<?php if($r['feedback']){ ?>

<div class="text-green-600 font-semibold">

Your Feedback

</div>

<div class="text-gray-700 mb-1">

<?php echo $r['feedback']; ?>

</div>


<?php if($r['faculty_reply']){ ?>

<div class="bg-green-50 border border-green-200 p-2 rounded text-sm">

<strong>Faculty Reply:</strong><br>

<?php echo $r['faculty_reply']; ?>

</div>

<?php }else{ ?>

<span class="text-orange-500 text-sm">

Waiting for faculty reply

</span>

<?php } ?>


<?php }else{ ?>


<form method="post">

<input type="hidden"
name="request_id"
value="<?php echo $r['request_id']; ?>">

<input type="hidden"
name="faculty_name"
value="<?php echo $r['faculty_name']; ?>">

<input type="text"
name="feedback"
placeholder="Write feedback"
class="border p-1 rounded w-full mb-2"
required>

<button name="submit_feedback"
class="bg-indigo-600 text-white px-3 py-1 rounded">

Submit

</button>

</form>

<?php } ?>


<?php }else{

echo "-";

} ?>

</td>

<td class="p-3 text-center">
    <form method="post" onsubmit="return confirm('Are you sure you want to delete this request?');">
        <input type="hidden" name="delete_request_id" value="<?php echo $r['request_id']; ?>">
        <button type="submit" name="delete_request" class="text-white bg-red-500 hover:bg-red-600 font-semibold px-3 py-1 rounded text-xs transition shadow-sm">
            Delete
        </button>
    </form>
</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

<!-- REQUEST RESOURCE MODAL -->
<div id="requestModal" class="fixed inset-0 bg-slate-900 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 relative mx-4">
        <button onclick="document.getElementById('requestModal').classList.add('hidden')" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        <h3 class="text-2xl font-bold text-slate-800 mb-4">Request Learning Resource</h3>
        <form method="post" action="">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Subject *</label>
                    <input type="text" name="req_subject" required class="w-full border border-slate-300 rounded-lg p-2.5 focus:ring-2 focus:ring-indigo-600 focus:outline-none" placeholder="e.g. Python, Web Development">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Specific Topic *</label>
                    <input type="text" name="req_topic" required class="w-full border border-slate-300 rounded-lg p-2.5 focus:ring-2 focus:ring-indigo-600 focus:outline-none" placeholder="e.g. Pandas DataFrames, React Hooks">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">What do you want to learn? (Goal)</label>
                    <textarea name="req_goal" rows="3" class="w-full border border-slate-300 rounded-lg p-2.5 focus:ring-2 focus:ring-indigo-600 focus:outline-none" placeholder="e.g. I want to build data analysis pipelines"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Study Hours Per Day</label>
                    <input type="number" name="req_hours" class="w-full border border-slate-300 rounded-lg p-2.5 focus:ring-2 focus:ring-indigo-600 focus:outline-none" placeholder="e.g. 2">
                </div>
                <button type="submit" name="request_resource" class="w-full bg-indigo-600 text-white font-bold py-3 rounded-lg hover:bg-indigo-700 transition">Submit Request</button>
            </div>
        </form>
    </div>
</div>

</main>

</body>
</html>