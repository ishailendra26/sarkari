<?php
/**
 * SarkariJobs Portal Setup Script
 * Run this file once to set up the database and initial data
 */

require_once 'src/config.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Setup - SarkariJobs Portal</title>
    <script src='https://cdn.tailwindcss.com'></script>
</head>
<body class='bg-gray-100 p-8'>
    <div class='max-w-4xl mx-auto'>
        <div class='bg-white rounded-lg shadow-md p-8'>
            <h1 class='text-3xl font-bold text-gray-800 mb-6'>SarkariJobs Portal Setup</h1>";

try {
    // Create database connection
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<div class='mb-4 p-4 bg-green-100 text-green-800 rounded-lg'>✓ Database connection successful</div>";
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE " . DB_NAME);
    
    echo "<div class='mb-4 p-4 bg-green-100 text-green-800 rounded-lg'>✓ Database created/selected: " . DB_NAME . "</div>";
    
    // Read and execute main SQL file
    $mainSql = file_get_contents('sarkari.sql');
    if ($mainSql) {
        $statements = explode(';', $mainSql);
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        echo "<div class='mb-4 p-4 bg-green-100 text-green-800 rounded-lg'>✓ Main database schema created</div>";
    }
    
    // Read and execute migration file 001 (sample data)
    $migrationSql = file_get_contents('migrations/001_init.sql');
    if ($migrationSql) {
        $statements = explode(';', $migrationSql);
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        echo "<div class='mb-4 p-4 bg-green-100 text-green-800 rounded-lg'>✓ Sample data inserted (001_init.sql)</div>";
    }

    // Execute all remaining migrations in order
    $migrationsDir = __DIR__ . DIRECTORY_SEPARATOR . 'migrations';
    if (is_dir($migrationsDir)) {
        $files = glob($migrationsDir . DIRECTORY_SEPARATOR . '*.sql');
        sort($files, SORT_NATURAL);
        foreach ($files as $file) {
            // 001_init.sql already executed above
            if (preg_match('/001_init\.sql$/', $file)) {
                continue;
            }
            $sql = file_get_contents($file);
            if (!$sql) continue;
            $statements = explode(';', $sql);
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if ($statement === '') continue;
                try {
                    $pdo->exec($statement);
                } catch (PDOException $ex) {
                    // Non-fatal: show warning and continue (idempotent migrations may error on existing columns/constraints)
                    echo "<div class='mb-2 p-3 bg-yellow-100 text-yellow-800 rounded'>⚠ Migration warning in " . basename($file) . ": " . htmlspecialchars($ex->getMessage()) . "</div>";
                }
            }
            echo "<div class='mb-2 p-3 bg-green-100 text-green-800 rounded'>✓ Executed migration: " . basename($file) . "</div>";
        }
    }
    
    // Create uploads directory
    $uploadDir = 'uploads';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
        echo "<div class='mb-4 p-4 bg-green-100 text-green-800 rounded-lg'>✓ Uploads directory created</div>";
    } else {
        echo "<div class='mb-4 p-4 bg-blue-100 text-blue-800 rounded-lg'>ℹ Uploads directory already exists</div>";
    }
    
    echo "<div class='mb-6 p-6 bg-green-50 border border-green-200 rounded-lg'>
            <h2 class='text-xl font-bold text-green-800 mb-4'>🎉 Setup Complete!</h2>
            <p class='text-green-700 mb-4'>Your SarkariJobs portal has been successfully set up.</p>
            
            <div class='grid grid-cols-1 md:grid-cols-2 gap-4'>
                <div>
                    <h3 class='font-semibold text-green-800 mb-2'>Frontend Access:</h3>
                    <a href='.' class='text-blue-600 hover:underline'>Visit Your Website →</a>
                </div>
                <div>
                    <h3 class='font-semibold text-green-800 mb-2'>Admin Access:</h3>
                    <a href='admin/' class='text-blue-600 hover:underline'>Admin Panel →</a>
                    <div class='text-sm text-gray-600 mt-1'>
                        Username: admin<br>
                        Password: admin123
                    </div>
                </div>
            </div>
            
            <div class='mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded'>
                <h4 class='font-semibold text-yellow-800'>⚠️ Security Notice:</h4>
                <p class='text-yellow-700 text-sm'>Please change the default admin password after first login!</p>
            </div>
          </div>";
    
} catch (PDOException $e) {
    echo "<div class='mb-4 p-4 bg-red-100 text-red-800 rounded-lg'>✗ Error: " . $e->getMessage() . "</div>";
    echo "<div class='p-4 bg-yellow-100 text-yellow-800 rounded-lg'>
            <h3 class='font-semibold mb-2'>Troubleshooting:</h3>
            <ul class='list-disc list-inside text-sm'>
                <li>Check your database credentials in src/config.php</li>
                <li>Ensure MySQL server is running</li>
                <li>Verify database user has CREATE privileges</li>
                <li>Check if database name already exists</li>
            </ul>
          </div>";
}

echo "        </div>
    </div>
</body>
</html>";
?>
