<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../auth/guard_guest.php';
$conn = getDB();

$name = $email = $mobile = $nationality = $gender = "";
$idproof = $address = $checkin = $checkout = "";
$bedtype = $roomtype = $priceperday = "";
$days = $total = $roomnumber = "";
$checkout_value = ""; 

if (isset($_POST['update'])) {

    $roomnumber   = $_POST['roomnumber'];
    $name         = $_POST['name'];
    $email        = $_POST['email'];
    $mobile       = $_POST['mobile'];
    $nationality  = $_POST['nationality'];
    $gender       = $_POST['gender'];
    $idproof      = $_POST['idproof'];
    $address      = $_POST['address'];
    $checkin      = $_POST['checkin'];
    $checkout     = $_POST['checkout'];
    $bedtype      = $_POST['bedtype'];
    $roomtype     = $_POST['roomtype'];
    $priceperday  = $_POST['priceperday'];
    $days         = $_POST['daystayed'];
    $total        = $_POST['total'];

    $sql = "UPDATE customer SET
        name='$name',
        email='$email',
        mobilenumber='$mobile',
        nationality='$nationality',
        gender='$gender',
        idproof='$idproof',
        address='$address',
        checkin='$checkin',
        checkout='$checkout',
        bedtype='$bedtype',
        roomtype='$roomtype',
        priceperday='$priceperday',
        daystayed='$days',
        totalamount='$total'
        WHERE roomnumber='$roomnumber'";

    if ($conn->query($sql)) {
        echo "<script>alert('Customer updated successfully');</script>";
    } else {
        echo "Update error: " . $conn->error;
    }
}

if(isset($_POST['search'])){
    $roomnumber = $_POST['roomnumber'];
    $checkout_value = $_POST['checkout'];

    $quer="UPDATE customer SET checkout='$checkout' WHERE roomnumber='$roomnumber'";
    if($conn->query($quer)=== TRUE){
        $sql = "SELECT * FROM customer WHERE roomnumber='$roomnumber'";
        $result = $conn->query($sql);

        if($result->rowCount() > 0){
            $row = $result->fetch(PDO::FETCH_ASSOC);
            $name = $row['name'];
            $email = $row['email'];
            $mobile = $row['mobilenumber'];
            $nationality = $row['nationality'];
            $gender = $row['gender'];
            $idproof = $row['idproof'];
            $address = $row['address'];
            $checkin = $row['checkin'];
            $checkout = $row['checkout'];
            $bedtype = $row['bedtype'];
            $roomtype = $row['roomtype'];
            $priceperday = $row['priceperday'];
            $days = (strtotime($checkout) - strtotime($checkin)) / (60*60*24);
            $total = $days * $priceperday;
        } else {
            echo "<script>alert('Room number not found');</script>";
        }
    } else {
        echo "Insert failed: ". $conn->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Checkout Page</title>
   <link rel="stylesheet" href="../../css/pages/update.css">
</head>
<body>
<nav style="position:relative;z-index:10;background:rgba(13,27,42,0.96);border-bottom:1px solid rgba(201,168,76,0.2);padding:0 24px;height:56px;display:flex;align-items:center;justify-content:space-between;backdrop-filter:blur(12px);">
    <span style="font-family:'Playfair Display',serif;font-size:17px;color:#fff;">Sabawyan Hotel</span>
    <a href="../public/rooms.php" style="display:inline-flex;align-items:center;gap:6px;padding:7px 16px;background:rgba(201,168,76,0.1);border:1px solid rgba(201,168,76,0.3);border-radius:6px;color:#e8c97a;font-size:13px;font-weight:600;text-decoration:none;font-family:'Inter',sans-serif;">&#8592; Back</a>
</nav>
<div class="container">
    <form action="../guest/update.php" method="POST">
        <div class="form-row">
            <label style="margin-left:170px">Room Number: </label>
            <input type="number" name="roomnumber" value="<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
echo $roomnumber; ?>">
            <button name="search" type="submit" class="btn btn-search">Search</button>
        </div>
        <div class="form-row">
            <label>Name:</label> <input type="text" name="name"  style="margin-left:38px" value="<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
echo $name; ?>" >
            <label>Email:</label> <input type="email" name="email" style="margin-left:60px" value="<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
echo $email; ?>" >
            <label>Mobile:</label> <input type="number" name="mobile" style="margin-left:50px" value="<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
echo $mobile; ?>" >
        </div>
        <div class="form-row">
            <label>Nationality:</label> <input type="text" name="nationality" value="<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
echo $nationality; ?>" >
            <label>Gender:</label> <input type="text" name="gender" style="margin-left:50px" value="<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
echo $gender; ?>" >
            <label>ID Proof:</label> <input type="text" name="idproof" style="margin-left:50px" value="<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
echo $idproof; ?>" >
        </div>
        
        <div class="form-row">
            <label>Address:</label> <input type="text" name="address" style="margin-left:15px" value="<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
echo $address; ?>" >
            <label>Check-In Date:</label> <input type="date" name="checkin" value="<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
echo $checkin; ?>" >
            <label>Check-Out Date:</label> <input type="date" name="checkout" value="<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
echo $checkout_value; ?>">
        </div>
        <div class="form-row">
            <label>Bed Type:</label> <input type="text" name="bedtype" value="<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
echo $bedtype; ?>" >
            <label>Room Type:</label> <input type="text" name="roomtype" style="margin-left:25px" value="<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
echo $roomtype; ?>" >
        </div>
        <div class="form-row">
            <label>Price/Day:</label> <input type="number" name="priceperday" value="<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
echo $priceperday; ?>" >
            <label>Stayed Days:</label> <input type="number" name="daystayed" style="margin-left:15px" value="<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
echo $days; ?>" >
            <label>Total:</label> <input type="number" name="total" value="<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
echo $total; ?>" >
        </div>

        <div class="form-row">
            <button type="submit" name="update" class="btn btn-checkout">Update customer</button>
        </div>
    </form>
</div>

</body>
</html>
