<?php
global $langs, $db, $user;
require '../../main.inc.php';
$langs->load("survey@survey");

$page_name = "Thank You";
llxHeader('', $langs->trans($page_name));
?>

<div class="thank-you-container">
	<h1><?php echo $langs->trans("Formulaire sans titre"); ?></h1>
	<p><?php echo $langs->trans("Votre réponse a bien été enregistrée."); ?></p>
	<a href="index.php"><?php echo $langs->trans("Envoyer une autre réponse"); ?></a>
</div>

<?php
llxFooter();
?>

<style>
	.thank-you-container {
		text-align: center;
		margin-top: 50px;
		padding: 20px;
		border: 1px solid #ddd;
		border-radius: 10px;
		background-color: #fff;
		box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
		max-width: 600px;
		margin: 40px auto;
	}
	.thank-you-container h1 {
		font-size: 24px;
		margin-bottom: 10px;
	}
	.thank-you-container p {
		font-size: 18px;
		margin-bottom: 20px;
	}
	.thank-you-container a {
		display: inline-block;
		padding: 10px 20px;
		background-color: #007bff;
		color: #fff;
		text-decoration: none;
		border-radius: 5px;
	}
	.thank-you-container a:hover {
		background-color: #0056b3;
	}
</style>
