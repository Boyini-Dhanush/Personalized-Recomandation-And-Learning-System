<?php
include("../config/db.php");

/* =====================
   LOGIN
===================== */

if(isset($_POST['login'])){

$email = mysqli_real_escape_string($conn,$_POST['login_email']);
$password = md5($_POST['login_password']);

$query = "SELECT * FROM users WHERE email='$email' AND password='$password'";

$result = mysqli_query($conn,$query);

if(mysqli_num_rows($result) == 1){

$user = mysqli_fetch_assoc($result);

$_SESSION['uid'] = $user['id'];
$_SESSION['role'] = $user['role'];

if($user['role'] == "student"){
header("Location: ../student/dashboard.php");
exit();
}

if($user['role'] == "faculty"){
header("Location: ../faculty/dashboard.php");
exit();
}

}else{

$login_error = "Invalid Email or Password";

}

}

/* =====================
   REGISTER
===================== */
if(isset($_POST['register'])){

    $name  = mysqli_real_escape_string($conn,$_POST['reg_name']);
    $email = mysqli_real_escape_string($conn,$_POST['reg_email']);
    $pass  = md5($_POST['reg_password']);
    $role  = $_POST['reg_role'];

    $check = mysqli_query($conn,
        "SELECT id FROM users WHERE email='$email'"
    );

    if(mysqli_num_rows($check)>0){

        $reg_error = "Email already exists";

    }else{

        $q = "INSERT INTO users(name,email,password,role)
              VALUES('$name','$email','$pass','$role')";

        mysqli_query($conn,$q);

        $success = "Registration successful! Please login.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EduSmart | Login</title>

<script src="https://cdn.tailwindcss.com"></script>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>

body { 
font-family: 'Plus Jakarta Sans', sans-serif; 
background: radial-gradient(at 0% 0%, hsla(253,16%,7%,1) 0, transparent 50%), 
            radial-gradient(at 50% 0%, hsla(225,39%,30%,1) 0, transparent 50%), 
            radial-gradient(at 100% 0%, hsla(339,49%,30%,1) 0, transparent 50%);
background-color: #0f172a;
}

.bg-gradient-animate {
background: linear-gradient(-45deg, #4338ca, #6d28d9, #be185d, #1d4ed8);
background-size: 400% 400%;
animation: gradient 12s ease infinite;
}

@keyframes gradient {
0% { background-position: 0% 50%; }
50% { background-position: 100% 50%; }
100% { background-position: 0% 50%; }
}

.custom-input {
background: #f8fafc;
border: 1px solid #e2e8f0;
}

.content-area {
height: 400px;
display: flex;
flex-direction: column;
justify-content: center;
}

</style>
</head>

<body class="min-h-screen flex items-center justify-center p-6">

<div class="max-w-4xl w-full grid grid-cols-1 md:grid-cols-2 shadow-[0_35px_60px_-15px_rgba(0,0,0,0.5)] rounded-[2.5rem] overflow-hidden bg-white/95">

<!-- LEFT PANEL -->
<div class="hidden md:flex bg-gradient-animate p-10 flex-col justify-center text-white text-center">

<h1 class="text-4xl font-extrabold mb-3">
EduSmart
</h1>

<p class="text-blue-100 text-lg">
Your personalized learning ecosystem
</p>

</div>


<!-- RIGHT PANEL -->
<div class="p-8 md:p-10 bg-white">

<div class="flex gap-8 mb-6 border-b">

<button onclick="toggleForm('login')" id="login-tab"
class="pb-3 text-indigo-600 border-b-2 border-indigo-600 font-bold">

Login

</button>

<button onclick="toggleForm('register')" id="register-tab"
class="pb-3 text-gray-400 font-bold">

Register

</button>

</div>


<div class="content-area">

<!-- MESSAGES -->

<?php if(isset($login_error)){ ?>
<p class="text-red-600 text-center mb-3">
<?php echo $login_error; ?>
</p>
<?php } ?>

<?php if(isset($reg_error)){ ?>
<p class="text-red-600 text-center mb-3">
<?php echo $reg_error; ?>
</p>
<?php } ?>

<?php if(isset($success)){ ?>
<p class="text-green-600 text-center mb-3">
<?php echo $success; ?>
</p>
<?php } ?>


<!-- LOGIN FORM -->

<form id="login-form" method="post" class="space-y-4">

<input type="email"
name="login_email"
placeholder="Email"
class="custom-input w-full px-4 py-3 rounded-xl"
required>

<input type="password"
name="login_password"
placeholder="Password"
class="custom-input w-full px-4 py-3 rounded-xl"
required>

<button name="login"
class="w-full bg-slate-900 text-white py-3.5 rounded-xl font-bold">

Sign In

</button>

</form>


<!-- REGISTER FORM -->

<form id="register-form" method="post" class="hidden space-y-3">

<div class="grid grid-cols-2 gap-3">

<input type="text"
name="reg_name"
placeholder="Name"
class="custom-input px-4 py-2.5 rounded-xl"
required>

<select name="reg_role"
class="custom-input px-4 py-2.5 rounded-xl"
required>

<option value="student">Student</option>
<option value="faculty">Faculty</option>

</select>

</div>

<input type="email"
name="reg_email"
placeholder="Email"
class="custom-input px-4 py-2.5 rounded-xl"
required>

<input type="password"
name="reg_password"
placeholder="Password"
class="custom-input px-4 py-2.5 rounded-xl"
required>


<button name="register"
class="w-full bg-indigo-600 text-white py-3.5 rounded-xl font-bold">

Join Platform

</button>

</form>

</div>

</div>

</div>


<script>

function toggleForm(type){

const loginForm = document.getElementById('login-form');
const regForm = document.getElementById('register-form');

const loginTab = document.getElementById('login-tab');
const regTab = document.getElementById('register-tab');

if(type==='login'){

loginForm.classList.remove('hidden');
regForm.classList.add('hidden');

loginTab.classList.add('text-indigo-600','border-b-2');
regTab.classList.remove('text-indigo-600','border-b-2');

}
else{

regForm.classList.remove('hidden');
loginForm.classList.add('hidden');

regTab.classList.add('text-indigo-600','border-b-2');
loginTab.classList.remove('text-indigo-600','border-b-2');

}

}

</script>

</body>
</html>