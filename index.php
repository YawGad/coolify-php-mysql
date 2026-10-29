
<?php
require_once __DIR__ . '/config/database.php';

function escapeHtml(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

$students = [];
$error = null;
$formError = null;

try {
    $pdo = getDatabaseConnection();

    // CREATE: Save a new student
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $course = trim($_POST['course'] ?? '');

        if (
            $name === '' ||
            $course === '' ||
            !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            $formError = 'Please enter valid student details.';
        } elseif (
            strlen($name) > 100 ||
            strlen($email) > 150 ||
            strlen($course) > 100
        ) {
            $formError = 'One or more fields are too long.';
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

                // Redirect to prevent duplicate form submission
                header('Location: /?saved=1', true, 303);
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $formError = 'This email is already registered.';
                } else {
                    throw $e;
                }
            }
        }
    }

    // READ: Retrieve all students
    $stmt = $pdo->query(
        'SELECT id, name, email, course, created_at
         FROM students
         ORDER BY id DESC'
    );

    $students = $stmt->fetchAll();

} catch (Throwable $e) {
    error_log($e->getMessage());
    $error = 'Database operation unavailable.';
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
        body {
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            margin: 0;
            padding: 32px 16px;
            color: #1f2937;
        }
        .container {
            max-width: 900px;
            margin: auto;
        }
        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px #0001;
        }
        h1 { color: #2563eb; }
        label {
            display: block;
            margin: 12px 0 6px;
            font-weight: bold;
        }
        input {
            width: 100%;
            padding: 11px;
            box-sizing: border-box;
            border: 1px solid #d1d5db;
            border-radius: 6px;
        }
        button {
            margin-top: 16px;
            background: #2563eb;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .table-wrap { overflow-x: auto; }
        .success { color: #15803d; }
        .error { color: #dc2626; }
    </style>
</head>
<body>
<div class="container">

    <h1>Student Management System</h1>
    <p>PHP + MySQL + Docker + Coolify</p>

    <div class="card">
        <h2>Register New Student</h2>

        <?php if (isset($_GET['saved'])): ?>
            <p class="success">
                Student registered successfully!
            </p>
        <?php endif; ?>

        <?php if ($formError): ?>
            <p class="error">
                <?= escapeHtml($formError) ?>
            </p>
        <?php endif; ?>

        <?php if ($error): ?>
            <p class="error">
                <?= escapeHtml($error) ?>
            </p>
        <?php else: ?>
            <form method="POST">
                <label>Full Name</label>
                <input type="text" name="name"
                       maxlength="100" required>

                <label>Email Address</label>
                <input type="email" name="email"
                       maxlength="150" required>

                <label>Course</label>
                <input type="text" name="course"
                       maxlength="100" required>

                <button type="submit">
                    Register Student
                </button>
            </form>
        <?php endif; ?>
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
