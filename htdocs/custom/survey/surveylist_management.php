<?php

global $db, $langs, $user;
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once 'class/Survey.class.php';

// Check if user has permission to create surveys
if (!$user->hasRight('survey',  'surveymanagement')) {
	// If the user does not have permission, deny access and redirect or show an error
	accessforbidden(); // This function will block access and show an error
}

$langs->load("admin");
$langs->load("survey@survey");

$page_name = "Created Surveys";
llxHeader('', $langs->trans($page_name));

// Fetch surveys
$sql = "SELECT rowid, title, description, date_start, date_end, status FROM llx_survey";
$resql = $db->query($sql);

?>

<h1><?php echo $langs->trans($page_name); ?></h1>


<table class="noborder" width="100%" style="table-layout: fixed;"> <!-- Added table-layout: fixed -->
	<thead>
	<tr class="liste_titre">
		<th style="width: 5%;"><?php echo $langs->trans("ID"); ?></th>
		<th style="width: 15%;"><?php echo $langs->trans("Title"); ?></th>
		<th style="width: 30%;"><?php echo $langs->trans("Description"); ?></th>
		<th style="width: 10%;"><?php echo $langs->trans("Status"); ?></th>
		<th style="width: 10%;"><?php echo $langs->trans("Start Date"); ?></th>
		<th style="width: 10%;"><?php echo $langs->trans("End Date"); ?></th>
		<th style="width: 10%;"><?php echo $langs->trans("Update"); ?></th>
		<th style="width: 10%;"><?php echo $langs->trans("Delete"); ?></th>
	</tr>
	</thead>
	<tbody>
	<?php
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			echo "<tr class='oddeven'>";
			echo "<td style='padding: 5px; font-size: 0.9em;'>$obj->rowid</td>";
			echo "<td style='padding: 5px; font-size: 0.9em;'>$obj->title</td>";
			echo "<td style='padding: 5px; font-size: 0.9em; word-wrap: break-word; overflow: hidden; text-overflow: ellipsis;'>$obj->description</td>";
			echo "<td style='padding: 5px; font-size: 0.9em;'>" . ($obj->status == 1 ? "Enabled" : "Disabled") . "</td>";
			echo "<td style='padding: 5px; font-size: 0.9em;'>" . dol_print_date($db->jdate($obj->date_start), 'day') . "</td>";
			echo "<td style='padding: 5px; font-size: 0.9em;'>" . dol_print_date($db->jdate($obj->date_end), 'day') . "</td>";
			echo "<td style='padding: 5px; font-size: 0.9em;'><a class='butAction' href='surveydetail_management.php?id=$obj->rowid'>".$langs->trans("Update")."</a></td>";
			echo "<td style='padding: 5px; font-size: 0.9em;'><a class='butActionDelete' href='deletesurvey_management.php?id=$obj->rowid'>".$langs->trans("Delete")."</a></td>";
			echo "</tr>";
		}
	} else {
		echo "<tr><td colspan='8' class='center'>".$langs->trans("NoSurveysFound")."</td></tr>";
	}
	?>
	</tbody>
</table>

<?php
llxFooter();
$db->close();
?>
