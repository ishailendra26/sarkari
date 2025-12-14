<?php
require_once __DIR__ . '/../db.php';

class Job {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    // Admin listings: include drafts and allow filtering by status and category slug
    public function getAllAdmin($limit = 15, $offset = 0, $categorySlug = null, $status = 'all'): array {
        $sql = "SELECT j.*, c.name as category_name, c.slug as category_slug 
                FROM jobs j 
                LEFT JOIN categories c ON j.category_id = c.id ";
        $where = [];
        $params = [];
        if ($status && $status !== 'all') {
            $where[] = "j.status = ?";
            $params[] = $status;
        }
        if ($categorySlug) {
            $where[] = "c.slug = ?";
            $params[] = $categorySlug;
        }
        if ($where) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY COALESCE(j.published_at, j.updated_at, j.created_at) DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        return $this->db->fetchAll($sql, $params);
    }

    public function getCountAdmin($categorySlug = null, $status = 'all'): int {
        $sql = "SELECT COUNT(*) as count FROM jobs j LEFT JOIN categories c ON j.category_id = c.id";
        $where = [];
        $params = [];
        if ($status && $status !== 'all') {
            $where[] = "j.status = ?";
            $params[] = $status;
        }
        if ($categorySlug) {
            $where[] = "c.slug = ?";
            $params[] = $categorySlug;
        }
        if ($where) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $row = $this->db->fetchOne($sql, $params);
        return (int)($row['count'] ?? 0);
    }

