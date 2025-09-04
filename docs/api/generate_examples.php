<?php
// Read-only helper to fetch a few recent slugs/titles from the DB
// Usage: http://localhost/sarkari/docs/api/generate_examples.php

header('Content-Type: application/json');

try {
    // Bootstrap DB and models
    require_once __DIR__ . '/../../src/db.php';
    require_once __DIR__ . '/../../src/models/Job.php';
    require_once __DIR__ . '/../../src/models/Result.php';
    require_once __DIR__ . '/../../src/models/AdmitCard.php';
    require_once __DIR__ . '/../../src/models/Syllabus.php';
    require_once __DIR__ . '/../../src/models/Post.php';

    $baseUrl = 'http://localhost/sarkari/api/index.php/v1';

    $jobs = (new Job())->getAll(3, 0); // latest 3
    $results = (new Result())->getAll(3, 0);
    $admitCards = (new AdmitCard())->getAll(3, 0);
    $syllabi = (new Syllabus())->getAll(3, 0);
    $posts = (new Post())->getAll(3, 0);

    $pick = function ($rows) {
        return array_map(function ($r) {
            return [
                'id' => $r['id'] ?? null,
                'title' => $r['title'] ?? null,
                'slug' => $r['slug'] ?? null,
                'published_at' => $r['published_at'] ?? null,
            ];
        }, $rows ?: []);
    };

    echo json_encode([
        'baseUrl' => $baseUrl,
        'jobs' => $pick($jobs),
        'results' => $pick($results),
        'admit_cards' => $pick($admitCards),
        'syllabi' => $pick($syllabi),
        'posts' => $pick($posts),
        'notes' => 'Use these slugs to update docs/api/postman_collection.json variables and OpenAPI examples as desired.'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => [
            'message' => $e->getMessage(),
            'type' => get_class($e)
        ]
    ], JSON_PRETTY_PRINT);
}
