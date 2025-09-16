<?php
require_once __DIR__ . '/../db.php';

class AdmitCard {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function getAll($limit = 10, $offset = 0): array {
        $sql = "SELECT * FROM admit_cards ORDER BY published_at DESC LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$limit, $offset]);
    }

    // Admin listings: include drafts/published filter
    public function getAllAdmin($limit = 10, $offset = 0, $status = 'all'): array {
        $sql = "SELECT * FROM admit_cards";
        $params = [];
        if ($status && $status !== 'all') {
            $sql .= " WHERE status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY COALESCE(published_at, updated_at, created_at) DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        return $this->db->fetchAll($sql, $params);
    }

    public function getBySlug($slug) {
        $sql = "SELECT * FROM admit_cards WHERE slug = ?";
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
            $conditions[] = "(title LIKE ? OR instructions LIKE ? OR download_url LIKE ?)";
            $like = "%$w%";
            array_push($params, $like, $like, $like);
        }
        $where = $conditions ? ("WHERE " . implode(' AND ', $conditions)) : '';
        $sql = "SELECT * FROM admit_cards $where ORDER BY published_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        return $this->db->fetchAll($sql, $params);
    }

    public function countSearch($query): int {
        $words = array_filter(preg_split('/\s+/', trim($query)));
        $conditions = [];
        $params = [];
        foreach ($words as $w) {
            $conditions[] = "(title LIKE ? OR instructions LIKE ? OR download_url LIKE ?)";
            $like = "%$w%";
            array_push($params, $like, $like, $like);
        }
        $where = $conditions ? ("WHERE " . implode(' AND ', $conditions)) : '';
        $sql = "SELECT COUNT(*) as count FROM admit_cards $where";
        $result = $this->db->fetchOne($sql, $params);
        return (int)($result['count'] ?? 0);
    }

    public function getCount() {
        $result = $this->db->fetchOne("SELECT COUNT(*) as count FROM admit_cards");
        return $result['count'];
    }

    public function getCountAdmin($status = 'all'): int {
        $sql = "SELECT COUNT(*) as count FROM admit_cards";
        $params = [];
        if ($status && $status !== 'all') {
            $sql .= " WHERE status = ?";
            $params[] = $status;
        }
        $row = $this->db->fetchOne($sql, $params);
        return (int)($row['count'] ?? 0);
    }

    public function create($data) {
        $sql = "INSERT INTO admit_cards (title, slug, organization, description, exam_date, download_url, instructions, required_documents, thumbnail_url, author_id, status, published_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $this->db->query($sql, [
            $data['title'],
            $data['slug'],
            $data['organization'],
            $data['description'],
            $data['exam_date'],
            $data['download_url'],
            $data['instructions'],
            $data['required_documents'],
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
        $sql = "UPDATE admit_cards SET " . implode(', ', $fields) . " WHERE id = ?";
        
        return $this->db->query($sql, $values);
    }
    
