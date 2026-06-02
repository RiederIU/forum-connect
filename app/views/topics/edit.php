<h1>Thema bearbeiten</h1>

<form method="POST" action="index.php?action=topics.edit">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    <input type="hidden" name="id" value="<?= $topic['id'] ?>">

    <label for="title">Titel</label>
    <input type="text" id="title" name="title" required
           minlength="<?= MIN_TITLE ?>"
           value="<?= hsc($topic['title']) ?>">

    <button type="submit">Speichern</button>
</form>

<p><a href="index.php?action=topics.show&id=<?= $topic['id'] ?>">&larr; Zurück zum Thema</a></p>
