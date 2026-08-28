<?php
require_once __DIR__ . '/../config/database.php';

$hash = password_hash('password', PASSWORD_DEFAULT);

$db = Database::getConnection();
$db->prepare("UPDATE users SET password = :hash WHERE is_active = 1")
   ->execute([':hash' => $hash]);

echo "✅ Tous les mots de passe ont été mis à jour.<br>";
echo "Hash utilisé : " . $hash . "<br>";
echo "<br><strong>Supprime ce fichier après utilisation !</strong>";
?>