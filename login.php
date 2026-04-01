<?php
session_start();
$pdo = new PDO('mysql:host=localhost;dbname=ruche_connectee;charset=utf8', 'root', 'Maya2026!');
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $mdp = $_POST['mdp'];
    
    if (isset($_POST['inscription'])) {
        $hash = password_hash($mdp, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, mot_de_passe) VALUES (?, ?)");
            $stmt->execute([$nom, $hash]);
            $msg = "✅ Compte créé ! Connecte-toi maintenant.";
        } catch (Exception $e) { $msg = "❌ Ce nom est déjà pris."; }
    } elseif (isset($_POST['connexion'])) {
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE nom = ?");
        $stmt->execute([$nom]);
        $user = $stmt->fetch();
        if ($user && password_verify($mdp, $user['mot_de_passe'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nom'] = $user['nom'];
            header("Location: index.php"); exit;
        } else { $msg = "❌ Identifiants incorrects."; }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Connexion - Apis Dashboard</title>
    <style>
        body { font-family: sans-serif; background-color: #fdfbf7; text-align: center; margin-top: 100px; }
        .box { background: white; border: 2px solid #F59E0B; padding: 40px; border-radius: 15px; width: 300px; margin: auto; box-shadow: 0 10px 20px rgba(245, 158, 11, 0.1); }
        input { width: 90%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 5px; }
        button { width: 100%; padding: 10px; margin-top: 10px; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; }
        .btn-log { background: #F59E0B; color: white; }
        .btn-ins { background: #fff; color: #F59E0B; border: 1px solid #F59E0B; }
    </style>
</head>
<body>
    <div class="box">
        <h1 style="color:#F59E0B; margin:0;">🍯 Apis</h1>
        <p>Gérez vos ruches connectées</p>
        <p style="color:red;"><?php echo $msg; ?></p>
        <form method="POST">
            <input type="text" name="nom" placeholder="Pseudo Apiculteur" required>
            <input type="password" name="mdp" placeholder="Mot de passe" required>
            <button type="submit" name="connexion" class="btn-log">Se Connecter</button>
            <button type="submit" name="inscription" class="btn-ins">Créer un compte</button>
        </form>
    </div>
</body>
</html>
