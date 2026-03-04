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
    // on peut supprimer même si la course est terminée ou non
    $sql = "DELETE FROM courses WHERE course_id = :course_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':course_id' => $course_id]);
    $_SESSION['success'] = 'Course supprimée avec succès.';
} catch (PDOException $e) {
    error_log('Erreur suppression course : ' . $e->getMessage());
    $_SESSION['erreurs'] = ['Impossible de supprimer la course.'];
}

header('Location: index.php');
exit;
?>