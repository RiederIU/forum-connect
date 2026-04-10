<?php

/**
 * Modell für Forenthemen.
 * Kapselt alle Datenbankoperationen auf der topics-Tabelle.
 */

class Topic
{
    /**
     * Liefert Themen seitenweise. Optionale Suche über Titel und Beiträge.
     * COUNT(DISTINCT) verhindert Doppelzählung durch den LEFT JOIN.
     */
    public static function getAll(int $page, int $perPage, ?string $search = null): array
    {
        $db     = getDB();
        $where  = '';
        $params = [];

        if ($search !== null && $search !== '') {
            $where = 'WHERE t.title LIKE :search OR p_search.content LIKE :search';
            $params[':search'] = '%' . $search . '%';
        }

        $countSql = "SELECT COUNT(DISTINCT t.id)
                     FROM topics t
                     LEFT JOIN posts p_search ON p_search.topic_id = t.id
                     $where";
        $stmtCount = $db->prepare($countSql);
        $stmtCount->execute($params);
        $total = (int) $stmtCount->fetchColumn();

        $offset  = ($page - 1) * $perPage;

        // Subquery zählt Beiträge unabhängig vom Suchfilter
        $dataSql = "SELECT t.*, u.username AS author,
                        (SELECT COUNT(*) FROM posts WHERE topic_id = t.id) AS post_count
                    FROM topics t
                    JOIN users u ON u.id = t.user_id
                    LEFT JOIN posts p_search ON p_search.topic_id = t.id
                    $where
                    GROUP BY t.id
                    ORDER BY t.updated_at DESC
                    LIMIT :limit OFFSET :offset";

        $stmtData = $db->prepare($dataSql);
        foreach ($params as $key => $value) {
            $stmtData->bindValue($key, $value);
        }
        $stmtData->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmtData->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmtData->execute();

        return [
            'topics' => $stmtData->fetchAll(),
            'total'  => $total
        ];
    }

    public static function getById(int $id): ?array
    {
        $db = getDB();
        $stmt = $db->prepare(
            'SELECT t.*, u.username AS author
             FROM topics t
             JOIN users u ON u.id = t.user_id
             WHERE t.id = :id'
        );
        $stmt->execute([':id' => $id]);
        $topic = $stmt->fetch();
        return $topic ?: null;
    }

    public static function create(string $title, int $userId): int
    {
        $db = getDB();
        $stmt = $db->prepare(
            'INSERT INTO topics (title, user_id) VALUES (:title, :user_id)'
        );
        $stmt->execute([':title' => $title, ':user_id' => $userId]);
        return (int) $db->lastInsertId();
    }

    /** Legt Thema und ersten Beitrag in einer Transaktion an. */
    public static function createWithFirstPost(string $title, string $content, int $userId): int
    {
        $db = getDB();
        $db->beginTransaction();

        try {
            $topicId = self::create($title, $userId);
            Post::create($content, $userId, $topicId);
            $db->commit();
            return $topicId;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /** Aktualisiert den Titel und setzt updated_at auf jetzt. */
    public static function update(int $id, string $title): void
    {
        $db = getDB();
        $stmt = $db->prepare(
            'UPDATE topics
             SET title = :title, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute([':title' => $title, ':id' => $id]);
    }

    /** Löscht das Thema samt Beiträgen (CASCADE). */
    public static function delete(int $id): void
    {
        $db = getDB();
        $stmt = $db->prepare('DELETE FROM topics WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
