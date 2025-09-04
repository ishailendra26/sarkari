<?php
require_once __DIR__ . '/../db.php';

class Ad {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    public function getAll($limit = 50, $offset = 0) {
        $sql = "SELECT * FROM ads ORDER BY updated_at DESC, priority DESC LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$limit, $offset]);
    }

    public function getById($id) {
        return $this->db->fetchOne("SELECT * FROM ads WHERE id = ?", [$id]);
    }

    public function create($data) {
        $sql = "INSERT INTO ads (name, placement, page_scope, slug_scope, code, status, priority, start_at, end_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $this->db->query($sql, [
            $data['name'],
            $data['placement'],
            $data['page_scope'] ?? 'all',
            $data['slug_scope'] ?? null,
            $data['code'],
            $data['status'] ?? 'active',
            (int)($data['priority'] ?? 0),
            !empty($data['start_at']) ? $data['start_at'] : null,
            !empty($data['end_at']) ? $data['end_at'] : null,
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $fields = [];
        $values = [];
        foreach ($data as $k => $v) {
            $fields[] = "$k = ?";
            $values[] = $v;
        }
        $values[] = $id;
        $sql = "UPDATE ads SET " . implode(', ', $fields) . " WHERE id = ?";
        return $this->db->query($sql, $values);
    }

    public function delete($id) {
        return $this->db->query("DELETE FROM ads WHERE id = ?", [$id]);
    }

    // Fetch active ads for placement/scope, considering schedule
    public function getActive($placement, $pageScope = 'all', $slugScope = null) {
        $now = date('Y-m-d H:i:s');
        $params = [$placement, 'active', $now, $now];
        $where = "placement = ? AND status = ? AND (start_at IS NULL OR start_at <= ?) AND (end_at IS NULL OR end_at >= ?)";

        // Scope precedence: exact slug match > page scope > all
        $order = " CASE 
            WHEN page_scope = 'all' THEN 3
            WHEN page_scope = ? AND (slug_scope IS NULL OR slug_scope = '') THEN 2
            WHEN page_scope = ? AND slug_scope = ? THEN 1
            ELSE 4 END, priority DESC";

        $params[] = $pageScope; // for order
        $params[] = $pageScope; // for order
        $params[] = $slugScope; // for order

        $sql = "SELECT * FROM ads WHERE $where ORDER BY $order";
        return $this->db->fetchAll($sql, $params);
    }
}
?>
