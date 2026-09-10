<?php
include("../config/db.php");

if(!isset($_SESSION['uid'])){
    header("Location: ../auth/login.php");
    exit();
}

$uid=$_SESSION['uid'];

/* SAVE LEARNING REQUEST */

if(isset($_POST['save'])){

$subject=$_POST['subject'];
$weak=$_POST['weak_topic'];
$goal=$_POST['goal'];
$hours=$_POST['study_hours'];

mysqli_query($conn,"
INSERT INTO student_requests
(user_id,subject,weak_topic,learning_goal,study_hours)
VALUES
('$uid','$subject','$weak','$goal','$hours')
");

$success="Learning request saved successfully!";
}


/* SAVE FEEDBACK */

if(isset($_POST['submit_feedback'])){

$request_id=$_POST['request_id'];
$feedback=$_POST['feedback'];
$faculty_name=$_POST['faculty_name'];

mysqli_query($conn,"
INSERT INTO feedback(user_id,request_id,faculty_name,feedback)
VALUES('$uid','$request_id','$faculty_name','$feedback')
");

}


/* FETCH RESOURCES */

$resources=mysqli_query($conn,"
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
ON sr.id=r.request_id

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

<title>Learning Request</title>

<script src="https://cdn.tailwindcss.com"></script>

</head>

<body class="bg-slate-100 min-h-screen p-10">

<div class="max-w-5xl mx-auto bg-white p-8 rounded-2xl shadow">

<div class="flex items-center justify-between mb-4">
    <h2 class="text-2xl font-bold">Enter Learning Request</h2>
    <a href="dashboard.php" class="text-indigo-600 hover:underline font-medium">← Back</a>
</div>

<?php if(isset($success)){ ?>
<p class="text-green-600 mb-4"><?php echo $success; ?></p>
<?php } ?>

<form method="post" class="grid grid-cols-2 gap-4">

<div>
<label class="font-semibold">Subject</label>

<input type="text"
name="subject"
class="w-full border rounded-lg p-2"
required>

</div>


<div>
<label class="font-semibold">Weak Sub Topic</label>

<input type="text"
name="weak_topic"
class="w-full border rounded-lg p-2">

</div>


<div class="col-span-2">

<label class="font-semibold">
What do you want to learn?
</label>

<textarea
name="goal"
class="w-full border rounded-lg p-2">
</textarea>

</div>


<div>

<label class="font-semibold">
Study Hours Per Day
</label>

<input type="number"
name="study_hours"
class="w-full border rounded-lg p-2">

</div>


<div class="col-span-2">

<button name="save"
class="w-full bg-indigo-600 text-white py-3 rounded-lg">

Submit Learning Request

</button>

</div>

</form>

</html>