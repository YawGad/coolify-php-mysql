
<?php

require_once __DIR__ . '/config/database.php';

$status = 'Not connected';
$error = null;

try {
    $pdo = getDatabaseConnection();
    $pdo->query('SELECT 1');
    $status = 'Connected successfully!';
} catch (Throwable $e) {
    error_log($e->getMessage());
    $error = 'Database connection unavailable.';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP + MySQL Demo</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            display: grid;
            place-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .card {
            background: white;
            padding: 35px;
            border-radius: 14px;
            text-align: center;
            box-shadow: 0 8px 30px #0001;
        }

        h1 { color: #2563eb; }
        .success { color: #15803d; }
        .error { color: #dc2626; }
    </style>
</head>
<body>
    <div class="card">
        <h1>PHP + MySQL Application</h1>
        <p>Running with Docker and Coolify</p>

        <h3>Database Status</h3>

        <?php if ($error === null): ?>
            <p class="success">
                <?= htmlspecialchars($status) ?>
            </p>
        <?php else: ?>
            <p class="error">
                <?= htmlspecialchars($error) ?>
            </p>
        <?php endif; ?>

        <p>PHP Version: <?= htmlspecialchars(PHP_VERSION) ?></p>
    </div>
</body>
</html>
