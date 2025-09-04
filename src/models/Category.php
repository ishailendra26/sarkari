<?php
require_once __DIR__ . '/../db.php';

class Category {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function getAll() {
        $sql = "SELECT * FROM categories ORDER BY name";
        return $this->db->fetchAll($sql);
    }

    public function getById($id) {
        $sql = "SELECT * FROM categories WHERE id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }

    public function getBySlug($slug) {
        $sql = "SELECT * FROM categories WHERE slug = ?";
        return $this->db->fetchOne($sql, [$slug]);
    }

    public function create($data) {
        $sql = "INSERT INTO categories (name, slug) VALUES (?, ?)";
        $this->db->query($sql, [$data['name'], $data['slug']]);
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
        $sql = "UPDATE categories SET " . implode(', ', $fields) . " WHERE id = ?";
        
        return $this->db->query($sql, $values);
    }

    public function delete($id) {
        $sql = "DELETE FROM categories WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }

    public function getJobCount($categoryId) {
        $stmt = $this->db->query("SELECT COUNT(*) FROM jobs WHERE category_id = ?", [$categoryId]);
        return $stmt->fetchColumn();
    }

    public function getCount() {
        $stmt = $this->db->query("SELECT COUNT(*) FROM categories");
        return $stmt->fetchColumn();
    }
}
?>
