<?php
include("../config/db.php");

if(isset($_POST['register'])){

    $name = $_POST['name'];
    $email = $_POST['email'];
    $pass = md5($_POST['password']);
    $role = $_POST['role'];

    $q = "INSERT INTO users(name,email,password,role)
          VALUES('$name','$email','$pass','$role')";

    if(mysqli_query($conn,$q)){
        header("Location: login.php");
    }else{
        $error = "Email already exists!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>PLRS Register</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="card">

    <h2>Register</h2>

    <?php if(isset($error)){ ?>
        <div class="error"><?php echo $error; ?></div>
    <?php } ?>

    <form method="post">

        <input type="text" name="name" placeholder="Full Name" required>

        <input type="email" name="email" placeholder="Email" required>

        <input type="password" name="password" placeholder="Password" required>

        <select name="role" required>
            <option value="">Select Role</option>
            <option value="student">Student</option>
            <option value="faculty">Faculty</option>
        </select>

        <button type="submit" name="register">Register</button>

    </form>

    <p>
        Already Registered?
        <a href="login.php">Login Here</a>
    </p>

</div>

</body>
</html>
