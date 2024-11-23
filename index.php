<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

/*Ce code n'est pas nécessairement conçu pour se protéger contre tous types d'attaques. Il a été réalisé dans le cadre d'un projet personnel. Je vous conseille donc de sécuriser soigneusement tous vos inputs et d'utiliser des méthodes sécurisées pour la connexion à la base de données (par exemple, des variables d'environnement pour les informations sensibles, des requêtes préparées, etc.).*/

$dsn = 'mysql:host=votrehost;dbname=votredbname;charset=utf8mb4';
$username = 'votreusername';
$password = 'votrepassword';

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_task'])) {
    $title = $_POST['title'];
    $text = $_POST['text'];
    $alarm_time = $_POST['alarm_time'] ?? null;

    $imagePath = null;  
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $imageName = $_FILES['image']['name'];
        $imageTmpName = $_FILES['image']['tmp_name'];
        $imageType = mime_content_type($imageTmpName);
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

        if (in_array($imageType, $allowedTypes)) {
            $imagePath = 'uploads/' . basename($imageName);

            if (move_uploaded_file($imageTmpName, $imagePath)) {
            } else {
                echo 'Erreur lors du téléchargement de l\'image.';
            }
        } else {
            echo 'Le fichier n\'est pas une image valide.';
        }
    }

    $stmt = $pdo->prepare("INSERT INTO tasks (title, text, alarm_time, image_path, status) VALUES (?, ?, ?, ?, 'en cours')");
    $stmt->execute([$title, $text, $alarm_time, $imagePath]);
}

