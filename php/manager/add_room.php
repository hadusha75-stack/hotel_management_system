<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../auth/guard_manager.php';
$conn = getDB();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $roomnumber = $_POST['roomnumber'] ?? '';
    $bedtype = $_POST['bedtype'] ?? '';
    $roomtype = $_POST['roomtype'] ?? '';
    $price = $_POST['Price'] ?? '';

    if($roomnumber && $bedtype && $roomtype && $price){
        $sql = "INSERT INTO rooms (roomnumber, bedtype, roomtype, price, status,cleanDerty) 
                VALUES ('$roomnumber', '$bedtype', '$roomtype', '$price', 'not booked','clean')";
        if($conn->query($sql) === false){
             echo " Error: " . $conn->error;
        }else{
    echo "<script>alert('Room added successfully'); 
window.location.href='".$_SERVER['PHP_SELF']."';</script>";
        }
    } else {
        echo " Please fill in all fields.";
    }
}
?>