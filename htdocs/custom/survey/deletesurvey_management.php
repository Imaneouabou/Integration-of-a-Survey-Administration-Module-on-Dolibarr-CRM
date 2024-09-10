<?php

global $langs, $user, $db;
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once 'class/Survey.class.php';

$langs->load("admin");
$langs->load("survey@survey");

$id = GETPOST('id', 'int');

if (!$id) {
	header('Location: surveylist_management.php');
	exit;
}

$survey = new Survey($db);
$survey->fetch($id);

if ($survey->delete($user)) {
	setEventMessages($langs->trans("SurveyDeleted"), null, 'mesgs');
} else {
	setEventMessages($langs->trans("ErrorDeletingSurvey"), null, 'errors');
}

header('Location: surveylist_management.php');
exit;

?>
