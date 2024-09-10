<?php
global $langs, $db, $user;
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once 'class/Survey.class.php';

// Check if user has permission to create surveys
if (!$user->hasRight('survey',  'surveyresult')) {
	// If the user does not have permission, deny access and redirect or show an error
	accessforbidden(); // This function will block access and show an error
}

$langs->load("survey@survey");

$page_name = "SurveyResults";
llxHeader('', $langs->trans($page_name));

$survey = new Survey($db);

$sql = "SELECT rowid, title, description FROM llx_survey";
$resql = $db->query($sql);

if ($resql) {
	echo '<h1>'.$langs->trans("Survey Results").'</h1>';
	echo '<table class="noborder" width="100%">';
	echo '<tr class="liste_titre">';
	echo '<th>'.$langs->trans("#").'</th>';
	echo '<th>'.$langs->trans("Title").'</th>';
	echo '<th>'.$langs->trans("Description").'</th>';
	echo '<th>'.$langs->trans("Actions").'</th>';
	echo '</tr>';

	while ($obj = $db->fetch_object($resql)) {
		echo '<tr>';
		echo '<td>'.$obj->rowid.'</td>';
		echo '<td>'.$obj->title.'</td>';
		echo '<td>'.$obj->description.'</td>';
		echo '<td><a href="survey_results_table.php?id='.$obj->rowid.'" class="button">'.$langs->trans("Show Result").'</a></td>';
		echo '</tr>';
	}

	echo '</table>';
} else {
	dol_print_error($db);
}

llxFooter();
$db->close();
?>

<link rel="stylesheet" type="text/css" href="css/survey_results_list.css?v=<?php echo time(); ?>">
