<h1><?= hsc($topic['title']) ?></h1>
<p class="topic-meta">Von <strong><?= hsc($topic['author']) ?></strong> am <?= hsc($topic['created_at']) ?></p>

<?php if (isLoggedIn()): ?>
    <div class="actions">
        <?php if ($topic['user_id'] === currentUser()['id'] || hasRole('moderator')): ?>
            <a href="index.php?action=topics.edit&id=<?= $topic['id'] ?>">Thema bearbeiten</a>
        <?php endif; ?>
        <?php if ($topic['user_id'] === currentUser()['id'] || hasRole('admin')): ?>
            <form method="POST" action="index.php?action=topics.delete" class="inline-form">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="id" value="<?= $topic['id'] ?>">
                <button type="submit" class="delete" onclick="return confirm('Thema und alle Beiträge wirklich löschen?')">Thema löschen</button>
            </form>
        <?php endif; ?>
    </div>
<?php endif; ?>

<hr>

<?php if (count($posts) === 0): ?>
    <p>Noch keine Beiträge vorhanden.</p>
<?php else: ?>
    <?php foreach ($posts as $post): ?>
        <article class="post">
            <header class="post-header">
                <strong><?= hsc($post['author']) ?></strong>
                <time><?= hsc($post['created_at']) ?></time>
                <?php if ($post['updated_at'] !== $post['created_at']): ?><em>(bearbeitet: <?= hsc($post['updated_at']) ?>)</em><?php endif; ?>
            </header>
            <div class="post-content"><?= nl2br(hsc($post['content'])) ?></div>
            <?php if (isLoggedIn()): ?>
                <div class="actions">
                    <?php if ($post['user_id'] === currentUser()['id'] || hasRole('moderator')): ?>
                        <a href="index.php?action=posts.edit&id=<?= $post['id'] ?>">Bearbeiten</a>
                    <?php endif; ?>
                    <?php if ($post['user_id'] === currentUser()['id'] || hasRole('moderator')): ?>
                        <form method="POST" action="index.php?action=posts.delete" class="inline-form">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <input type="hidden" name="id" value="<?= $post['id'] ?>">
                            <button type="submit" class="delete" onclick="return confirm('Beitrag wirklich löschen?')">Löschen</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
    <?php $baseUrl = 'index.php?action=topics.show&id=' . $topic['id']; require __DIR__ . '/../layout/pagination.php'; ?>
<?php endif; ?>

<hr>

<?php if (isLoggedIn()): ?>
    <h2>Antwort schreiben</h2>
    <form method="POST" action="index.php?action=posts.create">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="topic_id" value="<?= $topic['id'] ?>">
        <label for="reply-content">Dein Beitrag</label>
        <textarea id="reply-content" name="content" rows="5" required minlength="<?= MIN_CONTENT ?>"></textarea>
        <button type="submit">Absenden</button>
    </form>
<?php else: ?>
    <p><a href="index.php?action=login">Einloggen</a>, um zu antworten.</p>
<?php endif; ?>
