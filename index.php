
<?php
require_once __DIR__ . '/config/database.php';

$students = [];
$error = null;
$success = isset($_GET['success']);

try {
    $pdo = getDatabaseConnection();

    // CREATE: Process the submitted student form
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $course = trim($_POST['course'] ?? '');

        if ($name === '' || $course === '' ||
            !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid name, email and course.';
        } elseif (
            strlen($name) > 100 ||
            strlen($email) > 150 ||
            strlen($course) > 100
        ) {
            $error = 'One or more fields are too long.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO students (name, email, course)
                 VALUES (:name, :email, :course)'
            );

            try {
                $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':course' => $course
                ]);

                // Prevent duplicate submission on refresh
                header('Location: /?success=1', true, 303);
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $error = 'This email is already registered.';
                } else {
                    throw $e;
                }
            }
        }
    }

    // READ: Retrieve student records
    $stmt = $pdo->query(
        'SELECT id, name, email, course, created_at
         FROM students
         ORDER BY id DESC'
    );

    $students = $stmt->fetchAll();

} catch (Throwable $e) {
    error_log($e->getMessage());
    $error = 'A database error occurred. Check application logs.';
}

function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1">
    <title>Student Management</title>
    <style>
        * { box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            color: #1f2937;
            margin: 0;
            padding: 30px 16px;
        }

        .container {
            max-width: 950px;
            margin: auto;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px #0000000d;
        }

        h1 { color: #2563eb; }

        label {
            display: block;
            font-weight: bold;
            margin: 14px 0 6px;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
        }

        button {
            margin-top: 18px;
            padding: 12px 20px;
            background: #2563eb;
            color: white;
            border: 0;
            border-radius: 6px;
            cursor: pointer;
        }

        button:hover { background: #1d4ed8; }

        .success { color: #15803d; }
        .error { color: #dc2626; }

        .table-wrap { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        th { background: #f9fafb; }
    </style>
</head>
<body>
<div class="container">

    <div class="card">
        <h1>Student Management</h1>
        <p>PHP + MySQL using Docker and Coolify</p>

        <?php if ($success): ?>
            <p class="success">Student added successfully!</p>
        <?php endif; ?>

        <?php if ($error): ?>
            <p class="error">
                <?= escapeHtml($error) ?>
            </p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Add New Student</h2>

        <form method="POST" action="/">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name"
                   maxlength="100" required>

            <label for="email">Email Address</label>
            <input type="email" id="email" name="email"
                   maxlength="150" required>

            <label for="course">Course</label>
            <input type="text" id="course" name="course"
                   maxlength="100" required>

            <button type="submit">Add Student</button>
        </form>
    </div>

    <div class="card">
        <h2>Registered Students</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Course</th>
                        <th>Registered</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?= (int)$student['id'] ?></td>
                        <td><?= escapeHtml($student['name']) ?></td>
                        <td><?= escapeHtml($student['email']) ?></td>
                        <td><?= escapeHtml($student['course']) ?></td>
                        <td><?= escapeHtml($student['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="5">
                            No students registered yet.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</body>
</html>
