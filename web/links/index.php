<?php
 require_once '../../config/var.php';
$servername = HOST;
$username = USERNAME;
$password = PASSWORD;
$database = DATABASE_NOTIFICATION;

try {
  $conn = new PDO("mysql:host=$servername;dbname=$database", $username, $password);
  // set the PDO error mode to exception
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  echo "Connected successfully";
} catch(PDOException $e) {
  echo "Connection failed: " . $e->getMessage();
}

$value = $_GET['token'];

$sql = "SELECT  *FROM links WHERE token = '$value'"; //fetch record against the provided token query

$result = $conn->query($sql);

if ($result->rowCount() > 0) { //checks the retrieve record
	$record = $result->fetch(PDO::FETCH_ASSOC); //retrieve the actual row
	$click_count = $record['click_count'] + 1; //click_count column
	$destination_link = $record['destination']; //destination link column
	$sql = "UPDATE links SET click_count = '$click_count' WHERE token = '$value'";
	if ($conn->query($sql) === TRUE) {
		header('Location: ' .$destination_link, true, 303);
		die();
		
	} else {
		header('Location: https://gradely.ng/');
		exit();
	}

}
?>