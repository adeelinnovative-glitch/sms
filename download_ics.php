<?php
session_start();
include_once("db.php");

if(!isset($_GET['id'])) die("Appointment ID required.");

$id = intval($_GET['id']);
$res = mysqli_query($con, "SELECT a.*, s.name as stylist_name FROM appointments a JOIN staff s ON a.staff_id = s.staff_id WHERE a.appointment_id = $id");
$data = mysqli_fetch_assoc($res);

if(!$data) die("Appointment not found.");

$dtStart = date('Ymd\THis', strtotime($data['date'] . ' ' . $data['time']));
$dtEnd = date('Ymd\THis', strtotime($data['date'] . ' ' . $data['time'] . ' +1 hour'));
$summary = "Salon Appointment: " . $data['service'];
$description = "Stylist: " . $data['stylist_name'];

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="salon_appointment.ics"');

echo "BEGIN:VCALENDAR\r\n";
echo "VERSION:2.0\r\n";
echo "PRODID:-//Elegance Salon//NONSGML v1.0//EN\r\n";
echo "BEGIN:VEVENT\r\n";
echo "UID:" . uniqid() . "\r\n";
echo "DTSTAMP:" . date('Ymd\THis') . "Z\r\n";
echo "DTSTART:" . $dtStart . "\r\n";
echo "DTEND:" . $dtEnd . "\r\n";
echo "SUMMARY:" . $summary . "\r\n";
echo "DESCRIPTION:" . $description . "\r\n";
echo "END:VEVENT\r\n";
echo "END:VCALENDAR\r\n";
?>
