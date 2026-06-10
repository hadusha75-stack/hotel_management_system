<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../auth/guard_guest.php';
$conn = getDB();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Details</title>

    <style>
        table, th, td {
            border-collapse: collapse;
            border: 1px solid black;
            padding: 5px;
        }
        th, td {
            width: 160px;
        }
        body {
            background: #cef702;
        }
    </style>
</head>
<body>
<style>
.exit {
    width: 30px;
    height: 30px;
    position: absolute;
    top: 20px;
    right: 30px;
    cursor: pointer;
    transition: all 0.3s ease;
    filter: drop-shadow(0 0 6px rgba(0, 255, 180, 0.6));
}
.exit:hover {
    transform: rotate(90deg) scale(1.15);
    filter: drop-shadow(0 0 12px rgba(0, 255, 220, 1));
}
.exit:active {
    transform: scale(0.9);
    filter: drop-shadow(0 0 4px rgba(255, 80, 80, 0.8));
}</style>
<nav style="position:relative;z-index:10;background:rgba(13,27,42,0.96);border-bottom:1px solid rgba(201,168,76,0.2);padding:0 24px;height:56px;display:flex;align-items:center;justify-content:space-between;backdrop-filter:blur(12px);">
    <span style="font-family:'Playfair Display',serif;font-size:17px;color:#fff;">Sabawyan Hotel</span>
    <a href="../public/rooms.php" style="display:inline-flex;align-items:center;gap:6px;padding:7px 16px;background:rgba(201,168,76,0.1);border:1px solid rgba(201,168,76,0.3);border-radius:6px;color:#e8c97a;font-size:13px;font-weight:600;text-decoration:none;font-family:'Inter',sans-serif;">&#8592; Back</a>
</nav>
 
<br><br>

<table border="2">
    <tr>
        <th>Name</th>
        <th>Email</th>
        <th>Mobile</th>
        <th>Nationality</th>
        <th>Gender</th>
        <th>ID Proof</th>
        <th>Address</th>
        <th>Check-in</th>
        <th>Check-out</th>
        <th>Room Number</th>
        <th>Bed Type</th>
        <th>Room Type</th>
        <th>Price/Day</th>
        <th>Stayed Days</th>
        <th>Total Amount</th>
    </tr>

<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../auth/guard_guest.php';
$sql = "SELECT * FROM deleted_customers";
$result = $conn->query($sql);

// If query fails → show why
if (!$result) {
    die("<tr><td colspan='15'>SQL Error: " . "DB Error" . "</td></tr>");
}

// If there is data → display it
if ($result->rowCount() > 0) {
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {

        echo "<tr>
            <td>".$row['name']."</td>
            <td>".$row['email']."</td>
            <td>".$row['mobilenumber']."</td>
            <td>".$row['nationality']."</td>
            <td>".$row['gender']."</td>
            <td>".$row['idproof']."</td>
            <td>".$row['address']."</td>
            <td>".$row['checkin']."</td>
            <td>".$row['checkout']."</td>
            <td>".$row['roomnumber']."</td>
            <td>".$row['bedtype']."</td>
            <td>".$row['roomtype']."</td>
            <td>".$row['priceperday']."</td>
            <td>".$row['daystayed']."</td>
            <td>".$row['totalamount']."</td>
        </tr>";
    }
} else {
    echo "<tr><td colspan='15'>No Data Found</td></tr>";
}
?>

</table>

</body>
</html>
