<div class="toolbar">
    <div class="search-bar">
        <span class="icon">&#128269;</span>
        <input type="text" id="searchInput" placeholder="Search announcements by title or content...">
    </div>
    <div class="filter-pills">
        <button type="button" class="pill active" data-category="All">All</button>
        <?php foreach (get_category_names($pdo) as $cat): ?>
            <button type="button" class="pill" data-category="<?= e($cat) ?>"><?= e($cat) ?></button>
        <?php endforeach; ?>
    </div>
</div>
