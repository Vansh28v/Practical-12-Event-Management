<?php
require __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
verify_csrf();
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) { set_flash('error', 'Invalid event ID.'); header('Location: index.php'); exit; }

$get = $conn->prepare('SELECT title, poster_path FROM events WHERE id = ?');
$get->bind_param('i', $id);
$get->execute();
$event = $get->get_result()->fetch_assoc();
if (!$event) { set_flash('error', 'Event not found.'); header('Location: index.php'); exit; }

try {
    $conn->begin_transaction();
    $audit = $conn->prepare('INSERT INTO event_audit_log (event_id, actor, action, details) VALUES (?, ?, ?, ?)');
    $actor = 'local-admin'; $action = 'DELETE'; $details = 'Event deleted: ' . mb_substr($event['title'], 0, 180);
    $audit->bind_param('isss', $id, $actor, $action, $details);
    $audit->execute();

    $del = $conn->prepare('DELETE FROM events WHERE id = ?');
    $del->bind_param('i', $id);
    $del->execute();
    $conn->commit();
    remove_poster($event['poster_path']);
    set_flash('success', 'Event and its poster deleted successfully.');
} catch (Throwable $ex) {
    $conn->rollback();
    set_flash('error', 'Unable to delete event. Please try again.');
}
header('Location: index.php');
exit;
