<?php
$mois = (int)date('n');

if ($vrai_donnee && $alert_temp && $data['temp'] > 37) {
    [$ico, $titre, $texte, $type] = ['🔥', 'Surchauffe détectée !',
        "Température à {$data['temp']} °C. Vérifiez la ventilation et apportez de l'ombre immédiatement.", 'danger'];

} elseif ($vrai_donnee && $alert_temp && $data['temp'] < 30) {
    [$ico, $titre, $texte, $type] = ['🥶', 'Risque d\'hypothermie',
        "Température à {$data['temp']} °C. Vérifiez les réserves et envisagez une isolation.", 'warning'];

} elseif ($vrai_donnee && $alert_hum) {
    [$ico, $titre, $texte, $type] = ['💦', 'Humidité trop élevée',
        "{$data['hum']} % d'humidité. Ouvrez l'aération du plancher pour prévenir les moisissures.", 'warning'];

} else {
    $type = 'info';
    if ($mois >= 3 && $mois <= 5) {
        $tips = [
            ['🌸', 'Printemps : vérifiez l\'espace', 'La colonie grossit vite ! Ajoutez une hausse si les cadres sont couverts à 80 %.'],
            ['👑', 'Printemps : cherchez la reine', 'Profitez des beaux jours pour vérifier la ponte. Un couvain compact et homogène est excellent signe.'],
            ['🌼', 'Printemps : nourrissement', 'Si les provisions sont légères, nourrissez au sirop léger (1:1) pour stimuler la ponte.'],
        ];
    } elseif ($mois >= 6 && $mois <= 8) {
        $tips = [
            ['☀️', 'Été : surveillez la miellée', 'Pesez la ruche chaque matin — une hausse de 1–2 kg/jour signale une grande miellée.'],
            ['🍯', 'Été : récolte imminente ?', 'Récoltez quand les cadres sont operculés à plus de 75 %. Vérifiez l\'humidité du miel (< 18 %).'],
            ['💦', 'Été : point d\'eau', 'Par forte chaleur les abeilles consomment beaucoup d\'eau. Maintenez un abreuvoir proche du rucher.'],
        ];
    } elseif ($mois >= 9 && $mois <= 10) {
        $tips = [
            ['🍂', 'Automne : traitement varroa', 'Agissez avant l\'arrêt de ponte. Acide oxalique ou lanières selon votre méthode.'],
            ['📦', 'Automne : réserves hivernales', '15–20 kg de miel minimum pour passer l\'hiver. Nourrissez au sirop épais (2:1) si insuffisant.'],
        ];
    } else {
        $tips = [
            ['❄️', 'Hiver : ne dérangez pas', 'Évitez d\'ouvrir sous 10 °C. Vérifiez juste que le trou de vol n\'est pas obstrué par des abeilles mortes.'],
            ['📋', 'Hiver : planifiez la saison', 'Bon moment pour entretenir le matériel, commander les hausses et lire des ouvrages apicoles.'],
        ];
    }
    [$ico, $titre, $texte] = $tips[(int)date('j') % count($tips)];
}
?>
<div class="conseil-card conseil-<?= $type ?> anim-fade delay-5">
    <div class="conseil-ico"><?= $ico ?></div>
    <div class="conseil-body">
        <div class="conseil-titre"><?= $titre ?></div>
        <div class="conseil-texte"><?= $texte ?></div>
    </div>
    <span class="conseil-badge">Conseil du jour</span>
</div>
