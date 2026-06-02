<h1>Neues Thema erstellen</h1>

<form method="POST" action="index.php?action=topics.create">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

    <label for="title">Titel</label>
    <input type="text" id="title" name="title" required
           minlength="<?= MIN_TITLE ?>"
           value="<?= hsc($_POST['title'] ?? '') ?>">

    <label for="content">Erster Beitrag</label>
    <textarea id="content" name="content" rows="6" required
              minlength="<?= MIN_CONTENT ?>"><?= hsc($_POST['content'] ?? '') ?></textarea>

    <button type="submit">Thema erstellen</button>
</form>

<p><a href="index.php?action=topics.index">&larr; Zurück zur Übersicht</a></p>
