<?php
require __DIR__ . '/db.php';

$q = trim((string)($_GET['q'] ?? ''));
$category = trim((string)($_GET['category'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = ($page && $page > 0) ? $page : 1;
$perPage = 6;

$where = ['1=1'];
$params = [];
$types = '';
if ($q !== '') {
    $where[] = '(title LIKE ? OR description LIKE ? OR venue LIKE ?)';
    $term = '%' . $q . '%';
    array_push($params, $term, $term, $term);
    $types .= 'sss';
}
if ($category !== '') {
    $where[] = 'category = ?';
    $params[] = $category;
    $types .= 's';
}
if (in_array($status, ['open', 'closed'], true)) {
    $where[] = 'status = ?';
    $params[] = $status;
    $types .= 's';
}
$whereSql = implode(' AND ', $where);

$count = $conn->prepare("SELECT COUNT(*) AS total FROM events WHERE $whereSql");
if ($types !== '') $count->bind_param($types, ...$params);
$count->execute();
$total = (int)$count->get_result()->fetch_assoc()['total'];
$count->close();

$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$list = $conn->prepare("SELECT id, title, description, category, event_date, venue, status, poster_path FROM events WHERE $whereSql ORDER BY event_date ASC, id DESC LIMIT ? OFFSET ?");
$listParams = $params;
$listTypes = $types . 'ii';
$listParams[] = $perPage;
$listParams[] = $offset;
$list->bind_param($listTypes, ...$listParams);
$list->execute();
$events = $list->get_result();

$categories = [];
$catResult = $conn->query('SELECT DISTINCT category FROM events ORDER BY category');
while ($row = $catResult->fetch_assoc()) $categories[] = $row['category'];
$flash = get_flash();

function event_page_url(int $page, string $q, string $category, string $status): string {
    return '?' . http_build_query(['q' => $q, 'category' => $category, 'status' => $status, 'page' => $page]);
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Event Management</title><link rel="stylesheet" href="css/style.css"></head><body>
<header class="topbar"><div class="container nav"><a class="brand" href="index.php"><span class="brand-icon">E</span> EventDesk</a><span class="nav-note">Practical 12 · Admin module</span><a class="button primary" href="add.php">＋ Add event</a></div></header>
<main class="container">
<section class="hero"><div><p class="eyebrow">EVENT CONTROL CENTER</p><h1>Manage your events</h1><p class="muted">Create event listings, upload posters and manage event status.</p></div><div class="hero-chip"><span class="dot"></span> Database connected</div></section>
<?php if ($flash): ?><div class="alert <?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div><?php endif; ?>
<section class="stats">
<div class="stat-card"><span>Total events</span><strong><?= $total ?></strong><small>Matching current filters</small></div>
<div class="stat-card"><span>Current page</span><strong><?= $page ?> / <?= $totalPages ?></strong><small><?= $perPage ?> events per page</small></div>
<div class="stat-card"><span>Categories</span><strong><?= count($categories) ?></strong><small>Across event records</small></div>
</section>
<section class="panel">
<form class="filters" method="get">
<label class="search"><span>Search events</span><input type="search" name="q" value="<?= e($q) ?>" placeholder="Title, description or venue"></label>
<label><span>Category</span><select name="category"><option value="">All categories</option><?php foreach ($categories as $c): ?><option value="<?= e($c) ?>" <?= $category === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></label>
<label><span>Status</span><select name="status"><option value="">All statuses</option><option value="open" <?= $status === 'open' ? 'selected' : '' ?>>Open</option><option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>Closed</option></select></label>
<button class="button primary" type="submit">Apply filters</button><a class="button light" href="index.php">Reset</a>
</form>
<div class="event-grid">
<?php if ($events->num_rows === 0): ?><div class="empty">No events match the selected filters.</div>
<?php else: while ($event = $events->fetch_assoc()): ?>
<article class="event-card">
<div class="poster">
<?php if ($event['poster_path'] && is_file(__DIR__ . '/' . $event['poster_path'])): ?><img src="<?= e($event['poster_path']) ?>" alt="Poster for <?= e($event['title']) ?>">
<?php else: ?><div class="poster-placeholder"><span class="poster-symbol">✦</span><span><?= e($event['category']) ?></span></div><?php endif; ?>
<span class="status <?= e($event['status']) ?>"><?= e(ucfirst($event['status'])) ?></span>
</div>
<div class="event-content"><div class="event-meta"><span><?= e(date('d M Y', strtotime($event['event_date']))) ?></span><span>·</span><span><?= e($event['category']) ?></span></div>
<h2><?= e($event['title']) ?></h2><p class="description"><?= e($event['description']) ?></p><p class="venue">⌖ <?= e($event['venue']) ?></p>
<div class="card-actions"><a class="button small light" href="edit.php?id=<?= (int)$event['id'] ?>">Edit event</a>
<form method="post" action="delete.php" onsubmit="return confirm('Delete this event and its poster permanently? This cannot be undone.');"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$event['id'] ?>"><button class="button small danger" type="submit">Delete</button></form></div></div>
</article>
<?php endwhile; endif; ?>
</div>
<div class="table-footer"><span>Showing <?= $total ? $offset + 1 : 0 ?>–<?= min($offset + $perPage, $total) ?> of <?= $total ?> events</span><nav class="pagination"><?php if ($page > 1): ?><a href="<?= e(event_page_url($page - 1, $q, $category, $status)) ?>">← Previous</a><?php else: ?><span class="disabled">← Previous</span><?php endif; ?><span class="current"><?= $page ?></span><?php if ($page < $totalPages): ?><a href="<?= e(event_page_url($page + 1, $q, $category, $status)) ?>">Next →</a><?php else: ?><span class="disabled">Next →</span><?php endif; ?></nav></div>
</section>
<p class="note">Posters: JPG, PNG or WEBP · Maximum file size: 2 MB. Event changes are recorded in the audit log.</p>
</main><footer class="footer">EventDesk · PHP, MySQLi and XAMPP</footer></body></html>
