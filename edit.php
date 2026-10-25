<?php
require __DIR__ . '/db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) { set_flash('error', 'Invalid event ID.'); header('Location: index.php'); exit; }
$get = $conn->prepare('SELECT * FROM events WHERE id = ?');
$get->bind_param('i', $id);
$get->execute();
$event = $get->get_result()->fetch_assoc();
if (!$event) { set_flash('error', 'Event not found.'); header('Location: index.php'); exit; }

$values = ['title'=>$event['title'], 'description'=>$event['description'], 'category'=>$event['category'], 'event_date'=>$event['event_date'], 'venue'=>$event['venue'], 'status'=>$event['status']];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach ($values as $key => $_) $values[$key] = trim((string)($_POST[$key] ?? ''));

    if ($values['title'] === '' || mb_strlen($values['title']) > 150) $errors[] = 'Enter an event title (maximum 150 characters).';
    if ($values['description'] === '') $errors[] = 'Enter an event description.';
    if ($values['category'] === '' || mb_strlen($values['category']) > 80) $errors[] = 'Enter a category (maximum 80 characters).';
    $dateObj = DateTime::createFromFormat('!Y-m-d', $values['event_date']);
    if (!$dateObj || $dateObj->format('Y-m-d') !== $values['event_date']) $errors[] = 'Select a valid event date.';
    if ($values['venue'] === '' || mb_strlen($values['venue']) > 180) $errors[] = 'Enter a venue (maximum 180 characters).';
    if (!in_array($values['status'], ['open', 'closed'], true)) $errors[] = 'Select a valid event status.';

    $upload = validate_poster($_FILES['poster'] ?? []);
    if (isset($upload['error'])) $errors[] = $upload['error'];

    $removeOld = isset($_POST['remove_poster']) && $_POST['remove_poster'] === '1';
    if (!$errors) {
        $newPoster = $event['poster_path'];
        $storedNew = null;
        try {
            if (!empty($upload['path'])) {
                $storedNew = store_poster($upload);
                $newPoster = $storedNew;
            } elseif ($removeOld) {
                $newPoster = null;
            }

            $update = $conn->prepare('UPDATE events SET title=?, description=?, category=?, event_date=?, venue=?, status=?, poster_path=? WHERE id=?');
            $update->bind_param('sssssssi', $values['title'], $values['description'], $values['category'], $values['event_date'], $values['venue'], $values['status'], $newPoster, $id);
            $update->execute();
            audit_event($conn, $id, 'UPDATE', 'Event updated: ' . mb_substr($values['title'], 0, 180));

            if (($storedNew || $removeOld) && $event['poster_path']) remove_poster($event['poster_path']);
            set_flash('success', 'Event updated successfully.');
            header('Location: index.php'); exit;
        } catch (Throwable $ex) {
            if ($storedNew) remove_poster($storedNew);
            $errors[] = 'Unable to update event. Check the database and upload-folder permissions, then try again.';
        }
    }
}
$currentPoster = $event['poster_path'];
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Edit Event</title><link rel="stylesheet" href="css/style.css"></head><body>
<header class="topbar"><div class="container nav"><a class="brand" href="index.php"><span class="brand-icon">E</span> EventDesk</a><a href="index.php" class="top-link">← Back to events</a></div></header>
<main class="container narrow"><section class="form-heading"><p class="eyebrow">EDIT LISTING · #<?= (int)$id ?></p><h1>Update event</h1><p class="muted">Change event information or replace/remove its poster.</p></section>
<?php if ($errors): ?><div class="alert error"><strong>Please correct the following:</strong><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form class="panel form-panel" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<div class="form-grid">
<label class="full"><span>Event title *</span><input name="title" maxlength="150" required value="<?= e($values['title']) ?>"></label>
<label class="full"><span>Description *</span><textarea name="description" rows="4" required><?= e($values['description']) ?></textarea></label>
<label><span>Category *</span><input name="category" maxlength="80" required value="<?= e($values['category']) ?>"></label>
<label><span>Event date *</span><input type="date" name="event_date" required value="<?= e($values['event_date']) ?>"></label>
<label class="full"><span>Venue *</span><input name="venue" maxlength="180" required value="<?= e($values['venue']) ?>"></label>
<label><span>Event status *</span><select name="status"><option value="open" <?= $values['status']==='open'?'selected':'' ?>>Open</option><option value="closed" <?= $values['status']==='closed'?'selected':'' ?>>Closed</option></select></label>
<label><span>Replace poster (optional)</span><input type="file" name="poster" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"><small>JPG, PNG or WEBP · max 2 MB</small></label>
<?php if ($currentPoster && is_file(__DIR__ . '/' . $currentPoster)): ?><div class="full current-poster"><img src="<?= e($currentPoster) ?>" alt="Current event poster"><label class="check-label"><input type="checkbox" name="remove_poster" value="1"> Remove current poster (unless replaced with a new one)</label></div><?php endif; ?>
</div><div class="form-actions"><a class="button light" href="index.php">Cancel</a><button class="button primary" type="submit">Save changes</button></div></form>
</main><footer class="footer">EventDesk · Practical 12</footer></body></html>
