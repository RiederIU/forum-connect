<?php

/**
 * Modell für Forenbeiträge.
 * Kapselt alle Datenbankoperationen auf der posts-Tabelle.
 */

class Post
{
    public static function getByTopic(int $topicId, int $page, int $perPage): array
    {
        $db = getDB();

        $stmtCount = $db->prepare(
            'SELECT COUNT(*) FROM posts WHERE topic_id = :topic_id'
        );
        $stmtCount->execute([':topic_id' => $topicId]);
        $total = (int) $stmtCount->fetchColumn();

        $offset = ($page - 1) * $perPage;

        $stmtData = $db->prepare(
            'SELECT p.*, u.username AS author
             FROM posts p
             JOIN users u ON u.id = p.user_id
             WHERE p.topic_id = :topic_id
             ORDER BY p.created_at ASC
             LIMIT :limit OFFSET :offset'
        );
        $stmtData->bindValue(':topic_id', $topicId, PDO::PARAM_INT);
        $stmtData->bindValue(':limit',    $perPage, PDO::PARAM_INT);
        $stmtData->bindValue(':offset',   $offset,  PDO::PARAM_INT);
        $stmtData->execute();

        return [
            'posts' => $stmtData->fetchAll(),
            'total' => $total
        ];
    }

    public static function getById(int $id): ?array
    {
        $db = getDB();
        $stmt = $db->prepare('SELECT * FROM posts WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $post = $stmt->fetch();
        return $post ?: null;
    }

    /** Legt einen neuen Beitrag an und aktualisiert updated_at des Themas. */
    public static function create(string $content, int $userId, int $topicId): int
    {
        $db = getDB();

        $stmt = $db->prepare(
            'INSERT INTO posts (content, user_id, topic_id)
             VALUES (:content, :user_id, :topic_id)'
        );
        $stmt->execute([
            ':content'  => $content,
            ':user_id'  => $userId,
            ':topic_id' => $topicId
        ]);
        $postId = (int) $db->lastInsertId();

        $db->prepare(
            'UPDATE topics SET updated_at = CURRENT_TIMESTAMP WHERE id = :id'
        )->execute([':id' => $topicId]);

        return $postId;
    }

    public static function update(int $id, string $content): void
    {
        $db = getDB();
        $stmt = $db->prepare(
            'UPDATE posts
             SET content = :content, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute([':content' => $content, ':id' => $id]);
    }

    public static function delete(int $id): void
    {
        $db = getDB();
        $stmt = $db->prepare('DELETE FROM posts WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
