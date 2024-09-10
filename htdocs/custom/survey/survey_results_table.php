<?php
global $langs, $db, $user;
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once 'class/Survey.class.php';
require_once 'class/Question.class.php';
require_once 'class/Response.class.php';

$langs->load("survey@survey");

$survey_id = GETPOST('id', 'int'); // Get the survey ID from the URL parameter

$page_name = "SurveyResultsDetails";
llxHeader('', $langs->trans($page_name));

if ($survey_id) {
	// Step 1: Fetch all questions for the survey
	$sql_questions = "SELECT rowid, question FROM llx_survey_question WHERE fk_survey = ".intval($survey_id)." ORDER BY position";
	$resql_questions = $db->query($sql_questions);

	if ($resql_questions && $db->num_rows($resql_questions) > 0) {
		// Fetch questions into an array
		$questions = [];
		while ($obj = $db->fetch_object($resql_questions)) {
			$questions[$obj->rowid] = $obj->question;
		}

		// Step 2: Fetch all user responses for the survey, grouped by user and submission
		$sql_responses = "SELECT r.fk_user, r.response, r.fk_survey_question, u.firstname, u.lastname, r.date_creation
                          FROM llx_survey_response r
                          JOIN llx_user u ON r.fk_user = u.rowid
                          JOIN llx_survey_question q ON r.fk_survey_question = q.rowid
                          WHERE q.fk_survey = ".intval($survey_id)."
                          ORDER BY r.fk_user, r.date_creation, q.position";
		$resql_responses = $db->query($sql_responses);

		if ($resql_responses && $db->num_rows($resql_responses) > 0) {
			echo '<h1>'.$langs->trans("Survey Details").'</h1>';
			echo '<table class="noborder" width="100%">';
			echo '<tr class="liste_titre">';
			echo '<th>'.$langs->trans("User ID").'</th>';
			echo '<th>'.$langs->trans("First Name").'</th>';
			echo '<th>'.$langs->trans("Last Name").'</th>';

			// Display each question as a table header
			foreach ($questions as $question) {
				echo '<th>'.$question.'</th>';
			}
			echo '</tr>';

			$current_user_id = null;
			$current_submission_time = null;
			$current_row = [];

			// Step 3: Process and display each response, creating a new row for each submission
			while ($response_data = $db->fetch_object($resql_responses)) {
				// Check if we need to start a new row for a new submission (based on user and submission time)
				if ($current_user_id !== $response_data->fk_user || $current_submission_time !== $response_data->date_creation) {
					if ($current_user_id !== null) {
						// Print the previous submission's row
						echo '<tr>';
						echo '<td>'.$current_user_id.'</td>';
						echo '<td>'.$current_row['firstname'].'</td>';
						echo '<td>'.$current_row['lastname'].'</td>';
						foreach ($questions as $question_id => $question_text) {
							echo '<td>'.(isset($current_row['responses'][$question_id]) ? implode(', ', $current_row['responses'][$question_id]) : '-').'</td>';
						}
						echo '</tr>';
					}

					// Start a new row for the current submission
					$current_user_id = $response_data->fk_user;
					$current_submission_time = $response_data->date_creation;
					$current_row = [
						'firstname' => $response_data->firstname,
						'lastname' => $response_data->lastname,
						'responses' => []
					];
				}

				// Store the response for the current question
				if (!isset($current_row['responses'][$response_data->fk_survey_question])) {
					$current_row['responses'][$response_data->fk_survey_question] = [];
				}
				$current_row['responses'][$response_data->fk_survey_question][] = $response_data->response;
			}

			// Print the last submission's row
			if ($current_user_id !== null) {
				echo '<tr>';
				echo '<td>'.$current_user_id.'</td>';
				echo '<td>'.$current_row['firstname'].'</td>';
				echo '<td>'.$current_row['lastname'].'</td>';
				foreach ($questions as $question_id => $question_text) {
					echo '<td>'.(isset($current_row['responses'][$question_id]) ? implode(', ', $current_row['responses'][$question_id]) : '-').'</td>';
				}
				echo '</tr>';
			}

			echo '</table>';

			// Step 4: Add the button to view results graphically
			echo '<div style="text-align: center; margin-top: 20px;">';
			echo '<a href="survey_results_detail.php?id='.$survey_id.'" class="button">'.$langs->trans("Show Graphically").'</a>';
			echo '</div>';
		} else {
			echo '<p>'.$langs->trans("NoResponsesFound").'</p>';
		}
	} else {
		echo '<p>'.$langs->trans("NoQuestionsFound").'</p>';
	}
} else {
	echo '<p>'.$langs->trans("NoSurveySelected").'</p>';
}

llxFooter();
$db->close();
?>
