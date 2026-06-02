<h1>Beitrag bearbeiten</h1>

<form method="POST" action="index.php?action=posts.edit">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    <input type="hidden" name="id" value="<?= $post['id'] ?>">

    <label for="content">Inhalt</label>
    <textarea id="content" name="content" rows="8" required
              minlength="<?= MIN_CONTENT ?>"><?= hsc($post['content']) ?></textarea>

    <button type="submit">Speichern</button>
</form>

<p><a href="index.php?action=topics.show&id=<?= $post['topic_id'] ?>">&larr; Zurück zum Thema</a></p>
