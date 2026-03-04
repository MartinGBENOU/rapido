<?php

require_once 'config.php';

// Récupération et validation de l'ID de la course
$course_id = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);

if (!$course_id) {
    session_start();
    $_SESSION['erreurs'] = ["ID de course invalide."];
    header('Location: index.php');
    exit;
}

try {
    // Début de la transaction
    $pdo->beginTransaction();
    
    // Récupérer les informations de la course
    $selectSql = "SELECT chauffeur_id, statut FROM courses WHERE course_id = :course_id";
    $selectStmt = $pdo->prepare($selectSql);
    $selectStmt->execute([':course_id' => $course_id]);
    $course = $selectStmt->fetch();
    
    if (!$course) {
        throw new Exception("Course introuvable.");
    }
    
    if ($course['statut'] === 'terminée') {
        throw new Exception("Cette course est déjà terminée.");
    }
    
    // Mise à jour du statut de la course
    $updateSql = "UPDATE courses SET statut = 'terminée' WHERE course_id = :course_id";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([':course_id' => $course_id]);
    
    // Si un chauffeur était affecté, le rendre à nouveau disponible
    if ($course['chauffeur_id']) {
        $updateChauffeurSql = "UPDATE chauffeurs SET disponible = 1 WHERE chauffeur_id = :chauffeur_id";
        $updateChauffeurStmt = $pdo->prepare($updateChauffeurSql);
        $updateChauffeurStmt->execute([':chauffeur_id' => $course['chauffeur_id']]);
    }
    
    // Validation de la transaction
    $pdo->commit();
    
    session_start();
    $_SESSION['success'] = "La course a été marquée comme terminée avec succès !";
    
} catch (Exception $e) {
    // Annulation de la transaction
    $pdo->rollBack();
    
    error_log("Erreur lors de la terminaison de course : " . $e->getMessage());
    
    session_start();
    $_SESSION['erreurs'] = [$e->getMessage()];
}

header('Location: index.php');
exit;
?>