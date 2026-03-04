<?php
session_start();
require_once 'config.php';

// Vérification que le formulaire a été soumis en POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et validation des données
    $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
    $chauffeur_id = filter_input(INPUT_POST, 'chauffeur_id', FILTER_VALIDATE_INT);
    
    $erreurs = [];
    
    if (!$course_id) {
        $erreurs[] = "ID de course invalide.";
    }
    
    if (!$chauffeur_id) {
        $erreurs[] = "Veuillez sélectionner un chauffeur valide.";
    }
    
    if (!empty($erreurs)) {
        $_SESSION['erreurs'] = $erreurs;
        header('Location: index.php');
        exit;
    }
    
    try {
        // Début de la transaction
        $pdo->beginTransaction();
        
        // Vérifier que la course existe et n'a pas déjà de chauffeur
        $checkSql = "SELECT chauffeur_id, statut FROM courses WHERE course_id = :course_id FOR UPDATE";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([':course_id' => $course_id]);
        $course = $checkStmt->fetch();
        
        if (!$course) {
            throw new Exception("Course introuvable.");
        }
        
        // Considérer la course comme affectée si chauffeur_id n'est pas nul et non zéro
        if ($course['chauffeur_id'] !== null && $course['chauffeur_id'] != 0) {
            throw new Exception("Cette course a déjà un chauffeur affecté.");
        }
        
        // Vérifier le statut (en tenant compte de 'en cours' ou 'en attente')
        $statut = strtolower(trim($course['statut'] ?? ''));
        if ($statut === 'terminée' || $statut === 'terminee') {
            throw new Exception("Cette course est déjà terminée.");
        }
        
        // Vérifier que le chauffeur existe (sans vérifier la disponibilité pour l'instant)
        $checkChauffeurSql = "SELECT disponible FROM chauffeurs WHERE chauffeur_id = :chauffeur_id FOR UPDATE";
        $checkChauffeurStmt = $pdo->prepare($checkChauffeurSql);
        $checkChauffeurStmt->execute([':chauffeur_id' => $chauffeur_id]);
        $chauffeur = $checkChauffeurStmt->fetch();
        
        if (!$chauffeur) {
            throw new Exception("Chauffeur introuvable.");
        }
        
        // Vérifier la disponibilité
        if ((int)$chauffeur['disponible'] !== 1) {
            throw new Exception("Ce chauffeur n'est pas disponible actuellement.");
        }
        
        // Mise à jour de la course avec le chauffeur
        $updateSql = "UPDATE courses SET chauffeur_id = :chauffeur_id WHERE course_id = :course_id";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([
            ':chauffeur_id' => $chauffeur_id,
            ':course_id' => $course_id
        ]);
        
        // Marquer le chauffeur comme non disponible
        $updateChauffeurSql = "UPDATE chauffeurs SET disponible = 0 WHERE chauffeur_id = :chauffeur_id";
        $updateChauffeurStmt = $pdo->prepare($updateChauffeurSql);
        $updateChauffeurStmt->execute([':chauffeur_id' => $chauffeur_id]);
        
        // Validation de la transaction
        $pdo->commit();
        
        $_SESSION['success'] = "Le chauffeur a été affecté avec succès à la course !";
        
    } catch (Exception $e) {
        // Annulation de la transaction en cas d'erreur
        $pdo->rollBack();
        
        error_log("Erreur d'affectation : " . $e->getMessage());
        
        $_SESSION['erreurs'] = [$e->getMessage()];
    }
    
    header('Location: index.php');
    exit;
}

// Si ce n'est pas une requête POST, c'est qu'on veut afficher le formulaire
// Récupération de l'ID de course depuis GET
$course_id = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);
$course = null;
$chauffeurs = []; // Changé de $chauffeursDisponibles à $chauffeurs
$chauffeursDisponibles = []; // Pour garder la compatibilité avec le template

if (!$course_id) {
    $_SESSION['erreurs'] = ["ID de course invalide."];
    header('Location: index.php');
    exit;
}

