<?php

global $langs, $db, $user;
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once 'class/Survey.class.php';

$langs->load("admin");
$langs->load("survey@survey");

//if (!$user->rights->survey->write) accessforbidden();

$id = POST('id', 'int');
$status = POST('status', 'int');
$date_start = POST('date_start', 'date');
$date_end = POST('date_end', 'date');

$survey = new Survey($db);
$survey->fetch($id);

if ($status !== null) {
	$survey->status = $status;
}

if ($date_start !== null) {
	$survey->date_start = $db->idate($date_start);
}

if ($date_end !== null) {
	$survey->date_end = $db->idate($date_end);
}

$survey->update($user);

header("Location: surveydetail.php?id=" . $id);
exit;
?>
