<?php
require_once __DIR__ . '/../db.php';

class Post {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function getAll($limit = 15, $offset = 0, $category = null) {
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug, 
                        a.name as author_name, a.avatar_url as author_avatar
                FROM posts p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN authors a ON p.author_id = a.id
                WHERE p.status = 'published'";
        
        $params = [];
        
        if ($category) {
            $sql .= " AND c.slug = ?";
            $params[] = $category;
        }
        
        $sql .= " ORDER BY p.published_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }

    public function getBySlug($slug) {
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug,
                       a.name as author_name, a.avatar_url as author_avatar
                FROM posts p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN authors a ON p.author_id = a.id
                WHERE p.slug = ? AND p.status = 'published'";
        
        return $this->db->fetchOne($sql, [$slug]);
    }

    public function getLatest($limit = 5) {
        return $this->getAll($limit, 0);
    }

    public function search($query, $limit = 15, $offset = 0) {
        $sql = "SELECT p.*, c.name as category_name, a.name as author_name, a.avatar_url as author_avatar
                FROM posts p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN authors a ON p.author_id = a.id
                WHERE p.status = 'published' 
                AND (p.title LIKE ? OR p.excerpt LIKE ? OR p.content LIKE ?) 
                ORDER BY p.published_at DESC 
                LIMIT ? OFFSET ?";
        
        $searchTerm = "%$query%";
        return $this->db->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm, $limit, $offset]);
    }

    public function countSearch($query) {
        $sql = "SELECT COUNT(*) as count 
                FROM posts p 
                WHERE p.status = 'published' 
                AND (p.title LIKE ? OR p.excerpt LIKE ? OR p.content LIKE ?)";
        $searchTerm = "%$query%";
        $row = $this->db->fetchOne($sql, [$searchTerm, $searchTerm, $searchTerm]);
        return (int)$row['count'];
    }

    public function getCount($category = null) {
        $sql = "SELECT COUNT(*) as count FROM posts p";
        $params = [];
        
        if ($category) {
            $sql .= " LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 'published' AND c.slug = ?";
            $params[] = $category;
        } else {
            $sql .= " WHERE p.status = 'published'";
        }
        
        $result = $this->db->fetchOne($sql, $params);
        return $result['count'];
    }

    public function create($data) {
        $sql = "INSERT INTO posts (title, slug, excerpt, content, category_id, status, meta_title, meta_description, thumbnail_url, author_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $this->db->query($sql, [
            $data['title'],
            $data['slug'],
            $data['excerpt'] ?? null,
            $data['content'],
            $data['category_id'] ?? null,
            $data['status'] ?? 'published',
            $data['meta_title'] ?? null,
            $data['meta_description'] ?? null,
            $data['thumbnail_url'] ?? null,
            $data['author_id'] ?? null
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
        $sql = "UPDATE posts SET " . implode(', ', $fields) . " WHERE id = ?";
        
        return $this->db->query($sql, $values);
    }
    
    public function getById($id) {
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug 
                FROM posts p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.id = ?";
        
        return $this->db->fetchOne($sql, [$id]);
    }

    public function delete($id) {
        $sql = "DELETE FROM posts WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
}
?>