try {
    // Récupération des détails de la course
    $queryCourse = "SELECT * FROM courses WHERE course_id = :course_id";
    $stmtCourse = $pdo->prepare($queryCourse);
    $stmtCourse->execute([':course_id' => $course_id]);
    $course = $stmtCourse->fetch();
    
    if (!$course) {
        $_SESSION['erreurs'] = ["Course introuvable."];
        header('Location: index.php');
        exit;
    }
    
    // Vérifier le statut
    $statut = strtolower(trim($course['statut'] ?? ''));
    if ($statut !== 'en cours' && $statut !== 'en attente') {
        $_SESSION['erreurs'] = ["Cette course n'est pas disponible pour affectation (statut: " . $course['statut'] . ")."];
        header('Location: index.php');
        exit;
    }
    
    // Vérifier si un chauffeur est déjà affecté
    if (!empty($course['chauffeur_id'])) {
        $_SESSION['erreurs'] = ["Cette course a déjà un chauffeur affecté."];
        header('Location: index.php');
        exit;
    }
    
    // Récupération de TOUS les chauffeurs avec leur statut de disponibilité
    $queryChauffeurs = "SELECT chauffeur_id, nom, prenoms, telephone, disponible 
                        FROM chauffeurs 
                        ORDER BY disponible DESC, nom, prenoms";
    $stmtChauffeurs = $pdo->query($queryChauffeurs);
    $chauffeurs = $stmtChauffeurs->fetchAll();
    
    // Séparer les chauffeurs disponibles pour la liste déroulante
    $chauffeursDisponibles = array_filter($chauffeurs, function($ch) {
        return $ch['disponible'] == 1;
    });
    
} catch (PDOException $e) {
    error_log("Erreur : " . $e->getMessage());
    $_SESSION['erreurs'] = ["Erreur lors de la récupération des données."];
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Affecter un chauffeur - RAPIDO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .chauffeur-option {
            padding: 8px 12px;
            border-bottom: 1px solid #eee;
        }
        .chauffeur-option:last-child {
            border-bottom: none;
        }
        .chauffeur-disponible {
            color: #198754;
            font-weight: bold;
        }
        .chauffeur-indisponible {
            color: #dc3545;
            font-style: italic;
        }
        .badge-disponible {
            background-color: #198754;
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
        }
        .badge-indisponible {
            background-color: #dc3545;
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-user-plus"></i> Affecter un chauffeur à la course</h4>
                    </div>
                    <div class="card-body">
                        <!-- Informations de la course -->
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle"></i> Détails de la course :</h5>
                            <p class="mb-1"><strong>Départ :</strong> <?= htmlspecialchars($course['point_depart']) ?></p>
                            <p class="mb-1"><strong>Arrivée :</strong> <?= htmlspecialchars($course['point_arrivee']) ?></p>
                            <p class="mb-0"><strong>Date/Heure :</strong> <?= date('d/m/Y H:i', strtotime($course['date_heure'])) ?></p>
                        </div>
                        
                        <!-- Formulaire d'affectation -->
                        <form action="affecter_chauffeur.php" method="POST">
                            <input type="hidden" name="course_id" value="<?= $course['course_id'] ?>">
                            
                            <div class="mb-3">
                                <label for="chauffeur_id" class="form-label">
                                    <i class="fas fa-users"></i> Choisir un chauffeur disponible :
                                </label>
                                
                                <?php if (empty($chauffeursDisponibles)): ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i> 
                                        <strong>Aucun chauffeur disponible pour le moment.</strong>
                                        <p class="mb-0 mt-2 small">
                                            <a href="liste_chauffeurs.php" class="alert-link">Voir tous les chauffeurs</a>
                                        </p>
                                    </div>
                                    
                                    <!-- Afficher quand même la liste complète des chauffeurs (lecture seule) -->
                                    <?php if (!empty($chauffeurs)): ?>
                                        <div class="mt-3">
                                            <p><strong>Tous les chauffeurs enregistrés :</strong></p>
                                            <div class="border rounded p-2 bg-light">
                                                <?php foreach ($chauffeurs as $ch): ?>
                                                    <div class="chauffeur-option">
                                                        <i class="fas fa-user"></i>
                                                        <?= htmlspecialchars($ch['nom'] . ' ' . $ch['prenoms']) ?> 
                                                        (Tél: <?= htmlspecialchars($ch['telephone']) ?>)
                                                        <?php if ($ch['disponible'] == 1): ?>
                                                            <span class="badge-disponible ms-2">Disponible</span>
                                                        <?php else: ?>
                                                            <span class="badge-indisponible ms-2">Indisponible</span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                <?php else: ?>
                                    <select class="form-select" id="chauffeur_id" name="chauffeur_id" required>
                                        <option value="">-- Sélectionnez un chauffeur disponible --</option>
                                        <?php foreach ($chauffeursDisponibles as $chauffeur): ?>
                                            <option value="<?= $chauffeur['chauffeur_id'] ?>">
                                                <?= htmlspecialchars($chauffeur['nom'] . ' ' . $chauffeur['prenoms']) ?> 
                                                (Tél: <?= htmlspecialchars($chauffeur['telephone']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    
                                    <!-- Petit rappel du nombre de chauffeurs disponibles -->
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i> 
                                        <?= count($chauffeursDisponibles) ?> chauffeur(s) disponible(s) sur 
                                        <?= count($chauffeurs) ?> au total.
                                    </small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Retour à la liste
                                </a>
                                <?php if (!empty($chauffeursDisponibles)): ?>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-check"></i> Confirmer l'affectation
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>
                        
                        <!-- Informations supplémentaires -->
                        <div class="mt-4 small text-muted border-top pt-3">
                            <p class="mb-1">
                                <i class="fas fa-lightbulb"></i> 
                                <strong>Info :</strong> Seuls les chauffeurs marqués comme "Disponible" peuvent être affectés.
                            </p>
                            <p class="mb-0">
                                Pour modifier la disponibilité d'un chauffeur, contactez l'administrateur.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>