    public function getAll($limit = 15, $offset = 0, $category = null): array {
        $sql = "SELECT j.*, c.name as category_name, c.slug as category_slug 
                FROM jobs j 
                LEFT JOIN categories c ON j.category_id = c.id 
                WHERE j.status = 'published'";
        
        $params = [];
        
        if ($category) {
            $sql .= " AND c.slug = ?";
            $params[] = $category;
        }
        
        $sql .= " ORDER BY j.published_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }

    public function getBySlug($slug) {
        $sql = "SELECT j.*, c.name as category_name, c.slug as category_slug 
                FROM jobs j 
                LEFT JOIN categories c ON j.category_id = c.id 
                WHERE j.slug = ? AND j.status = 'published'";
        
        return $this->db->fetchOne($sql, [$slug]);
    }

    public function getLatest($limit = 5): array {
        return $this->getAll($limit, 0);
    }

    public function getExpiringSoon($days = 7, $limit = 10): array {
        $sql = "SELECT j.*, c.name as category_name 
                FROM jobs j 
                LEFT JOIN categories c ON j.category_id = c.id 
                WHERE j.status = 'published' 
                AND j.last_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                ORDER BY j.last_date ASC 
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$days, $limit]);
    }

    public function search($query, $limit = 15, $offset = 0): array {
        $words = array_filter(preg_split('/\s+/', trim($query)));
        $conditions = ["j.status = 'published'"];
        $params = [];
        foreach ($words as $w) {
            $conditions[] = "(j.title LIKE ? OR j.organization LIKE ? OR j.location LIKE ?)";
            $like = "%$w%";
            array_push($params, $like, $like, $like);
        }
        $where = implode(' AND ', $conditions);
        $sql = "SELECT j.*, c.name as category_name
                FROM jobs j
                LEFT JOIN categories c ON j.category_id = c.id
                WHERE $where
                ORDER BY j.published_at DESC
                LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        return $this->db->fetchAll($sql, $params);
    }

    public function searchByCategory($query, $categoryId, $limit = 15, $offset = 0): array {
        $words = array_filter(preg_split('/\s+/', trim($query)));
        $conditions = ["j.status = 'published'", "j.category_id = ?"];
        $params = [$categoryId];
        foreach ($words as $w) {
            $conditions[] = "(j.title LIKE ? OR j.organization LIKE ? OR j.location LIKE ?)";
            $like = "%$w%";
            array_push($params, $like, $like, $like);
        }
        $where = implode(' AND ', $conditions);
        $sql = "SELECT j.*, c.name as category_name
                FROM jobs j
                LEFT JOIN categories c ON j.category_id = c.id
                WHERE $where
                ORDER BY j.published_at DESC
                LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        return $this->db->fetchAll($sql, $params);
    }

    public function countSearchByCategory($query, $categoryId): int {
        $words = array_filter(preg_split('/\s+/', trim($query)));
        $conditions = ["j.status = 'published'", "j.category_id = ?"];
        $params = [$categoryId];
        foreach ($words as $w) {
            $conditions[] = "(j.title LIKE ? OR j.organization LIKE ? OR j.location LIKE ?)";
            $like = "%$w%";
            array_push($params, $like, $like, $like);
        }
        $where = implode(' AND ', $conditions);
        $sql = "SELECT COUNT(*) as count FROM jobs j WHERE $where";
        $result = $this->db->fetchOne($sql, $params);
        return (int)($result['count'] ?? 0);
    }

    public function countSearch($query): int {
        $words = array_filter(preg_split('/\s+/', trim($query)));
        $conditions = ["j.status = 'published'"];
        $params = [];
        foreach ($words as $w) {
            $conditions[] = "(j.title LIKE ? OR j.organization LIKE ? OR j.location LIKE ?)";
            $like = "%$w%";
            array_push($params, $like, $like, $like);
        }
        $where = implode(' AND ', $conditions);
        $sql = "SELECT COUNT(*) as count FROM jobs j WHERE $where";
        $result = $this->db->fetchOne($sql, $params);
        return (int)($result['count'] ?? 0);
    }

    public function getCount($category = null) {
        $sql = "SELECT COUNT(*) as count FROM jobs j";
        $params = [];
        
        if ($category) {
            $sql .= " LEFT JOIN categories c ON j.category_id = c.id WHERE j.status = 'published' AND c.slug = ?";
            $params[] = $category;
        } else {
            $sql .= " WHERE j.status = 'published'";
        }
        
        $result = $this->db->fetchOne($sql, $params);
        return $result['count'];
    }

    public function create($data) {
        $sql = "INSERT INTO jobs (title, slug, organization, location, apply_link, last_date,
                vacancy_count, educational_qualification, age_limit, category_id, content, fees, vacancy_details, attachments,
                thumbnail_url, author_id, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $this->db->query($sql, [
            $data['title'],
            $data['slug'],
            $data['organization'],
            $data['location'],
            $data['apply_link'],
            $data['last_date'],
            $data['vacancy_count'],
            $data['educational_qualification'],
            $data['age_limit'],
            $data['category_id'],
            $data['content'],
            $data['fees'] ?? null,
            $data['vacancy_details'] ?? null,
            $data['attachments'] ? json_encode($data['attachments']) : null,
            $data['thumbnail_url'] ?? null,
            isset($data['author_id']) && $data['author_id'] !== '' ? (int)$data['author_id'] : null,
            $data['status'] ?? 'published'
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
        $sql = "UPDATE jobs SET " . implode(', ', $fields) . " WHERE id = ?";
        
        return $this->db->query($sql, $values);
    }
    
    public function getById($id) {
        $sql = "SELECT j.*, c.name as category_name, c.slug as category_slug 
                FROM jobs j 
                LEFT JOIN categories c ON j.category_id = c.id 
                WHERE j.id = ?";
        
        return $this->db->fetchOne($sql, [$id]);
    }

    public function delete($id) {
        $sql = "DELETE FROM jobs WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }

    // ===== Flexible Content Helpers (polymorphic via entity_type 'job') =====

    private function entityType(): string {
        return 'job';
    }

    // Events
    public function getEvents(int $jobId, ?string $eventType = null): array {
        $sql = "SELECT * FROM content_events WHERE entity_type = ? AND entity_id = ?";
        $params = [$this->entityType(), $jobId];
        if ($eventType) {
            $sql .= " AND event_type = ?";
            $params[] = $eventType;
        }
        $sql .= " ORDER BY sort_order ASC, start_date ASC, id ASC";
        return $this->db->fetchAll($sql, $params);
    }

    public function saveEvents(int $jobId, array $rows): bool {
        $conn = $this->db->getConnection();
        if ($conn === null) return false;
        $conn->beginTransaction();
        try {
            $del = $conn->prepare("DELETE FROM content_events WHERE entity_type = ? AND entity_id = ?");
            $del->execute([$this->entityType(), $jobId]);
            if (!empty($rows)) {
                $ins = $conn->prepare("INSERT INTO content_events (entity_type, entity_id, event_type, event_label, start_date, end_date, notes, sort_order) VALUES (?,?,?,?,?,?,?,?)");
                foreach ($rows as $r) {
                    $ins->execute([
                        $this->entityType(),
                        $jobId,
                        $r['event_type'] ?? 'other',
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
            error_log('saveEvents failed: ' . $e->getMessage());
            return false;
        }
    }

    // Links
    public function getLinks(int $jobId): array {
        $sql = "SELECT * FROM content_links WHERE entity_type = ? AND entity_id = ? ORDER BY sort_order ASC, id ASC";
        return $this->db->fetchAll($sql, [$this->entityType(), $jobId]);
    }

    public function saveLinks(int $jobId, array $rows): bool {
        $conn = $this->db->getConnection();
        if ($conn === null) return false;
        $conn->beginTransaction();
        try {
            $conn->prepare("DELETE FROM content_links WHERE entity_type = ? AND entity_id = ?")
                 ->execute([$this->entityType(), $jobId]);
            if (!empty($rows)) {
                $ins = $conn->prepare("INSERT INTO content_links (entity_type, entity_id, label, url, sort_order) VALUES (?,?,?,?,?)");
                foreach ($rows as $r) {
                    $ins->execute([
                        $this->entityType(),
                        $jobId,
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
            error_log('saveLinks failed: ' . $e->getMessage());
            return false;
        }
    }

    // FAQs
    public function getFaqs(int $jobId): array {
        $sql = "SELECT * FROM content_faqs WHERE entity_type = ? AND entity_id = ? AND is_active = 1 ORDER BY sort_order ASC, id ASC";
        return $this->db->fetchAll($sql, [$this->entityType(), $jobId]);
    }

    public function saveFaqs(int $jobId, array $rows): bool {
        $conn = $this->db->getConnection();
        if ($conn === null) return false;
        $conn->beginTransaction();
        try {
            $conn->prepare("DELETE FROM content_faqs WHERE entity_type = ? AND entity_id = ?")
                 ->execute([$this->entityType(), $jobId]);
            if (!empty($rows)) {
                $ins = $conn->prepare("INSERT INTO content_faqs (entity_type, entity_id, question, answer, sort_order, is_active) VALUES (?,?,?,?,?,?)");
                foreach ($rows as $r) {
                    $ins->execute([
                        $this->entityType(),
                        $jobId,
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
            error_log('saveFaqs failed: ' . $e->getMessage());
            return false;
        }
    }

    // Fees
    public function getFees(int $jobId): array {
        $sql = "SELECT * FROM content_fees WHERE entity_type = ? AND entity_id = ? ORDER BY sort_order ASC, id ASC";
        return $this->db->fetchAll($sql, [$this->entityType(), $jobId]);
    }

    public function saveFees(int $jobId, array $rows): bool {
        $conn = $this->db->getConnection();
        if ($conn === null) return false;
        $conn->beginTransaction();
        try {
            $conn->prepare("DELETE FROM content_fees WHERE entity_type = ? AND entity_id = ?")
                 ->execute([$this->entityType(), $jobId]);
            if (!empty($rows)) {
                $ins = $conn->prepare("INSERT INTO content_fees (entity_type, entity_id, category, amount, text, mode_notes, sort_order) VALUES (?,?,?,?,?,?,?)");
                foreach ($rows as $r) {
                    $ins->execute([
                        $this->entityType(),
                        $jobId,
                        $r['category'] ?? '',
                        isset($r['amount']) && $r['amount'] !== '' ? $r['amount'] : null,
                        $r['text'] ?? null,
                        $r['mode_notes'] ?? null,
                        isset($r['sort_order']) ? (int)$r['sort_order'] : 0,
                    ]);
                }
            }
            $conn->commit();
            return true;
        } catch (Exception $e) {
            $conn->rollBack();
            error_log('saveFees failed: ' . $e->getMessage());
            return false;
        }
    }

    // Age Limit (single row)
    public function getAgeLimit(int $jobId): ?array {
        $sql = "SELECT * FROM content_age_limits WHERE entity_type = ? AND entity_id = ? LIMIT 1";
        $row = $this->db->fetchOne($sql, [$this->entityType(), $jobId]);
        return $row ?: null;
    }

    public function saveAgeLimit(int $jobId, array $data): bool {
        $conn = $this->db->getConnection();
        if ($conn === null) return false;
        $conn->beginTransaction();
        try {
            $conn->prepare("DELETE FROM content_age_limits WHERE entity_type = ? AND entity_id = ?")
                 ->execute([$this->entityType(), $jobId]);
            $ins = $conn->prepare("INSERT INTO content_age_limits (entity_type, entity_id, min_age, max_age, cutoff_date, relaxation_text) VALUES (?,?,?,?,?,?)");
            $ins->execute([
                $this->entityType(),
                $jobId,
                isset($data['min_age']) && $data['min_age'] !== '' ? (int)$data['min_age'] : null,
                isset($data['max_age']) && $data['max_age'] !== '' ? (int)$data['max_age'] : null,
                $data['cutoff_date'] ?? null,
                $data['relaxation_text'] ?? null,
            ]);
            $conn->commit();
            return true;
        } catch (Exception $e) {
            $conn->rollBack();
            error_log('saveAgeLimit failed: ' . $e->getMessage());
            return false;
        }
    }

    // Vacancies
    public function getVacancies(int $jobId): array {
        $sql = "SELECT * FROM content_vacancies WHERE entity_type = ? AND entity_id = ? ORDER BY sort_order ASC, id ASC";
        return $this->db->fetchAll($sql, [$this->entityType(), $jobId]);
    }

    public function saveVacancies(int $jobId, array $rows): bool {
        $conn = $this->db->getConnection();
        if ($conn === null) return false;
        $conn->beginTransaction();
        try {
            $conn->prepare("DELETE FROM content_vacancies WHERE entity_type = ? AND entity_id = ?")
                 ->execute([$this->entityType(), $jobId]);
            if (!empty($rows)) {
                $ins = $conn->prepare("INSERT INTO content_vacancies (entity_type, entity_id, post_name, category, total_posts, eligibility_text, pay_scale, sort_order) VALUES (?,?,?,?,?,?,?,?)");
                foreach ($rows as $r) {
                    $ins->execute([
                        $this->entityType(),
                        $jobId,
                        $r['post_name'] ?? '',
                        $r['category'] ?? null,
                        isset($r['total_posts']) && $r['total_posts'] !== '' ? (int)$r['total_posts'] : null,
                        $r['eligibility_text'] ?? null,
                        $r['pay_scale'] ?? null,
                        isset($r['sort_order']) ? (int)$r['sort_order'] : 0,
                    ]);
                }
            }
            $conn->commit();
            return true;
        } catch (Exception $e) {
            $conn->rollBack();
            error_log('saveVacancies failed: ' . $e->getMessage());
            return false;
        }
    }

    // Sections
    public function getSections(int $jobId, ?string $sectionType = null): array {
        $sql = "SELECT * FROM content_sections WHERE entity_type = ? AND entity_id = ?";
        $params = [$this->entityType(), $jobId];
        if ($sectionType) {
            $sql .= " AND section_type = ?";
            $params[] = $sectionType;
        }
        $sql .= " ORDER BY sort_order ASC, id ASC";
        return $this->db->fetchAll($sql, $params);
    }

    public function saveSections(int $jobId, array $rows): bool {
        $conn = $this->db->getConnection();
        if ($conn === null) return false;
        $conn->beginTransaction();
        try {
            $conn->prepare("DELETE FROM content_sections WHERE entity_type = ? AND entity_id = ?")
                 ->execute([$this->entityType(), $jobId]);
            if (!empty($rows)) {
                $ins = $conn->prepare("INSERT INTO content_sections (entity_type, entity_id, section_type, title, content, sort_order) VALUES (?,?,?,?,?,?)");
                foreach ($rows as $r) {
                    $ins->execute([
                        $this->entityType(),
                        $jobId,
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
            error_log('saveSections failed: ' . $e->getMessage());
            return false;
        }
    }
}
?>
