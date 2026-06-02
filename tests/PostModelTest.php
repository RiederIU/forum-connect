<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * DB-Komponententests für die statischen Methoden von Post.
 * Greifen über getDB() auf die In-Memory-SQLite-DB aus bootstrap.php zu.
 */
class PostModelTest extends TestCase
{
    private int $userId;
    private int $topicId;

    protected function setUp(): void
    {
        // Reihenfolge wegen der Fremdschlüssel: erst Beiträge, dann Themen, dann Nutzer.
        $db = getDB();
        $db->exec('DELETE FROM posts');
        $db->exec('DELETE FROM topics');
        $db->exec('DELETE FROM users');
        $db->exec("DELETE FROM sqlite_sequence WHERE name IN ('posts', 'topics', 'users')");

        $hash = password_hash('geheim123', PASSWORD_DEFAULT);
        $this->userId  = User::create('autor', 'autor@example.com', $hash);
        $this->topicId = Topic::create('Trägerthema', $this->userId);
    }

    public function testCreateReturnsNewIntegerId(): void
    {
        $id = Post::create('ein Beitrag', $this->userId, $this->topicId);

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
    }

    public function testCreateTouchesTraegerthemaUpdatedAt(): void
    {
        // Ein zweites Thema in die Vergangenheit setzen, damit der updated_at-Touch
        // durch Post::create das Trägerthema unabhängig von der Sekunden-Granularität
        // eindeutig nach vorne sortiert (getAll ordnet nach updated_at DESC).
        $db = getDB();
        $other = Topic::create('Älteres Thema', $this->userId);
        $stmt = $db->prepare('UPDATE topics SET updated_at = :ts WHERE id = :id');
        $stmt->execute([':ts' => '2000-01-01 00:00:00', ':id' => $other]);

        Post::create('Beitrag im Trägerthema', $this->userId, $this->topicId);

        $first = Topic::getAll(1, 10, null)['topics'][0];
        $this->assertSame('Trägerthema', $first['title']);
    }

    public function testGetByIdReturnsPost(): void
    {
        $id = Post::create('such mich', $this->userId, $this->topicId);

        $post = Post::getById($id);
        $this->assertNotNull($post);
        $this->assertSame('such mich', $post['content']);
    }

    public function testGetByIdReturnsNullForMissingPost(): void
    {
        $this->assertNull(Post::getById(999));
    }

    public function testGetByTopicReturnsTotalAndPaginates(): void
    {
        Post::create('eins', $this->userId, $this->topicId);
        Post::create('zwei', $this->userId, $this->topicId);
        Post::create('drei', $this->userId, $this->topicId);

        $page1 = Post::getByTopic($this->topicId, 1, 2);
        $this->assertSame(3, $page1['total']);
        $this->assertCount(2, $page1['posts']);

        $page2 = Post::getByTopic($this->topicId, 2, 2);
        $this->assertCount(1, $page2['posts']);
    }

    public function testGetByTopicReturnsPostsChronologically(): void
    {
        // created_at künstlich staffeln, damit die ASC-Reihenfolge deterministisch ist.
        $db = getDB();
        $p1 = Post::create('älter', $this->userId, $this->topicId);
        $p2 = Post::create('neuer', $this->userId, $this->topicId);
        $stmt = $db->prepare('UPDATE posts SET created_at = :ts WHERE id = :id');
        $stmt->execute([':ts' => '2001-01-01 00:00:00', ':id' => $p1]);
        $stmt->execute([':ts' => '2002-01-01 00:00:00', ':id' => $p2]);

        $posts = Post::getByTopic($this->topicId, 1, 10)['posts'];
        $this->assertSame('älter', $posts[0]['content']);
        $this->assertSame('neuer', $posts[1]['content']);
    }

    public function testGetByTopicReturnsAuthorName(): void
    {
        Post::create('mit Autor', $this->userId, $this->topicId);

        $row = Post::getByTopic($this->topicId, 1, 10)['posts'][0];
        $this->assertSame('autor', $row['author']);
    }

    public function testUpdateChangesContent(): void
    {
        $id = Post::create('alt', $this->userId, $this->topicId);

        Post::update($id, 'neu');
        $this->assertSame('neu', Post::getById($id)['content']);
    }

    public function testDeleteRemovesPost(): void
    {
        $id = Post::create('weg damit', $this->userId, $this->topicId);

        Post::delete($id);
        $this->assertNull(Post::getById($id));
    }
}
