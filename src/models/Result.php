<?php
require_once __DIR__ . '/../db.php';

class Result {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function getAll($limit = 10, $offset = 0): array {
        $sql = "SELECT * FROM results ORDER BY published_at DESC LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$limit, $offset]);
    }

    public function getBySlug($slug) {
        $sql = "SELECT * FROM results WHERE slug = ?";
        return $this->db->fetchOne($sql, [$slug]);
    }

    public function getLatest($limit = 5): array {
        return $this->getAll($limit, 0);
    }

    public function search($query, $limit = 10, $offset = 0): array {
        $words = array_filter(preg_split('/\s+/', trim($query)));
        $conditions = [];
        $params = [];
        foreach ($words as $w) {
            $conditions[] = "(title LIKE ? OR description LIKE ?)";
            $like = "%$w%";
            array_push($params, $like, $like);
        }
        $where = $conditions ? ("WHERE " . implode(' AND ', $conditions)) : '';
        $sql = "SELECT * FROM results $where ORDER BY published_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        return $this->db->fetchAll($sql, $params);
    }

    public function countSearch($query): int {
        $words = array_filter(preg_split('/\s+/', trim($query)));
        $conditions = [];
        $params = [];
        foreach ($words as $w) {
            $conditions[] = "(title LIKE ? OR description LIKE ?)";
            $like = "%$w%";
            array_push($params, $like, $like);
        }
        $where = $conditions ? ("WHERE " . implode(' AND ', $conditions)) : '';
        $sql = "SELECT COUNT(*) as count FROM results $where";
        $result = $this->db->fetchOne($sql, $params);
        return (int)($result['count'] ?? 0);
    }

    public function getCount() {
        $result = $this->db->fetchOne("SELECT COUNT(*) as count FROM results");
        return $result['count'];
    }

