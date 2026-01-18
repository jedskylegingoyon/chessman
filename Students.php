<?php
session_start();

class StudentGradeSystem {
    private $filename = 'grades.json';
    private $students = [];

    public function __construct() {
        $this->load_data();
        $this->handle_action();
    }

    private function load_data() {
        if (file_exists($this->filename)) {
            $content = file_get_contents($this->filename);
            $this->students = json_decode($content, true) ?? [];
        }
    }

    private function save_data() {
        file_put_contents($this->filename, json_encode($this->students, JSON_PRETTY_PRINT));
    }

    private function handle_action() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
            switch ($action) {
                case 'add':
                    $this->add_student();
                    break;
                case 'update':
                    $this->update_student();
                    break;
                case 'delete':
                    $this->delete_student();
                    break;
                case 'export':
                    $this->export_data();
                    break;
            }
            
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
    }

    private function add_student() {
        $student_id = $_POST['student_id'] ?? '';
        $name = $_POST['name'] ?? '';
        $grades = $_POST['grades'] ?? '';

        if (empty($student_id) || empty($name) || empty($grades)) {
            $_SESSION['message'] = 'All fields are required!';
            $_SESSION['message_type'] = 'error';
            return;
        }

        if (isset($this->students[$student_id])) {
            $_SESSION['message'] = "Student ID $student_id already exists!";
            $_SESSION['message_type'] = 'error';
            return;
        }

        $grade_array = array_map('floatval', explode(',', $grades));
        if (empty($grade_array)) {
            $_SESSION['message'] = 'Please enter valid grades!';
            $_SESSION['message_type'] = 'error';
            return;
        }

        $average = array_sum($grade_array) / count($grade_array);
        $current_time = date('Y-m-d H:i:s');

        $this->students[$student_id] = [
            'name' => $name,
            'grades' => $grade_array,
            'average' => round($average, 2),
            'added_date' => $current_time,
            'last_updated' => $current_time
        ];

        $this->save_data();
        $_SESSION['message'] = "Student $name added successfully!";
        $_SESSION['message_type'] = 'success';
    }

    private function update_student() {
        $student_id = $_POST['student_id'] ?? '';
        $name = $_POST['name'] ?? '';
        $grades = $_POST['grades'] ?? '';

        if (empty($student_id) || !isset($this->students[$student_id])) {
            $_SESSION['message'] = 'Student not found!';
            $_SESSION['message_type'] = 'error';
            return;
        }

        if (!empty($name)) {
            $this->students[$student_id]['name'] = $name;
        }

        if (!empty($grades)) {
            $grade_array = array_map('floatval', explode(',', $grades));
            if (!empty($grade_array)) {
                $this->students[$student_id]['grades'] = $grade_array;
                $this->students[$student_id]['average'] = round(array_sum($grade_array) / count($grade_array), 2);
            }
        }

        $this->students[$student_id]['last_updated'] = date('Y-m-d H:i:s');
        $this->save_data();
        $_SESSION['message'] = "Student record updated successfully!";
        $_SESSION['message_type'] = 'success';
    }

    private function delete_student() {
        $student_id = $_POST['student_id'] ?? '';
        
        if (isset($this->students[$student_id])) {
            unset($this->students[$student_id]);
            $this->save_data();
            $_SESSION['message'] = "Student deleted successfully!";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Student not found!';
            $_SESSION['message_type'] = 'error';
        }
    }

    private function export_data() {
        if (empty($this->students)) {
            $_SESSION['message'] = 'No data to export!';
            $_SESSION['message_type'] = 'error';
            return;
        }

        $filename = 'student_report_' . date('Y-m-d') . '.txt';
        $content = "STUDENT GRADE REPORT\n";
        $content .= "=" . str_repeat("=", 60) . "\n";
        $content .= "Generated on: " . date('Y-m-d H:i:s') . "\n";
        $content .= "Total Students: " . count($this->students) . "\n";
        $content .= "=" . str_repeat("=", 60) . "\n\n";

        foreach ($this->students as $id => $data) {
            $content .= "Student ID: $id\n";
            $content .= "Name: {$data['name']}\n";
            $content .= "Grades: " . implode(', ', array_map(fn($g) => number_format($g, 1), $data['grades'])) . "\n";
            $content .= "Average: " . number_format($data['average'], 2) . "\n";
            $content .= "Added: {$data['added_date']}\n";
            $content .= "Last Updated: {$data['last_updated']}\n";
            $content .= str_repeat("-", 40) . "\n";
        }

        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $content;
        exit();
    }

    private function calculate_statistics() {
        if (empty($this->students)) {
            return null;
        }

        $averages = array_column($this->students, 'average');
        $total = count($averages);
        $class_avg = array_sum($averages) / $total;
        $max_avg = max($averages);
        $min_avg = min($averages);

        $categories = [
            'A (90-100)' => 0,
            'B (80-89)' => 0,
            'C (70-79)' => 0,
            'D (60-69)' => 0,
            'F (0-59)' => 0
        ];

        foreach ($averages as $avg) {
            if ($avg >= 90) $categories['A (90-100)']++;
            elseif ($avg >= 80) $categories['B (80-89)']++;
            elseif ($avg >= 70) $categories['C (70-79)']++;
            elseif ($avg >= 60) $categories['D (60-69)']++;
            else $categories['F (0-59)']++;
        }

        return [
            'total' => $total,
            'class_avg' => round($class_avg, 2),
            'max_avg' => round($max_avg, 2),
            'min_avg' => round($min_avg, 2),
            'categories' => $categories
        ];
    }

    public function display() {
        $message = $_SESSION['message'] ?? '';
        $message_type = $_SESSION['message_type'] ?? '';
        unset($_SESSION['message'], $_SESSION['message_type']);

        $current_view = $_GET['view'] ?? 'dashboard';
        $search_term = $_GET['search'] ?? '';
        $stats = $this->calculate_statistics();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Student Grade Management System</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }

                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                }

                .container {
                    max-width: 1200px;
                    margin: 0 auto;
                    padding: 20px;
                }

                header {
                    background: white;
                    border-radius: 10px;
                    padding: 20px;
                    margin-bottom: 20px;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                }

                header h1 {
                    color: #2c3e50;
                    text-align: center;
                    margin-bottom: 20px;
                    font-size: 2.5rem;
                }

                nav {
                    display: flex;
                    justify-content: center;
                    gap: 15px;
                    flex-wrap: wrap;
                }

                nav a {
                    text-decoration: none;
                    color: #3498db;
                    padding: 10px 20px;
                    border-radius: 5px;
                    transition: all 0.3s ease;
                    font-weight: 500;
                }

                nav a:hover {
                    background: #3498db;
                    color: white;
                    transform: translateY(-2px);
                }

                nav a.active {
                    background: #2c3e50;
                    color: white;
                }

                main {
                    background: white;
                    border-radius: 10px;
                    padding: 30px;
                    margin-bottom: 20px;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                }

                .message {
                    padding: 15px;
                    margin-bottom: 20px;
                    border-radius: 5px;
                    text-align: center;
                    font-weight: 500;
                }

                .message.success {
                    background: #d4edda;
                    color: #155724;
                    border: 1px solid #c3e6cb;
                }

                .message.error {
                    background: #f8d7da;
                    color: #721c24;
                    border: 1px solid #f5c6cb;
                }

                .dashboard h2, .students-list h2, .form-container h2, .search-container h2, .statistics h2 {
                    color: #2c3e50;
                    margin-bottom: 20px;
                    padding-bottom: 10px;
                    border-bottom: 2px solid #eee;
                }

                .stats-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 20px;
                    margin-bottom: 30px;
                }

                .stat-card {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 20px;
                    border-radius: 10px;
                    text-align: center;
                    transition: transform 0.3s ease;
                }

                .stat-card:hover {
                    transform: translateY(-5px);
                }

                .stat-card h3 {
                    font-size: 1rem;
                    margin-bottom: 10px;
                    opacity: 0.9;
                }

                .stat-number {
                    font-size: 2rem;
                    font-weight: bold;
                }

                .recent-students, .results-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                    gap: 20px;
                }

                .student-card {
                    background: #f8f9fa;
                    padding: 20px;
                    border-radius: 8px;
                    border: 1px solid #dee2e6;
                }

                .student-card h4 {
                    color: #2c3e50;
                    margin-bottom: 10px;
                }

                .student-card p {
                    margin: 5px 0;
                    color: #666;
                }

                .form-container, .search-container {
                    max-width: 600px;
                    margin: 0 auto;
                }

                .form-group {
                    margin-bottom: 20px;
                }

                .form-group label {
                    display: block;
                    margin-bottom: 5px;
                    color: #2c3e50;
                    font-weight: 500;
                }

                .form-group input {
                    width: 100%;
                    padding: 10px;
                    border: 1px solid #ddd;
                    border-radius: 5px;
                    font-size: 1rem;
                }

                .form-group input:focus {
                    outline: none;
                    border-color: #3498db;
                    box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
                }

                .form-group small {
                    display: block;
                    color: #666;
                    margin-top: 5px;
                    font-size: 0.875rem;
                }

                .search-form {
                    display: flex;
                    gap: 10px;
                    margin-bottom: 30px;
                }

                .search-form input {
                    flex: 1;
                    padding: 10px;
                    border: 1px solid #ddd;
                    border-radius: 5px;
                    font-size: 1rem;
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                }

                table th, table td {
                    padding: 12px;
                    text-align: left;
                    border-bottom: 1px solid #ddd;
                }

                table th {
                    background: #f8f9fa;
                    color: #2c3e50;
                    font-weight: 600;
                }

                table tr:hover {
                    background: #f8f9fa;
                }

                .btn {
                    display: inline-block;
                    padding: 10px 20px;
                    background: #3498db;
                    color: white;
                    border: none;
                    border-radius: 5px;
                    cursor: pointer;
                    font-size: 1rem;
                    font-weight: 500;
                    text-decoration: none;
                    transition: all 0.3s ease;
                }

                .btn:hover {
                    background: #2980b9;
                    transform: translateY(-2px);
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                }

                .btn.secondary {
                    background: #95a5a6;
                }

                .btn.secondary:hover {
                    background: #7f8c8d;
                }

                .btn.danger {
                    background: #e74c3c;
                }

                .btn.danger:hover {
                    background: #c0392b;
                }

                .btn.small {
                    padding: 5px 10px;
                    font-size: 0.875rem;
                }

                .btn.export {
                    background: #27ae60;
                }

                .btn.export:hover {
                    background: #229954;
                }

                .actions {
                    display: flex;
                    gap: 5px;
                }

                .inline-form {
                    display: inline;
                }

                .stat-cards {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                    gap: 30px;
                }

                .stat-card.large {
                    background: white;
                    border: 1px solid #dee2e6;
                    color: #333;
                }

                .stat-card.large h3 {
                    color: #2c3e50;
                    margin-bottom: 20px;
                    padding-bottom: 10px;
                    border-bottom: 1px solid #eee;
                }

                .stat-item {
                    display: flex;
                    justify-content: space-between;
                    padding: 10px 0;
                    border-bottom: 1px solid #f8f9fa;
                }

                .stat-item:last-child {
                    border-bottom: none;
                }

                .stat-item span:first-child {
                    font-weight: 500;
                    color: #2c3e50;
                }

                footer {
                    text-align: center;
                    color: white;
                    padding: 20px;
                }

                footer p {
                    margin-top: 10px;
                    opacity: 0.8;
                }

                .export-form {
                    display: inline-block;
                }

                @media (max-width: 768px) {
                    nav {
                        flex-direction: column;
                        align-items: center;
                    }
                    
                    nav a {
                        width: 100%;
                        text-align: center;
                    }
                    
                    .stats-grid {
                        grid-template-columns: 1fr;
                    }
                    
                    .recent-students, .results-grid {
                        grid-template-columns: 1fr;
                    }
                    
                    table {
                        display: block;
                        overflow-x: auto;
                    }
                    
                    .search-form {
                        flex-direction: column;
                    }
                    
                    .stat-cards {
                        grid-template-columns: 1fr;
                    }
                }

                @media (max-width: 480px) {
                    .container {
                        padding: 10px;
                    }
                    
                    header h1 {
                        font-size: 1.8rem;
                    }
                    
                    main {
                        padding: 15px;
                    }
                    
                    .btn {
                        padding: 8px 16px;
                        font-size: 0.9rem;
                    }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <header>
                    <h1>Student Grade Management System</h1>
                    <nav>
                        <a href="?view=dashboard" class="<?= $current_view == 'dashboard' ? 'active' : '' ?>">Dashboard</a>
                        <a href="?view=all" class="<?= $current_view == 'all' ? 'active' : '' ?>">All Students</a>
                        <a href="?view=add" class="<?= $current_view == 'add' ? 'active' : '' ?>">Add Student</a>
                        <a href="?view=search" class="<?= $current_view == 'search' ? 'active' : '' ?>">Search</a>
                        <a href="?view=stats" class="<?= $current_view == 'stats' ? 'active' : '' ?>">Statistics</a>
                    </nav>
                </header>

                <?php if ($message): ?>
                    <div class="message <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <main>
                    <?php
                    switch ($current_view) {
                        case 'dashboard':
                            $this->display_dashboard($stats);
                            break;
                        case 'all':
                            $this->display_all_students();
                            break;
                        case 'add':
                            $this->display_add_form();
                            break;
                        case 'edit':
                            $this->display_edit_form($_GET['id'] ?? '');
                            break;
                        case 'search':
                            $this->display_search($search_term);
                            break;
                        case 'stats':
                            $this->display_statistics($stats);
                            break;
                        default:
                            $this->display_dashboard($stats);
                    }
                    ?>
                </main>

                <footer>
                    <form method="post" class="export-form">
                        <input type="hidden" name="action" value="export">
                        <button type="submit" class="btn export">Export Data</button>
                    </form>
                    <p>&copy; <?= date('Y') ?> Student Grade Management System</p>
                </footer>
            </div>
        </body>
        </html>
        <?php
    }

    private function display_dashboard($stats) {
        ?>
        <div class="dashboard">
            <h2>Dashboard</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Students</h3>
                    <p class="stat-number"><?= $stats ? $stats['total'] : 0 ?></p>
                </div>
                <div class="stat-card">
                    <h3>Class Average</h3>
                    <p class="stat-number"><?= $stats ? $stats['class_avg'] : '0.00' ?></p>
                </div>
                <div class="stat-card">
                    <h3>Highest Average</h3>
                    <p class="stat-number"><?= $stats ? $stats['max_avg'] : '0.00' ?></p>
                </div>
                <div class="stat-card">
                    <h3>Lowest Average</h3>
                    <p class="stat-number"><?= $stats ? $stats['min_avg'] : '0.00' ?></p>
                </div>
            </div>

            <h3>Recent Students</h3>
            <?php if (empty($this->students)): ?>
                <p>No students found. <a href="?view=add">Add your first student</a></p>
            <?php else: ?>
                <div class="recent-students">
                    <?php 
                    $recent = array_slice($this->students, -5, 5, true);
                    foreach ($recent as $id => $student): ?>
                        <div class="student-card">
                            <h4><?= htmlspecialchars($student['name']) ?></h4>
                            <p>ID: <?= htmlspecialchars($id) ?></p>
                            <p>Average: <?= number_format($student['average'], 2) ?></p>
                            <div class="actions">
                                <a href="?view=edit&id=<?= urlencode($id) ?>" class="btn small">Edit</a>
                                <form method="post" class="inline-form">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="student_id" value="<?= htmlspecialchars($id) ?>">
                                    <button type="submit" class="btn small danger" onclick="return confirm('Delete this student?')">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function display_all_students() {
        ?>
        <div class="students-list">
            <h2>All Students</h2>
            <?php if (empty($this->students)): ?>
                <p>No students found.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Grades</th>
                            <th>Average</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->students as $id => $student): ?>
                            <tr>
                                <td><?= htmlspecialchars($id) ?></td>
                                <td><?= htmlspecialchars($student['name']) ?></td>
                                <td><?= implode(', ', array_map(fn($g) => number_format($g, 1), $student['grades'])) ?></td>
                                <td><?= number_format($student['average'], 2) ?></td>
                                <td><?= htmlspecialchars($student['last_updated']) ?></td>
                                <td class="actions">
                                    <a href="?view=edit&id=<?= urlencode($id) ?>" class="btn small">Edit</a>
                                    <form method="post" class="inline-form">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="student_id" value="<?= htmlspecialchars($id) ?>">
                                        <button type="submit" class="btn small danger" onclick="return confirm('Delete this student?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    private function display_add_form() {
        ?>
        <div class="form-container">
            <h2>Add New Student</h2>
            <form method="post">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="student_id">Student ID:</label>
                    <input type="text" id="student_id" name="student_id" required>
                </div>
                <div class="form-group">
                    <label for="name">Student Name:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="grades">Grades (comma-separated):</label>
                    <input type="text" id="grades" name="grades" placeholder="e.g., 85,90,78" required>
                </div>
                <button type="submit" class="btn">Add Student</button>
                <a href="?view=dashboard" class="btn secondary">Cancel</a>
            </form>
        </div>
        <?php
    }

    private function display_edit_form($student_id) {
        if (!isset($this->students[$student_id])) {
            echo '<p>Student not found.</p>';
            return;
        }

        $student = $this->students[$student_id];
        ?>
        <div class="form-container">
            <h2>Edit Student</h2>
            <form method="post">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="student_id" value="<?= htmlspecialchars($student_id) ?>">
                <div class="form-group">
                    <label for="student_id">Student ID:</label>
                    <input type="text" value="<?= htmlspecialchars($student_id) ?>" disabled>
                    <small>ID cannot be changed</small>
                </div>
                <div class="form-group">
                    <label for="name">Student Name:</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($student['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="grades">Grades (comma-separated):</label>
                    <input type="text" id="grades" name="grades" value="<?= implode(',', $student['grades']) ?>" required>
                </div>
                <button type="submit" class="btn">Update Student</button>
                <a href="?view=all" class="btn secondary">Cancel</a>
            </form>
        </div>
        <?php
    }

    private function display_search($search_term) {
        ?>
        <div class="search-container">
            <h2>Search Students</h2>
            <form method="get" class="search-form">
                <input type="hidden" name="view" value="search">
                <input type="text" name="search" value="<?= htmlspecialchars($search_term) ?>" placeholder="Search by ID or name...">
                <button type="submit" class="btn">Search</button>
            </form>

            <?php
            if ($search_term) {
                $results = [];
                $search_lower = strtolower($search_term);
                
                foreach ($this->students as $id => $student) {
                    if (strpos(strtolower($id), $search_lower) !== false || 
                        strpos(strtolower($student['name']), $search_lower) !== false) {
                        $results[$id] = $student;
                    }
                }

                if (empty($results)) {
                    echo '<p>No students found matching "' . htmlspecialchars($search_term) . '"</p>';
                } else {
                    echo '<h3>Results (' . count($results) . ' found):</h3>';
                    echo '<div class="results-grid">';
                    foreach ($results as $id => $student) {
                        ?>
                        <div class="student-card">
                            <h4><?= htmlspecialchars($student['name']) ?></h4>
                            <p>ID: <?= htmlspecialchars($id) ?></p>
                            <p>Grades: <?= implode(', ', array_map(fn($g) => number_format($g, 1), $student['grades'])) ?></p>
                            <p>Average: <?= number_format($student['average'], 2) ?></p>
                            <div class="actions">
                                <a href="?view=edit&id=<?= urlencode($id) ?>" class="btn small">Edit</a>
                                <form method="post" class="inline-form">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="student_id" value="<?= htmlspecialchars($id) ?>">
                                    <button type="submit" class="btn small danger" onclick="return confirm('Delete this student?')">Delete</button>
                                </form>
                            </div>
                        </div>
                        <?php
                    }
                    echo '</div>';
                }
            }
            ?>
        </div>
        <?php
    }

    private function display_statistics($stats) {
        ?>
        <div class="statistics">
            <h2>Class Statistics</h2>
            <?php if (!$stats): ?>
                <p>No data available.</p>
            <?php else: ?>
                <div class="stat-cards">
                    <div class="stat-card large">
                        <h3>Overall Statistics</h3>
                        <div class="stat-item">
                            <span>Total Students:</span>
                            <span><?= $stats['total'] ?></span>
                        </div>
                        <div class="stat-item">
                            <span>Class Average:</span>
                            <span><?= $stats['class_avg'] ?></span>
                        </div>
                        <div class="stat-item">
                            <span>Highest Average:</span>
                            <span><?= $stats['max_avg'] ?></span>
                        </div>
                        <div class="stat-item">
                            <span>Lowest Average:</span>
                            <span><?= $stats['min_avg'] ?></span>
                        </div>
                    </div>

                    <div class="stat-card large">
                        <h3>Grade Distribution</h3>
                        <?php foreach ($stats['categories'] as $category => $count): 
                            $percentage = $stats['total'] > 0 ? round(($count / $stats['total']) * 100, 1) : 0;
                            ?>
                            <div class="stat-item">
                                <span><?= $category ?>:</span>
                                <span><?= $count ?> students (<?= $percentage ?>%)</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}

if (!file_exists('grades.json')) {
    $sample_data = [
        'S001' => [
            'name' => 'John Doe',
            'grades' => [85, 90, 78],
            'average' => 84.33,
            'added_date' => date('Y-m-d 10:30:00'),
            'last_updated' => date('Y-m-d 10:30:00')
        ],
        'S002' => [
            'name' => 'Jane Smith',
            'grades' => [92, 88, 95],
            'average' => 91.67,
            'added_date' => date('Y-m-d 11:00:00'),
            'last_updated' => date('Y-m-d 11:00:00')
        ]
    ];
    file_put_contents('grades.json', json_encode($sample_data, JSON_PRETTY_PRINT));
}

$system = new StudentGradeSystem();
$system->display();
?>