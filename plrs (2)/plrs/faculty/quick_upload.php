<?php
include("../config/db.php");

if(!isset($_SESSION['uid']) || $_SESSION['role'] != 'faculty'){
    header("Location: ../auth/login.php");
    exit();
}

$faculty = mysqli_fetch_assoc(
    mysqli_query($conn,"SELECT * FROM users WHERE id='{$_SESSION['uid']}'")
);

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['pdf_file'])){
    $request_id = mysqli_real_escape_string($conn, $_POST['request_id']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $topic = mysqli_real_escape_string($conn, $_POST['topic']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    
    $faculty_name = $faculty['name'];
    $faculty_id = $faculty['id'];
    
    $target = "../uploads/";
    if(!is_dir($target)){
        mkdir($target, 0755, true);
    }
    
    $file_name = time()."_".$_FILES['pdf_file']['name'];
    $file_path = $target . $file_name;
    
    if(move_uploaded_file($_FILES['pdf_file']['tmp_name'], $file_path)){
        $type = "pdf";
        $link = $file_path;
        
        $insert = mysqli_query($conn,"
            INSERT INTO resources
            (faculty_id, faculty_name, request_id, subject, sub_topic, title, resource_type, resource_link, file_path)
            VALUES
            ('$faculty_id', '$faculty_name', '$request_id', '$subject', '$topic', '$title', '$type', '$link', '$file_path')
        ");
        
        if($insert){
            header("Location: dashboard.php?msg=success");
            exit();
        }
    }
}

header("Location: dashboard.php?msg=error");
exit();
?>
