<?php

$host = 'localhost';
$dbname = 'rapido'; // Remplacez par le nom de votre base
$username = 'root';
$password = '';

try {
    // Connexion à la base de données avec PDO
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Activer les exceptions
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Mode de fetch par défaut
            PDO::ATTR_EMULATE_PREPARES => false // Désactiver l'émulation des requêtes préparées
        ]
    );
} catch(PDOException $e) {
    // Message d'erreur générique (ne pas afficher les détails en production)
    die("Erreur de connexion à la base de données. Veuillez réessayer plus tard.");
}

/**
 * Fonction pour échapper les sorties HTML (prévention XSS)
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>