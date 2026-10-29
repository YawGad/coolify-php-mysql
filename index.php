<?php
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/database.php';

$students = [];
$editingStudent = null;
$error = null;
$success = $_GET['success'] ?? '';

function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

try {
    $pdo = getDatabaseConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrfToken();

        $action = $_POST['action'] ?? '';

        if ($action === 'create' || $action === 'update') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $course = trim($_POST['course'] ?? '');

            if (
                $name === '' ||
                $course === '' ||
                !filter_var($email, FILTER_VALIDATE_EMAIL)
            ) {
                $error = 'Please enter valid student information.';
            } elseif (
                strlen($name) > 100 ||
                strlen($email) > 150 ||
                strlen($course) > 100
            ) {
                $error = 'One or more fields are too long.';
            } else {
                if ($action === 'create') {
                    $stmt = $pdo->prepare(
                        'INSERT INTO students (name, email, course)
                         VALUES (?, ?, ?)'
                    );

                    $stmt->execute([$name, $email, $course]);
                } else {
                    $id = filter_var(
                        $_POST['id'] ?? null,
                        FILTER_VALIDATE_INT
                    );

                    if (!$id || $id < 1) {
                        throw new RuntimeException('Invalid student ID.');
                    }

                    $stmt = $pdo->prepare(
                        'UPDATE students
                         SET name = ?, email = ?, course = ?
                         WHERE id = ?'
                    );

                    $stmt->execute([$name, $email, $course, $id]);
                }

                header('Location: /?success=saved', true, 303);
                exit;
            }
        } elseif ($action === 'delete') {
            $id = filter_var(
                $_POST['id'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$id || $id < 1) {
                throw new RuntimeException('Invalid student ID.');
            }

            $stmt = $pdo->prepare(
                'DELETE FROM students WHERE id = ?'
            );

            $stmt->execute([$id]);

            header('Location: /?success=deleted', true, 303);
            exit;
        }
    }

    if (isset($_GET['edit'])) {
        $id = filter_var(
            $_GET['edit'],
            FILTER_VALIDATE_INT
        );

        if ($id && $id > 0) {
            $stmt = $pdo->prepare(
                'SELECT id, name, email, course
                 FROM students WHERE id = ?'
            );

            $stmt->execute([$id]);
            $editingStudent = $stmt->fetch() ?: null;
        }
    }

    $students = $pdo->query(
        'SELECT id, name, email, course, created_at
         FROM students ORDER BY id DESC'
    )->fetchAll();

} catch (PDOException $e) {
    error_log($e->getMessage());

    $error = $e->getCode() === '23000'
        ? 'This email is already registered.'
        : 'Database operation failed. Check logs.';
} catch (Throwable $e) {
    error_log($e->getMessage());
    $error = 'An error occurred. Check logs.';
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Management</title>
    <style>
        * {
            box-sizing: border-box;
        }

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

        h1 {
            color: #2563eb;
        }

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

        button:hover {
            background: #1d4ed8;
        }

        .success {
            color: #15803d;
        }

        .error {
            color: #dc2626;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        th {
            background: #f9fafb;
        }
    </style>
</head>

<body>
    <div class="container">

        <div class="card">
            <h1>Student Management</h1>
            <p>PHP + MySQL using Docker and Coolify</p>


            <?php if ($success === 'saved'): ?>
                <p class="success">Student saved successfully!</p>
            <?php elseif ($success === 'deleted'): ?>
                <p class="success">Student deleted successfully!</p>
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
                <input type="hidden" name="csrf_token" value="<?= escapeHtml(csrfToken()) ?>">

                <input type="hidden" name="action" value="<?= $editingStudent ? 'update' : 'create' ?>">

                <?php if ($editingStudent): ?>
                    <input type="hidden" name="id" value="<?= (int) $editingStudent['id'] ?>">
                <?php endif; ?>

                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" maxlength="100" required
                    value="<?= escapeHtml($editingStudent['name'] ?? '') ?>">

                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" maxlength="150" required
                    value="<?= escapeHtml($editingStudent['email'] ?? '') ?>">

                <label for="course">Course</label>
                <input type="text" id="course" name="course" maxlength="100" required
                    value="<?= escapeHtml($editingStudent['course'] ?? '') ?>">

                <button type="submit">
                    <?= $editingStudent ? 'Update Student' : 'Add Student' ?>
                </button>

                <?php if ($editingStudent): ?>
                    <a href="/">Cancel Editing</a>
                <?php endif; ?>
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
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?= (int) $student['id'] ?></td>
                                <td><?= escapeHtml($student['name']) ?></td>
                                <td><?= escapeHtml($student['email']) ?></td>
                                <td><?= escapeHtml($student['course']) ?></td>
                                <td><?= escapeHtml($student['created_at']) ?></td>

                                <td>
                                    <a href="/?edit=<?= (int) $student['id'] ?>">
                                        Edit
                                    </a>

                                    <form method="POST" action="/" style="display:inline"
                                        onsubmit="return confirm('Delete this student?');">

                                        <input type="hidden" name="csrf_token" value="<?= escapeHtml(csrfToken()) ?>">

                                        <input type="hidden" name="action" value="delete">

                                        <input type="hidden" name="id" value="<?= (int) $student['id'] ?>">

                                        <button type="submit" style="background:#dc2626;margin:0;">
                                            Delete
                                        </button>
                                    </form>
                                </td>

                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="6">
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