<?php
// API helper utilities for JSON responses, validation, and CORS

function api_send_cors(): void {
    // Allow all origins for now; adjust to your mobile app origin in production
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

function api_handle_options_preflight(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        api_send_cors();
        http_response_code(204);
        exit;
    }
}

function api_json_response($data, int $status = 200): void {
    api_send_cors();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($status);
    echo json_encode([
        'success' => $status >= 200 && $status < 300,
        'data' => $data,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function api_error(string $code, string $message, int $status = 400, array $extra = []): void {
    api_send_cors();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($status);
    $payload = [
        'success' => false,
        'error' => array_merge([
            'code' => $code,
            'message' => $message,
        ], $extra),
    ];
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function api_int($value, int $default, ?int $min = null, ?int $max = null): int {
    if ($value === null || $value === '') return $default;
    if (!is_numeric($value)) return $default;
    $v = (int)$value;
    if ($min !== null && $v < $min) $v = $min;
    if ($max !== null && $v > $max) $v = $max;
    return $v;
}

function api_str(?string $value, ?int $maxLen = 255): ?string {
    if ($value === null) return null;
    $s = trim($value);
    if ($maxLen !== null) {
        $s = mb_substr($s, 0, $maxLen);
    }
    return $s;
}

function api_pagination(): array {
    $page = api_int($_GET['page'] ?? null, 1, 1, null);
    $perPage = api_int($_GET['per_page'] ?? null, 10, 1, 50);
    $offset = ($page - 1) * $perPage;
    return [$page, $perPage, $offset];
}

function api_wrap_list(array $items, int $page, int $perPage, int $total): array {
    $totalPages = (int)ceil($total / max(1, $perPage));
    return [
        'items' => $items,
        'meta' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
        ],
    ];
}