if (isset($_GET['complete_id'])) {
    $completeId = $_GET['complete_id'];
    $stmt = $pdo->prepare("UPDATE tasks SET status = 'terminée' WHERE id = ?");
    $stmt->execute([$completeId]);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_GET['reopen_id'])) {
    $reopenId = $_GET['reopen_id'];
    $stmt = $pdo->prepare("UPDATE tasks SET status = 'en cours' WHERE id = ?");
    $stmt->execute([$reopenId]);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_GET['delete_id'])) {
    $deleteId = $_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->execute([$deleteId]);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$tasksInProgress = $pdo->query("SELECT * FROM tasks WHERE status = 'en cours' ORDER BY alarm_time ASC")->fetchAll(PDO::FETCH_ASSOC);
$tasksCompleted = $pdo->query("SELECT * FROM tasks WHERE status = 'terminée' ORDER BY alarm_time ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks</title>
    <link rel="icon" type="image/png" href="favicon.png" sizes="32x32">
    <link rel="apple-touch-icon" href="favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" rel="stylesheet">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sour+Gummy:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
<style>
    body {
        font-family: 'Arial', sans-serif;
        margin: 0;
        padding: 0;
        background: linear-gradient(135deg, #FFDD94, #F46B45);
        color: #333;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    nav {
        font-size: 20px;
        padding: 10px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: white;
        margin-bottom: 20px; 
        font-family: "Sour Gummy", serif;
  font-optical-sizing: auto;
  font-weight: <weight>;
  font-style: normal;
  font-variation-settings:
    "wdth" 100;
    }

    nav a {
        text-decoration: none;
        color: white;
        font-size: 1.2em;
        padding: 5px 15px;
        border-radius: 5px;
        transition: background 0.3s ease;
        font-family: "Sour Gummy", serif;
  font-optical-sizing: auto;
  font-weight: <weight>;
  font-style: normal;
  font-variation-settings:
    "wdth" 100;
    }

    nav a.active,
    nav a:hover {
        background: #ffffff;
        color: #ff7f50;
    }

    main {
        display: flex;
        flex: 1;
        overflow-y: auto;
        gap: 20px;
        padding: 20px;
        box-sizing: border-box;
        margin-top: 20px; 
    }
    a {
    text-decoration: none;
    font-family: "Sour Gummy", serif;
  font-optical-sizing: auto;
  font-weight: <weight>;
  font-style: normal;
  font-variation-settings:
    "wdth" 100;
    color: red;
}

    .left-panel {
        width: 30%;
        min-width: 300px;
        background: #ffffff;
        box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        border-radius: 10px;
        padding: 20px;
        box-sizing: border-box;
        margin-bottom: 20px; 
    }

    .left-panel h2 {
        margin-bottom: 15px;
    }

    .left-panel form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .left-panel input,
    .left-panel textarea,
    .left-panel button,
    .left-panel input[type="file"] {
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 1em;
        width: 100%;
        box-sizing: border-box;
    }

    .left-panel button {
        background: #ff7f50;
        color: white;
        cursor: pointer;
        transition: background 0.3s ease;
    }

    .left-panel button:hover {
        background: #ff5733;
    }

    .right-panel {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 20px;
        overflow-y: auto;
        padding: 10px 0;
    }

    .tasks-section {
        background: #fff;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .task {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        border: 1px solid #ccc;
        border-radius: 10px;
        margin-bottom: 15px;
        background: #f9f9f9;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .task:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 12px rgba(0, 0, 0, 0.15);
    }

    .task h3 {
        margin: 0;
        font-weight: bold;
    }

    .task img {
        max-width: 50px;
        max-height: 50px;
        border-radius: 5px;
        margin-right: 15px;
    }

    .task-actions a {
        text-decoration: none;
        color: #4CAF50;
        margin-right: 10px;
        display: inline-flex;
        align-items: center;
        font-size: 1.2em;
    }

    .task-actions .ri-delete-bin-line {
        color: red;
    }

    .task-actions button {
        background: none;
        border: none;
        cursor: pointer;
        font-size: 1.2em;
        color: #555;
        transition: color 0.3s ease;
    }

    .task-actions button:hover {
        color: #ff7f50;
    }

    @media (max-width: 768px) {
        main {
            flex-direction: column;
            gap: 20px;
        }

        .left-panel {
            width: 100%;
            box-shadow: none;
            padding: 15px;
            margin-bottom: 20px; 
        }

        .right-panel {
            width: 100%;
            padding: 0;
        }

        .tasks-section {
            padding: 15px;
        }

        .task {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }

        .task img {
            margin-bottom: 10px;
        }

        .task-actions {
            display: flex;
            gap: 10px;
        }

        .task-actions button {
            font-size: 1.5em; 
        }

       
        .tasks-section .task-filters {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .tasks-section .task-filters button {
            font-size: 1.2em;
            padding: 10px 15px;
            background: #ff7f50;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
            font-family: "Sour Gummy", serif;
  font-optical-sizing: auto;
  font-weight: <weight>;
  font-style: normal;
  font-variation-settings:
    "wdth" 100;
        }

        .tasks-section .task-filters button:hover {
            background: #ff5733;
        }


    }
</style>





</head>
<body>



<main>
    <div class="left-panel">
        <h2>Créer une tâche</h2>
        <form method="POST" enctype="multipart/form-data" id="task-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <label for="title">Titre :</label>
            <input type="text" id="title" name="title" maxlength="255" required>

            <label for="text">Description :</label>
            <textarea id="text" name="text" maxlength="1000" required></textarea>

            <label for="alarm_time">Date et heure de l'alarme :</label>
            <input type="datetime-local" id="alarm_time" name="alarm_time" min="<?= date('Y-m-d\TH:i') ?>">

            <label for="image">Ajouter une image :</label>
            <input type="file" id="image" name="image" accept="image/png, image/jpeg, image/jpg">

            <button type="submit" name="add_task">Ajouter la tâche</button>
        </form>
    </div>

    <div class="right-panel">
        <nav>
            <div>
                <a href="#tasks-in-progress" class="active" onclick="showSection('tasks-in-progress')">En cours</a>
                <a href="#tasks-completed" onclick="showSection('tasks-completed')">Terminées</a>
            </div>
        </nav>

        <div id="tasks-in-progress" class="tasks-section">
            <h2>Tâches en cours</h2>
            <?php foreach ($tasksInProgress as $task): ?>
                <div class="task">
                    <?php if (!empty($task['image_path'])): ?>
                        <img src="<?= htmlspecialchars($task['image_path']) ?>" alt="Image de la tâche">
                    <?php endif; ?>
                    <div>
                        <h3><?= htmlspecialchars($task['title']) ?></h3>
                        <p><?= nl2br(htmlspecialchars($task['text'])) ?></p>
                        <p>
                            <?php if (!empty($task['alarm_time']) && $task['alarm_time'] !== '0000-00-00 00:00:00'): ?>
                                <span class="alarm-time" data-time="<?= htmlspecialchars($task['alarm_time']) ?>">Alarme : <?= htmlspecialchars($task['alarm_time']) ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="task-actions">
                        <a href="?complete_id=<?= htmlspecialchars($task['id']) ?>"><i class="ri-checkbox-circle-line" style="font-size: 34px;"></i></a>
                        <a href="?delete_id=<?= htmlspecialchars($task['id']) ?>"><i class="ri-delete-bin-line" style="font-size: 34px;"></i></a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div id="tasks-completed" class="tasks-section" style="display: none;">
            <h2>Tâches terminées</h2>
            <?php foreach ($tasksCompleted as $task): ?>
                <div class="task">
                    <?php if (!empty($task['image_path'])): ?>
                        <img src="<?= htmlspecialchars($task['image_path']) ?>" alt="Image de la tâche">
                    <?php endif; ?>
                    <div>
                        <h3><?= htmlspecialchars($task['title']) ?></h3>
                        <p><?= nl2br(htmlspecialchars($task['text'])) ?></p>
                        <p>
                            <?php if (!empty($task['alarm_time']) && $task['alarm_time'] !== '0000-00-00 00:00:00'): ?>
                                Alarme : <?= htmlspecialchars($task['alarm_time']) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="task-actions">
                        <a href="?reopen_id=<?= htmlspecialchars($task['id']) ?>"><i class="ri-refresh-line" style="font-size: 34px;"></i></a>
                        <a href="?delete_id=<?= htmlspecialchars($task['id']) ?>"><i class="ri-delete-bin-line" style="font-size: 34px;"></i></a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<script>
    function showSection(sectionId) {
        document.querySelectorAll('.tasks-section').forEach(section => {
            section.style.display = 'none';
        });
        document.getElementById(sectionId).style.display = 'block';

        document.querySelectorAll('nav a').forEach(link => link.classList.remove('active'));
        document.querySelector(`nav a[href="#${sectionId}"]`).classList.add('active');
    }

    document.addEventListener('DOMContentLoaded', () => showSection('tasks-in-progress'));

    function checkAlarms() {
        const now = new Date();
        document.querySelectorAll('.alarm-time').forEach(alarm => {
            const alarmTime = new Date(alarm.dataset.time);
            if (alarmTime <= now && !alarm.dataset.notified) {
                if (Notification.permission === 'granted') {
                    new Notification('Alarme atteinte !', {
                        body: `Tâche : ${alarm.closest('.task').querySelector('h3').textContent}`,
                        icon: '/path/to/icon.png'
                    });
                }
                const sound = new Audio('/path/to/sound.mp3');
                sound.play();
                alarm.dataset.notified = 'true';
            }
        });
    }
    setInterval(checkAlarms, 30000);
</script>
</body>
</html>