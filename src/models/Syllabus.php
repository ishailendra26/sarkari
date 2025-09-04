<?php
require_once __DIR__ . '/../db.php';

class Syllabus {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function getAll($limit = 10, $offset = 0) {
        $sql = "SELECT * FROM syllabi ORDER BY published_at DESC LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$limit, $offset]);
    }

    public function getBySlug($slug) {
        $sql = "SELECT * FROM syllabi WHERE slug = ?";
        return $this->db->fetchOne($sql, [$slug]);
    }

    public function getLatest($limit = 5) {
        return $this->getAll($limit, 0);
    }

    public function search($query, $limit = 10, $offset = 0) {
        $words = array_filter(preg_split('/\s+/', trim($query)));
        $conditions = [];
        $params = [];
        foreach ($words as $w) {
            $conditions[] = "(title LIKE ? OR sections LIKE ?)";
            $like = "%$w%";
            array_push($params, $like, $like);
        }
        $where = $conditions ? ("WHERE " . implode(' AND ', $conditions)) : '';
        $sql = "SELECT * FROM syllabi $where ORDER BY published_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        return $this->db->fetchAll($sql, $params);
    }

    public function countSearch($query): int {
        $words = array_filter(preg_split('/\s+/', trim($query)));
        $conditions = [];
        $params = [];
        foreach ($words as $w) {
            $conditions[] = "(title LIKE ? OR sections LIKE ?)";
            $like = "%$w%";
            array_push($params, $like, $like);
        }
        $where = $conditions ? ("WHERE " . implode(' AND ', $conditions)) : '';
        $sql = "SELECT COUNT(*) as count FROM syllabi $where";
        $result = $this->db->fetchOne($sql, $params);
        return (int)($result['count'] ?? 0);
    }

    public function getCount() {
        $result = $this->db->fetchOne("SELECT COUNT(*) as count FROM syllabi");
        return $result['count'];
    }

    public function create($data) {
        $sql = "INSERT INTO syllabi (title, slug, organization, description, exam_date, download_url, sections, thumbnail_url, author_id, status, published_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $this->db->query($sql, [
            $data['title'],
            $data['slug'],
            $data['organization'],
            $data['description'],
            $data['exam_date'],
            $data['download_url'],
            $data['sections'],
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
        $sql = "UPDATE syllabi SET " . implode(', ', $fields) . " WHERE id = ?";
        
        return $this->db->query($sql, $values);
    }
    
    public function getById($id) {
        $sql = "SELECT * FROM syllabi WHERE id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }

    public function delete($id) {
        $sql = "DELETE FROM syllabi WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
}
?>
