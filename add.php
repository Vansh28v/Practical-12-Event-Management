<?php
require __DIR__ . '/db.php';

$values = ['title' => '', 'description' => '', 'category' => '', 'event_date' => '', 'venue' => '', 'status' => 'open'];
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

    if (!$errors) {
        $posterPath = null;
        try {
            $posterPath = store_poster($upload);
            $stmt = $conn->prepare('INSERT INTO events (title, description, category, event_date, venue, status, poster_path) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('sssssss', $values['title'], $values['description'], $values['category'], $values['event_date'], $values['venue'], $values['status'], $posterPath);
            $stmt->execute();
            $eventId = (int)$conn->insert_id;
            audit_event($conn, $eventId, 'CREATE', 'Event created: ' . mb_substr($values['title'], 0, 180));
            set_flash('success', 'Event created successfully.');
            header('Location: index.php'); exit;
        } catch (Throwable $ex) {
            if ($posterPath) remove_poster($posterPath);
            $errors[] = 'Unable to create event. Check the database and upload-folder permissions, then try again.';
        }
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Add Event</title><link rel="stylesheet" href="css/style.css"></head><body>
<header class="topbar"><div class="container nav"><a class="brand" href="index.php"><span class="brand-icon">E</span> EventDesk</a><a href="index.php" class="top-link">← Back to events</a></div></header>
<main class="container narrow"><section class="form-heading"><p class="eyebrow">NEW LISTING</p><h1>Create an event</h1><p class="muted">Enter the event details and optionally upload a poster.</p></section>
<?php if ($errors): ?><div class="alert error"><strong>Please correct the following:</strong><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form class="panel form-panel" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="MAX_FILE_SIZE" value="2097152">
<div class="form-grid">
<label class="full"><span>Event title *</span><input name="title" maxlength="150" required value="<?= e($values['title']) ?>" placeholder="e.g. Campus Tech Summit"></label>
<label class="full"><span>Description *</span><textarea name="description" rows="4" required placeholder="What is this event about?"><?= e($values['description']) ?></textarea></label>
<label><span>Category *</span><input name="category" maxlength="80" required value="<?= e($values['category']) ?>" placeholder="Technology, Workshop..."></label>
<label><span>Event date *</span><input type="date" name="event_date" required value="<?= e($values['event_date']) ?>"></label>
<label class="full"><span>Venue *</span><input name="venue" maxlength="180" required value="<?= e($values['venue']) ?>" placeholder="Auditorium / lab / room"></label>
<label><span>Event status *</span><select name="status"><option value="open" <?= $values['status']==='open'?'selected':'' ?>>Open</option><option value="closed" <?= $values['status']==='closed'?'selected':'' ?>>Closed</option></select></label>
<label><span>Poster image (optional)</span><input type="file" name="poster" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"><small>JPG, PNG or WEBP · max 2 MB</small></label>
</div><div class="form-actions"><a class="button light" href="index.php">Cancel</a><button class="button primary" type="submit">Create event</button></div></form>
</main><footer class="footer">EventDesk · Practical 12</footer></body></html>
