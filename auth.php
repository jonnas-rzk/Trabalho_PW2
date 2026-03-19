<?php
include "db.php";

$login = $_POST['login'];
$pwd = md5($_POST['pwd']);

$sql = "SELECT * FROM users WHERE login='$login' AND pwd='$pwd'";
$result = $conn->query($sql);

if($result->num_rows == 1){

    $_SESSION['user'] = $login;
    header("Location: dashboard.php");

}else{

    header("Location: index.php");
}



?>