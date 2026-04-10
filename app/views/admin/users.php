<h1>Nutzerverwaltung</h1>

<p><?= count($users) ?> registrierte Nutzer</p>

<div class="table-scroll">
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Benutzername</th>
            <th>E-Mail</th>
            <th>Rolle</th>
            <th>Registriert</th>
            <th>Aktionen</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= $user['id'] ?></td>
                <td><?= hsc($user['username']) ?></td>
                <td><?= hsc($user['email']) ?></td>

                <td>
                    <?php if ($user['id'] === currentUser()['id']): ?>
                        <!-- Eigene Rolle nur als Text, kein Dropdown (Selbstschutz) -->
                        <strong><?= hsc($user['role']) ?></strong>
                        <em>(eigenes Konto)</em>
                    <?php else: ?>
                        <form method="POST" action="index.php?action=admin.updateRole"
                              class="inline-form">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <select name="role" onchange="this.form.submit()">
                                <?php foreach (['user', 'moderator', 'admin'] as $role): ?>
                                    <option value="<?= $role ?>"
                                        <?= $user['role'] === $role ? 'selected' : '' ?>>
                                        <?= hsc($role) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    <?php endif; ?>
                </td>

                <td><?= hsc($user['created_at']) ?></td>

                <td>
                    <?php if ($user['id'] !== currentUser()['id']): ?>
                        <form method="POST" action="index.php?action=admin.deleteUser"
                              class="inline-form delete-form">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <button type="submit" class="delete"
                                    data-username="<?= hsc($user['username']) ?>">Löschen</button>
                        </form>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<p><a href="index.php?action=topics.index">&larr; Zurück zur Forumsübersicht</a></p>

<script>
document.querySelectorAll('.delete-form').forEach(function (form) {
    form.addEventListener('submit', function (e) {
        var name = e.submitter.dataset.username;
        if (!confirm('Nutzer „' + name + '" und ALLE zugehörigen Themen/Beiträge wirklich löschen?')) {
            e.preventDefault();
        }
    });
});
</script>
