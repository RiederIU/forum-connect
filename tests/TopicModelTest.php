<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * DB-Komponententests für die statischen Methoden von Topic.
 * Greifen über getDB() auf die In-Memory-SQLite-DB aus bootstrap.php zu.
 */
class TopicModelTest extends TestCase
{
    private int $userId;

    protected function setUp(): void
    {
        // Reihenfolge wegen der Fremdschlüssel: erst Beiträge, dann Themen, dann Nutzer.
        $db = getDB();
        $db->exec('DELETE FROM posts');
        $db->exec('DELETE FROM topics');
        $db->exec('DELETE FROM users');
        $db->exec("DELETE FROM sqlite_sequence WHERE name IN ('posts', 'topics', 'users')");

        // Jedes Thema braucht einen Autor, da topics.user_id NOT NULL ist.
        $hash = password_hash('geheim123', PASSWORD_DEFAULT);
        $this->userId = User::create('autor', 'autor@example.com', $hash);
    }

    public function testCreateReturnsNewIntegerId(): void
    {
        $id = Topic::create('Mein erstes Thema', $this->userId);

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
    }

    public function testGetByIdReturnsTopicWithAuthorName(): void
    {
        $id = Topic::create('Thema mit Autor', $this->userId);

        $topic = Topic::getById($id);
        $this->assertNotNull($topic);
        $this->assertSame('Thema mit Autor', $topic['title']);
        // getById verknüpft users und liefert den Nutzernamen als author.
        $this->assertSame('autor', $topic['author']);
    }

    public function testGetByIdReturnsNullForMissingTopic(): void
    {
        $this->assertNull(Topic::getById(999));
    }

    public function testCreateWithFirstPostCreatesTopicAndExactlyOnePost(): void
    {
        $topicId = Topic::createWithFirstPost('Frage', 'der erste Beitrag', $this->userId);
        $this->assertGreaterThan(0, $topicId);

        // Die Transaktion legt das Thema und genau einen Beitrag an.
        $posts = Post::getByTopic($topicId, 1, 10);
        $this->assertSame(1, $posts['total']);
        $this->assertSame('der erste Beitrag', $posts['posts'][0]['content']);
    }

    public function testGetAllReturnsTotalAndPaginates(): void
    {
        Topic::create('Apfel', $this->userId);
        Topic::create('Banane', $this->userId);
        Topic::create('Kirsche', $this->userId);

        $page1 = Topic::getAll(1, 2, null);
        // total spiegelt die Gesamtzahl, nicht die Seitengröße.
        $this->assertSame(3, $page1['total']);
        $this->assertCount(2, $page1['topics']);

        $page2 = Topic::getAll(2, 2, null);
        $this->assertSame(3, $page2['total']);
        $this->assertCount(1, $page2['topics']);
    }

    public function testGetAllSearchMatchesPostContentNotOnlyTitle(): void
    {
        Topic::create('Apfel', $this->userId);
        $banane = Topic::create('Banane', $this->userId);
        Post::create('hier steht spezialwort drin', $this->userId, $banane);

        // Die Suche trifft über den LEFT JOIN auch Beitragsinhalte, nicht nur Titel.
        $result = Topic::getAll(1, 10, 'spezialwort');
        $this->assertSame(1, $result['total']);
        $this->assertSame('Banane', $result['topics'][0]['title']);
    }

    public function testGetAllReportsPostCount(): void
    {
        $id = Topic::create('Mit Beiträgen', $this->userId);
        Post::create('erster', $this->userId, $id);
        Post::create('zweiter', $this->userId, $id);

        // post_count stammt aus der korrelierten Subquery.
        $row = Topic::getAll(1, 10, null)['topics'][0];
        $this->assertSame(2, (int) $row['post_count']);
    }

    public function testUpdateChangesTitle(): void
    {
        $id = Topic::create('Alter Titel', $this->userId);

        Topic::update($id, 'Neuer Titel');
        $this->assertSame('Neuer Titel', Topic::getById($id)['title']);
    }

    public function testDeleteRemovesTopicAndCascadesToPosts(): void
    {
        $id = Topic::create('Wird gelöscht', $this->userId);
        Post::create('hängt am Thema', $this->userId, $id);

        Topic::delete($id);
        $this->assertNull(Topic::getById($id));
        // ON DELETE CASCADE entfernt die zugehörigen Beiträge mit.
        $this->assertSame(0, Post::getByTopic($id, 1, 10)['total']);
    }
}
