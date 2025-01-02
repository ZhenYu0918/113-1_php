<?php
// 資料庫連線配置
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'workout_manager';

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("資料庫連線失敗：" . $conn->connect_error);
}

// 處理新增和刪除的表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_data'])) {
        // 新增功能
        $username = $_POST['username'];
        $email = $_POST['email'];
        $age = $_POST['age'];
        $gender = $_POST['gender'];
        $workout_name = $_POST['workout_name'];
        $category = $_POST['category'];
        $description = $_POST['description'];
        $date = $_POST['date'];
        $duration = $_POST['duration'];
        $calories_burned = $_POST['calories_burned'];
        $notes = $_POST['notes'];

        // 新增使用者
        $sql_user = "INSERT INTO users (username, email, age, gender) VALUES (?, ?, ?, ?)";
        $stmt_user = $conn->prepare($sql_user);
        $stmt_user->bind_param("ssis", $username, $email, $age, $gender);
        $stmt_user->execute();
        $user_id = $stmt_user->insert_id;
        $stmt_user->close();

        // 新增運動項目
        $sql_workout = "INSERT INTO workouts (workout_name, category, description) VALUES (?, ?, ?)";
        $stmt_workout = $conn->prepare($sql_workout);
        $stmt_workout->bind_param("sss", $workout_name, $category, $description);
        $stmt_workout->execute();
        $workout_id = $stmt_workout->insert_id;
        $stmt_workout->close();

        // 新增運動日誌
        $sql_log = "INSERT INTO logs (user_id, workout_id, date, duration, calories_burned, notes) 
                    VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_log = $conn->prepare($sql_log);
        $stmt_log->bind_param("iisiss", $user_id, $workout_id, $date, $duration, $calories_burned, $notes);
        $stmt_log->execute();
        $stmt_log->close();

        echo "資料新增成功！";
    } elseif (isset($_POST['delete'])) {
        // 刪除功能
        $table = $_POST['table'];
        $id = $_POST['id'];

        // 根據資料表名稱選擇主鍵欄位
        if ($table === 'logs') {
            $primaryKey = 'log_id';
        } elseif ($table === 'users') {
            $primaryKey = 'user_id';
        } elseif ($table === 'workouts') {
            $primaryKey = 'workout_id';
        } else {
            die("無效的資料表名稱！");
        }

        // 刪除資料
        $sql_delete = "DELETE FROM $table WHERE $primaryKey = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $id);
        $stmt_delete->execute();

        if ($stmt_delete->affected_rows > 0) {
            echo "資料刪除成功！";
        } else {
            echo "刪除失敗，可能資料不存在或已被刪除。";
        }

        $stmt_delete->close();
    }
}

// 查詢資料
$users = $conn->query("SELECT * FROM users");
$workouts = $conn->query("SELECT * FROM workouts");
$logs = $conn->query(
    "SELECT logs.*, users.username, workouts.workout_name, workouts.description 
     FROM logs 
     JOIN users ON logs.user_id = users.user_id 
     JOIN workouts ON logs.workout_id = workouts.workout_id"
);
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>運動數據管理系統</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f4f4f4;
        }
    </style>
</head>
<body>
    <h1>運動數據管理系統</h1>

    <!-- 單一表單 -->
    <h2>新增完整數據</h2>
    <form method="POST">
        <h3>使用者資訊</h3>
        <input type="text" name="username" placeholder="使用者名稱" required>
        <input type="email" name="email" placeholder="電子郵件" required>
        <input type="number" name="age" placeholder="年齡" required>
        <select name="gender" required>
            <option value="male">男性</option>
            <option value="female">女性</option>
            <option value="other">其他</option>
        </select>

        <h3>運動項目資訊</h3>
        <input type="text" name="workout_name" placeholder="運動名稱" required>
        <select name="category" required>
            <option value="cardio">心肺功能</option>
            <option value="strength">肌肉訓練</option>
            <option value="flexibility">柔軟訓練</option>
            <option value="balance">平衡訓練</option>
        </select>
        <textarea name="description" placeholder="描述"></textarea>

        <h3>運動日誌</h3>
        <input type="date" name="date" required>
        <input type="number" name="duration" placeholder="運動時間 (分鐘)" required>
        <input type="number" name="calories_burned" placeholder="燃燒卡路里" required>
        <textarea name="notes" placeholder="備註"></textarea>

        <button type="submit" name="add_data">新增資料</button>
    </form>

    <!-- 顯示所有運動日誌 -->
    <h2>運動日誌</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>使用者</th>
            <th>運動項目</th>
            <th>描述</th>
            <th>日期</th>
            <th>運動時間</th>
            <th>燃燒卡路里</th>
            <th>備註</th>
            <th>操作</th>
        </tr>
        <?php while ($log = $logs->fetch_assoc()) { ?>
            <tr>
                <td><?= $log['log_id'] ?></td>
                <td><?= $log['username'] ?></td>
                <td><?= $log['workout_name'] ?></td>
                <td><?= $log['description'] ?></td>
                <td><?= $log['date'] ?></td>
                <td><?= $log['duration'] ?></td>
                <td><?= $log['calories_burned'] ?></td>
                <td><?= $log['notes'] ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="table" value="logs">
                        <input type="hidden" name="id" value="<?= $log['log_id'] ?>">
                        <button type="submit" name="delete">刪除</button>
                    </form>
                </td>
            </tr>
        <?php } ?>
    </table>
</body>
</html>
