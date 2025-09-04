<?php
require_once __DIR__ . '/../db.php';

class Author {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function getAll($limit = 20, $offset = 0) {
        $sql = "SELECT * FROM authors ORDER BY name ASC LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$limit, $offset]);
    }

    public function getById($id) {
        $sql = "SELECT * FROM authors WHERE id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }

    public function getBySlug($slug) {
        $sql = "SELECT * FROM authors WHERE slug = ?";
        return $this->db->fetchOne($sql, [$slug]);
    }

    public function create($data) {
        $sql = "INSERT INTO authors (name, slug, bio, avatar_url, verified, social) VALUES (?, ?, ?, ?, ?, ?)";
        $this->db->query($sql, [
            $data['name'],
            $data['slug'],
            $data['bio'] ?? null,
            $data['avatar_url'] ?? null,
            !empty($data['verified']) ? 1 : 0,
            isset($data['social']) ? json_encode($data['social']) : null,
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $fields = [];
        $values = [];
        foreach ($data as $k => $v) {
            if ($k === 'social' && is_array($v)) { $v = json_encode($v); }
            $fields[] = "$k = ?";
            $values[] = $v;
        }
        $values[] = $id;
        $sql = "UPDATE authors SET " . implode(', ', $fields) . " WHERE id = ?";
        return $this->db->query($sql, $values);
    }

    // Content by author helpers (basic)
    public function getJobsByAuthor($authorId, $limit = 10, $offset = 0) {
        $sql = "SELECT id, title, slug, organization, published_at, thumbnail_url FROM jobs WHERE author_id = ? ORDER BY published_at DESC LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$authorId, $limit, $offset]);
    }

    public function getResultsByAuthor($authorId, $limit = 10, $offset = 0) {
        $sql = "SELECT id, title, slug, published_at, thumbnail_url FROM results WHERE author_id = ? ORDER BY published_at DESC LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$authorId, $limit, $offset]);
    }

    public function getAdmitsByAuthor($authorId, $limit = 10, $offset = 0) {
        $sql = "SELECT id, title, slug, published_at, thumbnail_url FROM admit_cards WHERE author_id = ? ORDER BY published_at DESC LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$authorId, $limit, $offset]);
    }

    public function getSyllabiByAuthor($authorId, $limit = 10, $offset = 0) {
        $sql = "SELECT id, title, slug, published_at, thumbnail_url FROM syllabi WHERE author_id = ? ORDER BY published_at DESC LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$authorId, $limit, $offset]);
    }
}
?>
