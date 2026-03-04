<?php
session_start();
require_once 'config.php';

$course_id = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);
if (!$course_id) {
    $_SESSION['erreurs'] = ['ID de course invalide.'];
    header('Location: index.php');
    exit;
}

try {
    // récupération de la course
    $sql = "SELECT * FROM courses WHERE course_id = :course_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':course_id' => $course_id]);
    $course = $stmt->fetch();
    if (!$course) {
        throw new Exception('Course introuvable.');
    }
    if (strtolower(trim($course['statut'])) !== 'en cours') {
        throw new Exception('Seules les courses en cours peuvent être modifiées.');
    }
} catch (Exception $e) {
    $_SESSION['erreurs'] = [$e->getMessage()];
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $depart = trim($_POST['depart'] ?? '');
    $arrivee = trim($_POST['arrivee'] ?? '');
    $date_heure = $_POST['date_heure'] ?? '';
    $erreurs = [];
    if (empty($depart)) {
        $erreurs[] = 'Le point de départ est obligatoire.';
    }
    if (empty($arrivee)) {
        $erreurs[] = "Le point d'arrivée est obligatoire.";
    }
    if (empty($date_heure)) {
        $erreurs[] = "La date et l'heure sont obligatoires.";
    } else {
        $date = DateTime::createFromFormat('Y-m-d\TH:i', $date_heure);
        if (!$date) {
            $erreurs[] = 'Le format de la date est invalide.';
        }
    }
    if (empty($erreurs)) {
        try {
            $update = "UPDATE courses SET point_depart = :depart, point_arrivee = :arrivee, date_heure = :date_heure WHERE course_id = :course_id";
            $stmt2 = $pdo->prepare($update);
            $stmt2->execute([
                ':depart' => $depart,
                ':arrivee' => $arrivee,
                ':date_heure' => $date_heure,
                ':course_id' => $course_id
            ]);
            $_SESSION['success'] = 'Course mise à jour avec succès.';
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            error_log('Erreur mise à jour course : ' . $e->getMessage());
            $erreurs[] = 'Une erreur est survenue lors de la mise à jour.';
        }
    }
    if (!empty($erreurs)) {
        $_SESSION['erreurs'] = $erreurs;
        header('Location: modifier_course.php?course_id=' . $course_id);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier une course - RAPIDO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-5">
    <h3>Modifier la course #<?= e($course_id) ?></h3>
    <form method="POST" class="row g-3">
        <div class="col-md-4">
            <label for="depart" class="form-label">Point de départ</label>
            <input type="text" name="depart" id="depart" class="form-control" required value="<?= e($course['point_depart']) ?>">
        </div>
        <div class="col-md-4">
            <label for="arrivee" class="form-label">Point d'arrivée</label>
            <input type="text" name="arrivee" id="arrivee" class="form-control" required value="<?= e($course['point_arrivee']) ?>">
        </div>
        <div class="col-md-4">
            <label for="date_heure" class="form-label">Date et heure</label>
            <input type="datetime-local" name="date_heure" id="date_heure" class="form-control" required value="<?= e(date('Y-m-d\TH:i', strtotime($course['date_heure']))) ?>">
        </div>
        <div class="col-12">
            <button class="btn btn-primary" type="submit">Enregistrer les modifications</button>
            <a href="index.php" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>
</body>
</html>
