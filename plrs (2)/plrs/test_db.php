<?php
include("config/db.php");
$res = mysqli_query($conn, "DESCRIBE student_requests");
while($row = mysqli_fetch_assoc($res)){
    echo $row['Field'] . "\n";
}
?>
