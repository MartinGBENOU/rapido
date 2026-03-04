<?php
session_start(); // Démarrer la session pour les messages
require_once 'config.php';

$chauffeursDisponibles = [];
$courses = [];
$db_error = false;

try {
    // Récupération de tous les chauffeurs disponibles
    $queryChauffeurs = "SELECT chauffeur_id, nom, prenoms FROM chauffeurs WHERE disponible = 1 ORDER BY nom, prenoms";
    $stmtChauffeurs = $pdo->query($queryChauffeurs);
    $chauffeursDisponibles = $stmtChauffeurs->fetchAll();
    
    // Récupération de toutes les courses avec les infos chauffeur
    $queryCourses = "
        SELECT 
            c.*,
            ch.nom,
            ch.prenoms,
            ch.telephone
        FROM courses c
        LEFT JOIN chauffeurs ch ON c.chauffeur_id = ch.chauffeur_id
        ORDER BY c.date_heure DESC
    ";
    $stmtCourses = $pdo->query($queryCourses);
    $courses = $stmtCourses->fetchAll();
    
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des données : " . $e->getMessage());
    $db_error = true;
    $courses = [];
    $chauffeursDisponibles = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RAPIDO - Gestion des courses</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Notre fichier CSS personnalisé -->
    <link rel="stylesheet" href="style.css">
    <style>
        /* Style pour le badge "en attente" */
        .badge.bg-warning {
            background-color: #ffc107 !important;
            color: #212529 !important;
        }
        .badge.bg-info {
            background-color: #0dcaf0 !important;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <!-- En-tête -->
        <div class="row mb-4">
            <div class="col">
                <h1 class="display-5 text-primary">
                    <i class="fas fa-taxi"></i> RAPIDO - Gestion des courses
                </h1>
                <p class="lead">Application de gestion des courses interurbaines</p>
            </div>
        </div>

        <!-- Messages de succès/erreur -->
        <?php if ($db_error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i> <strong>Erreur :</strong>
                Impossible de charger les données. Veuillez réessayer plus tard.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success']) && !$db_error): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['erreurs']) && !empty($_SESSION['erreurs']) && !$db_error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <strong>Erreur(s) :</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($_SESSION['erreurs'] as $erreur): ?>
                        <li><?= htmlspecialchars($erreur) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['erreurs']); ?>
        <?php endif; ?>

        <!-- Formulaire d'ajout de course -->
        <div class="row mb-4">
            <div class="col-md-8 mx-auto">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-plus-circle"></i> Ajouter une nouvelle course</h5>
                    </div>
                    <div class="card-body">
                        <form action="ajouter_course.php" method="POST" class="row g-3">
                            <div class="col-md-5">
                                <label for="depart" class="form-label">Point de départ</label>
                                <input type="text" class="form-control" id="depart" name="depart" required 
                                       placeholder="Ex: Calavi">
                            </div>
                            <div class="col-md-5">
                                <label for="arrivee" class="form-label">Point d'arrivée</label>
                                <input type="text" class="form-control" id="arrivee" name="arrivee" required 
                                       placeholder="Ex: Cotonou">
                            </div>
                            <div class="col-md-2">
                                <label for="date_heure" class="form-label">Date et heure</label>
                                <input type="datetime-local" class="form-control" id="date_heure" name="date_heure" required>
                            </div>
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Enregistrer la course
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des courses -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Liste des courses</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($courses)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Aucune course enregistrée pour le moment.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Départ</th>
                                            <th>Arrivée</th>
                                            <th>Date/Heure</th>
                                            <th>Chauffeur</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($courses as $course): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($course['course_id']) ?></td>
                                            <td><?= htmlspecialchars($course['point_depart']) ?></td>
                                            <td><?= htmlspecialchars($course['point_arrivee']) ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($course['date_heure'])) ?></td>
                                            <td>
                                                <?php if (!empty($course['chauffeur_id'])): ?>
                                                    <span class="badge bg-info">
                                                        <i class="fas fa-user"></i>
                                                        <?= htmlspecialchars($course['nom'] . ' ' . $course['prenoms']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-user-slash"></i> Non affecté
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                // Normaliser le statut (enlever les espaces et mettre en minuscules)
                                                $statut = strtolower(trim($course['statut'] ?? ''));
                                                
                                                if ($statut === 'en cours' || $statut === 'en attente'):
                                                ?>
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="fas fa-clock"></i> 
                                                        <?= ucfirst($statut) ?>
                                                    </span>
                                                <?php elseif ($statut === 'terminée' || $statut === 'terminee'): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check"></i> Terminée
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">
                                                        <?= htmlspecialchars($statut) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                // Normaliser le statut
                                                $statut = strtolower(trim($course['statut'] ?? ''));
                                                $hasChauffeur = !empty($course['chauffeur_id']);
                                                
                                                // Une course est "active" si elle est en cours OU en attente
                                                $isActive = ($statut === 'en cours' || $statut === 'en attente');
                                                
                                                if ($isActive):
                                                ?>
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="fas fa-cog"></i> Actions
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <?php if (!$hasChauffeur): ?>
                                                                <li>
                                                                    <a class="dropdown-item" href="affecter_chauffeur.php?course_id=<?= $course['course_id'] ?>">
                                                                        <i class="fas fa-user-plus"></i> Affecter un chauffeur
                                                                    </a>
                                                                </li>
                                                            <?php else: ?>
                                                                <li>
                                                                    <a class="dropdown-item" href="terminer_course.php?course_id=<?= $course['course_id'] ?>" 
                                                                       onclick="return confirm('Confirmez-vous la fin de cette course ?');">
                                                                        <i class="fas fa-check-circle"></i> Terminer la course
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>
                                                        </ul>
                                                    </div>
                                                <?php else: ?>
                                                    <!-- course terminée : permettre uniquement la suppression -->
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-sm btn-danger dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="fas fa-trash"></i> Actions
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li>
                                                                <a class="dropdown-item text-danger" href="supprimer_course.php?course_id=<?= $course['course_id'] ?>" onclick="return confirm('Supprimer définitivement cette course ?');">
                                                                    <i class="fas fa-trash"></i> Supprimer
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Légende des statuts -->
                            <div class="mt-3 d-flex justify-content-end gap-3">
                                <small>
                                    <span class="badge bg-warning text-dark">⬤</span> En cours / En attente
                                </small>
                                <small>
                                    <span class="badge bg-success">⬤</span> Terminée
                                </small>
                                <small>
                                    <span class="badge bg-secondary">⬤</span> Non affecté
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS et Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script pour les confirmations -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-fermeture des alertes après 5 secondes
            setTimeout(function() {
                document.querySelectorAll('.alert').forEach(function(alert) {
                    let bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
    </script>
</body>
</html>