</main>

<footer>
    &copy; <?= date('Y') ?> <?= hsc(APP_NAME) ?>
    <!-- Macht die Laufzeitumgebung in der HTTP-Antwort sichtbar, damit Staging und Production unterscheidbar sind. -->
    <span class="footer-env"><?= hsc(APP_ENV) ?></span>
</footer>

</body>
</html>
