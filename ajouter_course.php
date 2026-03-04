<?php
/**
 * Script de traitement pour l'ajout d'une nouvelle course
 * Le statut est mis à 'en attente' par défaut
 */

require_once 'config.php';

// Démarrer la session pour les messages
session_start();

// Vérification que le formulaire a été soumis en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Récupération et nettoyage des données
$depart = trim($_POST['depart'] ?? '');
$arrivee = trim($_POST['arrivee'] ?? '');
$date_heure = $_POST['date_heure'] ?? '';

// Validation des données
$erreurs = [];

if (empty($depart)) {
    $erreurs[] = "Le point de départ est obligatoire.";
}

if (empty($arrivee)) {
    $erreurs[] = "Le point d'arrivée est obligatoire.";
}

if (empty($date_heure)) {
    $erreurs[] = "La date et l'heure sont obligatoires.";
} else {
    // Vérification que la date est valide
    $date = DateTime::createFromFormat('Y-m-d\TH:i', $date_heure);
    if (!$date) {
        $erreurs[] = "Le format de la date est invalide.";
    }
}

// S'il y a des erreurs, on redirige avec un message
if (!empty($erreurs)) {
    $_SESSION['erreurs'] = $erreurs;
    header('Location: index.php');
    exit;
}

try {
    // Insertion de la course en base de données avec le statut 'en attente'
    $sql = "INSERT INTO courses (point_depart, point_arrivee, date_heure, statut) 
            VALUES (:depart, :arrivee, :date_heure, 'en attente')";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':depart' => $depart,
        ':arrivee' => $arrivee,
        ':date_heure' => $date_heure
    ]);
    
    // Message de succès
    $_SESSION['success'] = "La course a été ajoutée avec succès ! (Statut: En attente)";
    
} catch (PDOException $e) {
    // En cas d'erreur, on logge l'erreur
    error_log("Erreur d'insertion course : " . $e->getMessage());
    
    $_SESSION['erreurs'] = ["Une erreur est survenue lors de l'ajout de la course."];
}

header('Location: index.php');
exit;
?>