    public function getById($id) {
        $sql = "SELECT * FROM admit_cards WHERE id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }

    public function delete($id) {
        $sql = "DELETE FROM admit_cards WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }

    // ===== Flexible Content Helpers (polymorphic via entity_type 'admit') =====
    private function entityType(): string {
        return 'admit';
    }

    // Events (supports multiple exam dates etc.)
    public function getEvents(int $admitId, ?string $eventType = null): array {
        $sql = "SELECT * FROM content_events WHERE entity_type = ? AND entity_id = ?";
        $params = [$this->entityType(), $admitId];
        if ($eventType) {
            $sql .= " AND event_type = ?";
            $params[] = $eventType;
        }
        $sql .= " ORDER BY sort_order ASC, start_date ASC, id ASC";
        return $this->db->fetchAll($sql, $params);
    }

    public function saveEvents(int $admitId, array $rows): bool {
        $conn = $this->db->getConnection();
        if ($conn === null) return false;
        $conn->beginTransaction();
        try {
            error_log("AdmitCard.saveEvents start: admitId={$admitId}, rows=" . count($rows));
            $conn->prepare("DELETE FROM content_events WHERE entity_type = ? AND entity_id = ?")
                 ->execute([$this->entityType(), $admitId]);
            if (!empty($rows)) {
                $ins = $conn->prepare("INSERT INTO content_events (entity_type, entity_id, event_type, event_label, start_date, end_date, notes, sort_order) VALUES (?,?,?,?,?,?,?,?)");
                $inserted = 0;
                foreach ($rows as $r) {
                    $ins->execute([
                        $this->entityType(),
                        $admitId,
                        $r['event_type'] ?? 'exam',
                        $r['event_label'] ?? null,
                        $r['start_date'] ?? null,
                        $r['end_date'] ?? null,
                        $r['notes'] ?? null,
                        isset($r['sort_order']) ? (int)$r['sort_order'] : 0,
                    ]);
                    $inserted++;
                }
                error_log("AdmitCard.saveEvents inserted={$inserted}");
            }
            $conn->commit();
            error_log("AdmitCard.saveEvents done: admitId={$admitId}");
            return true;
        } catch (Exception $e) {
            $conn->rollBack();
            error_log('saveEvents(admit) failed: ' . $e->getMessage());
            return false;
        }
    }

    // Important Links
    public function getLinks(int $admitId): array {
        $sql = "SELECT * FROM content_links WHERE entity_type = ? AND entity_id = ? ORDER BY sort_order ASC, id ASC";
        return $this->db->fetchAll($sql, [$this->entityType(), $admitId]);
    }

    public function saveLinks(int $admitId, array $rows): bool {
        $conn = $this->db->getConnection();
        if ($conn === null) return false;
        $conn->beginTransaction();
        try {
            error_log("AdmitCard.saveLinks start: admitId={$admitId}, rows=" . count($rows));
            $conn->prepare("DELETE FROM content_links WHERE entity_type = ? AND entity_id = ?")
                 ->execute([$this->entityType(), $admitId]);
            if (!empty($rows)) {
                $ins = $conn->prepare("INSERT INTO content_links (entity_type, entity_id, label, url, sort_order) VALUES (?,?,?,?,?)");
                $inserted = 0;
                foreach ($rows as $r) {
                    $ins->execute([
                        $this->entityType(),
                        $admitId,
                        $r['label'] ?? '',
                        $r['url'] ?? '',
                        isset($r['sort_order']) ? (int)$r['sort_order'] : 0,
                    ]);
                    $inserted++;
                }
                error_log("AdmitCard.saveLinks inserted={$inserted}");
            }
            $conn->commit();
            error_log("AdmitCard.saveLinks done: admitId={$admitId}");
            return true;
        } catch (Exception $e) {
            $conn->rollBack();
            error_log('saveLinks(admit) failed: ' . $e->getMessage());
            return false;
        }
    }

    // FAQs
    public function getFaqs(int $admitId): array {
        $sql = "SELECT * FROM content_faqs WHERE entity_type = ? AND entity_id = ? AND is_active = 1 ORDER BY sort_order ASC, id ASC";
        return $this->db->fetchAll($sql, [$this->entityType(), $admitId]);
    }

    public function saveFaqs(int $admitId, array $rows): bool {
        $conn = $this->db->getConnection();
        if ($conn === null) return false;
        $conn->beginTransaction();
        try {
            error_log("AdmitCard.saveFaqs start: admitId={$admitId}, rows=" . count($rows));
            $conn->prepare("DELETE FROM content_faqs WHERE entity_type = ? AND entity_id = ?")
                 ->execute([$this->entityType(), $admitId]);
            if (!empty($rows)) {
                $ins = $conn->prepare("INSERT INTO content_faqs (entity_type, entity_id, question, answer, sort_order, is_active) VALUES (?,?,?,?,?,?)");
                $inserted = 0;
                foreach ($rows as $r) {
                    $ins->execute([
                        $this->entityType(),
                        $admitId,
                        $r['question'] ?? '',
                        $r['answer'] ?? '',
                        isset($r['sort_order']) ? (int)$r['sort_order'] : 0,
                        isset($r['is_active']) ? (int)$r['is_active'] : 1,
                    ]);
                    $inserted++;
                }
                error_log("AdmitCard.saveFaqs inserted={$inserted}");
            }
            $conn->commit();
            error_log("AdmitCard.saveFaqs done: admitId={$admitId}");
            return true;
        } catch (Exception $e) {
            $conn->rollBack();
            error_log('saveFaqs(admit) failed: ' . $e->getMessage());
            return false;
        }
    }

    // Sections (e.g., instructions, exam day guidelines, etc.)
    public function getSections(int $admitId, ?string $sectionType = null): array {
        $sql = "SELECT * FROM content_sections WHERE entity_type = ? AND entity_id = ?";
        $params = [$this->entityType(), $admitId];
        if ($sectionType) {
            $sql .= " AND section_type = ?";
            $params[] = $sectionType;
        }
        $sql .= " ORDER BY sort_order ASC, id ASC";
        return $this->db->fetchAll($sql, $params);
    }

    public function saveSections(int $admitId, array $rows): bool {
        $conn = $this->db->getConnection();
        if ($conn === null) return false;
        $conn->beginTransaction();
        try {
            error_log("AdmitCard.saveSections start: admitId={$admitId}, rows=" . count($rows));
            $conn->prepare("DELETE FROM content_sections WHERE entity_type = ? AND entity_id = ?")
                 ->execute([$this->entityType(), $admitId]);
            if (!empty($rows)) {
                $ins = $conn->prepare("INSERT INTO content_sections (entity_type, entity_id, section_type, title, content, sort_order) VALUES (?,?,?,?,?,?)");
                $inserted = 0;
                foreach ($rows as $r) {
                    $ins->execute([
                        $this->entityType(),
                        $admitId,
                        $r['section_type'] ?? 'other',
                        $r['title'] ?? null,
                        $r['content'] ?? '',
                        isset($r['sort_order']) ? (int)$r['sort_order'] : 0,
                    ]);
                    $inserted++;
                }
                error_log("AdmitCard.saveSections inserted={$inserted}");
            }
            $conn->commit();
            error_log("AdmitCard.saveSections done: admitId={$admitId}");
            return true;
        } catch (Exception $e) {
            $conn->rollBack();
            error_log('saveSections(admit) failed: ' . $e->getMessage());
            return false;
        }
    }
}

