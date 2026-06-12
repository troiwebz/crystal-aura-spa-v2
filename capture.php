<?php
// Booking enquiry capture — called by the homepage form before WhatsApp/LINE/email handoff
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo '{"ok":false}'; exit; }

$raw = file_get_contents('php://input');
$d = json_decode($raw, true);
if (!$d) { $d = $_POST; }

$name    = trim(substr($d['name'] ?? '', 0, 120));
$phone   = trim(substr($d['phone'] ?? '', 0, 60));
$email   = trim(substr($d['email'] ?? '', 0, 160));
$service = trim(substr($d['service'] ?? '', 0, 160));
$duration= trim(substr($d['duration'] ?? '', 0, 60));
$date    = trim(substr($d['date'] ?? '', 0, 30));
$time    = trim(substr($d['time'] ?? '', 0, 30));
$people  = trim(substr($d['people'] ?? '', 0, 30));
$notes   = trim(substr($d['notes'] ?? '', 0, 1000));
$channel = trim(substr($d['channel'] ?? 'unknown', 0, 30));

// require at least a name or phone to be worth saving
if ($name === '' && $phone === '') { echo '{"ok":false,"reason":"empty"}'; exit; }

$FILE = __DIR__ . '/data/bookings.json';
$bookings = file_exists($FILE) ? (json_decode(file_get_contents($FILE), true) ?: []) : [];
array_unshift($bookings, [
  'id' => uniqid('b'),
  'ts' => date('Y-m-d H:i'),
  'name' => $name, 'phone' => $phone, 'email' => $email,
  'service' => $service, 'duration' => $duration,
  'date' => $date, 'time' => $time, 'people' => $people,
  'notes' => $notes, 'channel' => $channel,
  'status' => 'new',
]);
$bookings = array_slice($bookings, 0, 500); // cap
file_put_contents($FILE, json_encode($bookings, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), LOCK_EX);
echo '{"ok":true}';