    public function create($data) {
        $sql = "INSERT INTO results (title, slug, organization, description, result_date, download_url, thumbnail_url, author_id, status, published_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $this->db->query($sql, [
            $data['title'],
            $data['slug'],
            $data['organization'],
            $data['description'],
            $data['result_date'],
            $data['download_url'],
            $data['thumbnail_url'] ?? null,
            isset($data['author_id']) && $data['author_id'] !== '' ? (int)$data['author_id'] : null,
            $data['status'],
            $data['published_at']
        ]);

        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
        
        $values[] = $id;
        $sql = "UPDATE results SET " . implode(', ', $fields) . " WHERE id = ?";
        
        return $this->db->query($sql, $values);
    }
    
    public function getById($id) {
        $sql = "SELECT * FROM results WHERE id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }

    public function delete($id) {
        $sql = "DELETE FROM results WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
    
    // ===== Flexible Content Helpers (polymorphic via entity_type 'result') =====
    private function entityType(): string {
        return 'result';
    }

    // Events (e.g., application/result dates)
    public function getEvents(int $resultId, ?string $eventType = null): array {
        $sql = "SELECT * FROM content_events WHERE entity_type = ? AND entity_id = ?";
        $params = [$this->entityType(), $resultId];
        if ($eventType) {
            $sql .= " AND event_type = ?";
            $params[] = $eventType;
        }
        $sql .= " ORDER BY sort_order ASC, start_date ASC, id ASC";
        return $this->db->fetchAll($sql, $params);
    }

    public function saveEvents(int $resultId, array $rows): bool {
        $conn = $this->db->getConnection();
        if ($conn === null) return false;
        $conn->beginTransaction();
        try {
            $conn->prepare("DELETE FROM content_events WHERE entity_type = ? AND entity_id = ?")
                 ->execute([$this->entityType(), $resultId]);
            if (!empty($rows)) {
                $ins = $conn->prepare("INSERT INTO content_events (entity_type, entity_id, event_type, event_label, start_date, end_date, notes, sort_order) VALUES (?,?,?,?,?,?,?,?)");
                foreach ($rows as $r) {
                    $ins->execute([
                        $this->entityType(),
                        $resultId,
                        $r['event_type'] ?? 'result',
                        $r['event_label'] ?? null,
                        $r['start_date'] ?? null,
                        $r['end_date'] ?? null,
                        $r['notes'] ?? null,
                        isset($r['sort_order']) ? (int)$r['sort_order'] : 0,
                    ]);
                }
            }
            $conn->commit();
            return true;
        } catch (Exception $e) {
            $conn->rollBack();
            error_log('saveEvents(result) failed: ' . $e->getMessage());
            return false;
        }
    }

    // Important Links
    public function getLinks(int $resultId): array {
        $sql = "SELECT * FROM content_links WHERE entity_type = ? AND entity_id = ? ORDER BY sort_order ASC, id ASC";
        return $this->db->fetchAll($sql, [$this->entityType(), $resultId]);
    }

    public function saveLinks(int $resultId, array $rows): bool {
        $conn = $this->db->getConnection();
        if ($conn === null) return false;
        $conn->beginTransaction();
        try {
            $conn->prepare("DELETE FROM content_links WHERE entity_type = ? AND entity_id = ?")
                 ->execute([$this->entityType(), $resultId]);
            if (!empty($rows)) {
                $ins = $conn->prepare("INSERT INTO content_links (entity_type, entity_id, label, url, sort_order) VALUES (?,?,?,?,?)");
                foreach ($rows as $r) {
                    $ins->execute([
                        $this->entityType(),
                        $resultId,
                        $r['label'] ?? '',
                        $r['url'] ?? '',
                        isset($r['sort_order']) ? (int)$r['sort_order'] : 0,
                    ]);
                }
            }
            $conn->commit();
            return true;
        } catch (Exception $e) {
            $conn->rollBack();
            error_log('saveLinks(result) failed: ' . $e->getMessage());
            return false;
        }
    }

    // FAQs
    public function getFaqs(int $resultId): array {
        $sql = "SELECT * FROM content_faqs WHERE entity_type = ? AND entity_id = ? AND is_active = 1 ORDER BY sort_order ASC, id ASC";
        return $this->db->fetchAll($sql, [$this->entityType(), $resultId]);
    }

    public function saveFaqs(int $resultId, array $rows): bool {
        $conn = $this->db->getConnection();
        if ($conn === null) return false;
        $conn->beginTransaction();
        try {
            $conn->prepare("DELETE FROM content_faqs WHERE entity_type = ? AND entity_id = ?")
                 ->execute([$this->entityType(), $resultId]);
            if (!empty($rows)) {
                $ins = $conn->prepare("INSERT INTO content_faqs (entity_type, entity_id, question, answer, sort_order, is_active) VALUES (?,?,?,?,?,?)");
                foreach ($rows as $r) {
                    $ins->execute([
                        $this->entityType(),
                        $resultId,
                        $r['question'] ?? '',
                        $r['answer'] ?? '',
                        isset($r['sort_order']) ? (int)$r['sort_order'] : 0,
                        isset($r['is_active']) ? (int)$r['is_active'] : 1,
                    ]);
                }
            }
            $conn->commit();
            return true;
        } catch (Exception $e) {
            $conn->rollBack();
            error_log('saveFaqs(result) failed: ' . $e->getMessage());
            return false;
        }
    }

    // Sections (e.g., how to check result, notes)
    public function getSections(int $resultId, ?string $sectionType = null): array {
        $sql = "SELECT * FROM content_sections WHERE entity_type = ? AND entity_id = ?";
        $params = [$this->entityType(), $resultId];
        if ($sectionType) {
            $sql .= " AND section_type = ?";
            $params[] = $sectionType;
        }
        $sql .= " ORDER BY sort_order ASC, id ASC";
        return $this->db->fetchAll($sql, $params);
    }

    public function saveSections(int $resultId, array $rows): bool {
        $conn = $this->db->getConnection();
        if ($conn === null) return false;
        $conn->beginTransaction();
        try {
            $conn->prepare("DELETE FROM content_sections WHERE entity_type = ? AND entity_id = ?")
                 ->execute([$this->entityType(), $resultId]);
            if (!empty($rows)) {
                $ins = $conn->prepare("INSERT INTO content_sections (entity_type, entity_id, section_type, title, content, sort_order) VALUES (?,?,?,?,?,?)");
                foreach ($rows as $r) {
                    $ins->execute([
                        $this->entityType(),
                        $resultId,
                        $r['section_type'] ?? 'other',
                        $r['title'] ?? null,
                        $r['content'] ?? '',
                        isset($r['sort_order']) ? (int)$r['sort_order'] : 0,
                    ]);
                }
            }
            $conn->commit();
            return true;
        } catch (Exception $e) {
            $conn->rollBack();
            error_log('saveSections(result) failed: ' . $e->getMessage());
            return false;
        }
    }
}
?>
