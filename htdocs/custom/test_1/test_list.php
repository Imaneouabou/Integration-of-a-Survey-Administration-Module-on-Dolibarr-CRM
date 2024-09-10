<?php
// survey_results.php

// Include Dolibarr environment
$res = @include("../../main.inc.php"); // From htdocs directory
if (! $res) $res = @include("../../../main.inc.php"); // From "custom" directory

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";


$title = $langs->trans("SurveyResults");

// Include Dolibarr header
llxHeader('', $title);

// Print title
print load_fiche_titre($title, '', 'title_surveys');

// Fetch survey results from the database
// Add your code to fetch survey results
// ...

// Display survey results
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("SurveyTitle").'</td>';
print '<td>'.$langs->trans("Results").'</td>';
print '</tr>';
// Loop through survey results and display them
// ...
print '</table>';

// Include Dolibarr footer
llxFooter();

$db->close();
