<?php
global $langs, $db, $user;
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once 'class/Survey.class.php';
require_once 'class/question.class.php';

// Check if user has permission to create surveys
if (!$user->hasRight('survey',  'surveyparticipation')) {
	// If the user does not have permission, deny access and redirect or show an error
	accessforbidden(); // This function will block access and show an error
}

$langs->load("survey@survey");

$page_name = "Survey Participation";
llxHeader('', $langs->trans($page_name));


// Link the CSS file
echo '<link rel="stylesheet" type="text/css" href="css/surveyparticipation.css?v='.time().'">';
echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css?v='.time().'">'; // Font Awesome for icons


// Generate CSRF token
$token = newToken();
$_SESSION['newtoken'] = $token;

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// Check CSRF token
	$formToken = $_POST['token'] ?? '';
	if (empty($formToken) || $formToken !== $_SESSION['newtoken']) {
		accessforbidden();
	}

	// Retrieve the survey ID and responses
	$survey_id = (int)$_POST['survey_id'];
	$responses = $_POST['response'] ?? [];

	// Define the upload directory for images
	$upload_dir = DOL_DOCUMENT_ROOT . '/custom/survey/img/'; // Ensure this directory exists and is writable

	// Handle file uploads
	if (isset($_FILES['response']) && isset($_FILES['response']['error']) && $_FILES['response']['error'] === UPLOAD_ERR_OK) {
		foreach ($_FILES['response']['name'] as $question_id => $fileNames) {
			foreach ($fileNames as $index => $fileName) {
				$fileTmpPath = $_FILES['response']['tmp_name'][$question_id][$index];
				$fileType = $_FILES['response']['type'][$question_id][$index];

				// Check if the question type is 'file'
				$sql = "SELECT type FROM llx_survey_question WHERE rowid = " . $db->escape($question_id);
				$resql = $db->query($sql);
				if ($resql && $db->num_rows($resql) > 0) {
					$obj = $db->fetch_object($resql);
					if ($obj->type === 'file') {
						// Define allowed file types
						$allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
						if (in_array($fileType, $allowedTypes)) {
							$destination = $upload_dir . basename($fileName);

							if (move_uploaded_file($fileTmpPath, $destination)) {
								// Store the relative URL of the image in the response
								$relativeUrl = '/custom/survey/img/' . basename($fileName);
								$responses[$question_id] = $relativeUrl;
							} else {
								echo "Error: Could not move the uploaded file.";
							}
						} else {
							echo "Error: Unsupported file type.";
						}
					}
				}
			}
		}
	}


	// Validate survey ID
	if ($survey_id > 0 && !empty($responses)) {
		foreach ($responses as $question_id => $response) {
			// Convert array responses to JSON format
			if (is_array($response)) {
				$response = json_encode($response);
			} else {
				// Escape non-array responses
				$response = $db->escape($response);
			}

			// Insert response into database
			$fk_user = $user->id; // Assuming the user is logged in
			$sql = "INSERT INTO llx_survey_response (fk_survey_question, fk_user, response, date_creation) VALUES ('$question_id', '$fk_user', '$response', NOW())";
			$resql = $db->query($sql);

			if (!$resql) {
				echo "Error: " . $db->lasterror();
			}
		}


		unset($_SESSION['newtoken']);


		$userRowId =$fk_user;
		$surveyRowId = $survey_id;

        // Prepare success URL
		$successUrl = "http://localhost/dolibarr/htdocs/custom/survey/surveyparticipation.php?idmenu=3431&mainmenu=survey&leftmenu=";
		echo "<div class='message success'>You (UserId=$fk_user) have successfully submitted the survey with rowid $surveyRowId.</div>";
		echo "<script>
        document.addEventListener('DOMContentLoaded', function () {
        // Scroll to the message
        const message = document.querySelector('.message');
        if (message) {
            message.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        // Redirect to the success URL
        setTimeout(function() {
            window.location.href = '$successUrl';
        }, 1000);
    });
       </script>";
		exit;
	} else {
		echo "<div class='message error'>No responses to save or invalid survey ID.</div>";
		echo "<script>
        document.addEventListener('DOMContentLoaded', function () {
            // Scroll to the message
            const message = document.querySelector('.message');
            if (message) {
                message.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    </script>";
	}

} else {
	// Fetch the survey ID from the GET parameter
	$survey_id = isset($_GET['survey_id']) ? (int)$_GET['survey_id'] : 0;

	$form = new Form($db);
	$survey = new Survey($db);

	if ($survey_id > 0) {
		$survey->fetch($survey_id);

		// Fetch the questions for this survey
		$sql = "SELECT * FROM llx_survey_question WHERE fk_survey = " . $survey_id . " ORDER BY position ASC";
		$resql = $db->query($sql);

		$questions = [];
		if ($resql) {
			while ($obj = $db->fetch_object($resql)) {
				$questions[] = $obj;
			}
		}
	} else {
		// Fetch available surveys and check participation status
		$sql = "SELECT s.rowid, s.title, s.description,
        (SELECT COUNT(*) FROM llx_survey_response sr WHERE sr.fk_survey_question IN (SELECT rowid FROM llx_survey_question WHERE fk_survey = s.rowid) AND sr.fk_user = ".$user->id.") AS has_participated
        FROM llx_survey s
        WHERE s.status = 1 AND NOW() BETWEEN s.date_start AND s.date_end";

		$resql = $db->query($sql);
		$surveys = [];
		if ($resql) {
			while ($obj = $db->fetch_object($resql)) {
				$surveys[] = $obj;
			}
		}

	}
}
?>

<h1><?php echo $langs->trans($page_name); ?></h1>


<?php if ($survey_id > 0 && !empty($questions)): ?>
	<!-- Form-->
	<form method="post" action="">
		<input type="hidden" name="survey_id" value="<?php echo $survey_id; ?>">
		<input type="hidden" name="token" value="<?php echo $token; ?>"> <!-- CSRF token -->

		<h2><?php echo $survey->title; ?></h2>
		<p><?php echo $survey->description; ?></p>

		<?php foreach ($questions as $question): ?>
			<div class="question <?php echo $question->mandatory ? 'mandatory' : ''; ?>" id="question-<?php echo $question->rowid; ?>" data-logic='<?php echo htmlspecialchars(stripslashes($question->conditional_logic), ENT_QUOTES, 'UTF-8'); ?>'>
				<!-- Only show * in front of the question label if it's mandatory -->
				<label><?php echo $question->question; ?><?php if ($question->mandatory) echo ' <span style="color:red;">*</span>'; ?></label>

				<div class="question-field">
					<?php if ($question->type == 'text'): ?>
						<input type="text" name="response[<?php echo $question->rowid; ?>]" data-question-id="<?php echo $question->rowid; ?>"/>

					<?php elseif ($question->type == 'textarea'): ?>
						<textarea name="response[<?php echo $question->rowid; ?>]" data-question-id="<?php echo $question->rowid; ?>"></textarea>

					<?php elseif ($question->type == 'checkbox'):
						$options = json_decode($question->options, true);
						foreach ($options as $option): ?>
							<label>
								<input type="checkbox" name="response[<?php echo $question->rowid; ?>][]" value="<?php echo $option; ?>" data-question-id="<?php echo $question->rowid; ?>"/>
								<?php echo $option; ?>
							</label>
						<?php endforeach; ?>

					<?php elseif ($question->type == 'radio'):
						$options = json_decode($question->options, true);
						foreach ($options as $option): ?>
							<label>
								<input type="radio" name="response[<?php echo $question->rowid; ?>]" value="<?php echo $option; ?>" data-question-id="<?php echo $question->rowid; ?>"/>
								<?php echo $option; ?>
							</label>
						<?php endforeach; ?>

					<?php elseif ($question->type == 'select'):
						$options = json_decode($question->options, true); ?>
						<select name="response[<?php echo $question->rowid; ?>]" data-question-id="<?php echo $question->rowid; ?>">
							<option value="">--Select an option--</option>
							<?php foreach ($options as $option): ?>
								<option value="<?php echo $option; ?>"><?php echo $option; ?></option>
							<?php endforeach; ?>
						</select>
					<?php elseif ($question->type == 'date'): ?>
					<input type="date" name="response[<?php echo $question->rowid; ?>]" data-question-id="<?php echo $question->rowid; ?>"/>

					<?php elseif ($question->type == 'time'): ?>
					<input type="time" name="response[<?php echo $question->rowid; ?>]" data-question-id="<?php echo $question->rowid; ?>"/>

					<?php elseif ($question->type == 'file'): ?>
					<input type="file" name="response[<?php echo $question->rowid; ?>]" data-question-id="<?php echo $question->rowid; ?>"/>

					<?php elseif ($question->type == 'linear_scale'): ?>
					<label>
						<input type="range" name="response[<?php echo $question->rowid; ?>]" min="1" max="10" step="1" data-question-id="<?php echo $question->rowid; ?>"/>
						<span>1 to 10</span>
					</label>
					<?php endif; ?>

				</div>

				<!-- Warning message for mandatory questions -->
				<div class="warning-message" style="display: none; color: red; font-size: 12px;">
					This question is mandatory.
				</div>
			</div>
		<?php endforeach; ?>

		<div class="center" id="form-button">
			<button type="submit" class="button">Submit Survey</button>
		</div>
	</form>


	<script>
		document.addEventListener("DOMContentLoaded", function () {
			const responses = {};

			// Function to update the responses object
			function updateResponse(key, value) {
				if (Array.isArray(value)) {
					// Trim each value in the array
					value = value.map(v => v.trim());
				} else {
					// Trim the single value
					value = value.trim();
				}
				console.log("Updating response for key:", key, "with value:", value);
				responses[key] = value;
				console.log("Current responses object:", responses);
				evaluateLogic();
			}


			// Function to handle input changes
			function handleInputChange(event) {
				const input = event.target;
				const questionId = input.getAttribute('data-question-id');

				if (!questionId) {
					console.warn("No question ID found for input:", input);
					return; // Exit if question ID is not found
				}

				// Handle different input types
				if (input.type === 'checkbox') {
					// Collect checked values and trim each to remove \n
					const checkedValues = Array.from(document.querySelectorAll(`[name="response[${questionId}][]"]:checked`))
						.map(el => el.value.trim());
					console.log(`Checkbox (${questionId}) checked values:`, checkedValues);
					updateResponse(questionId, checkedValues);

				} else if (input.type === 'radio') {
					// Correctly select the checked radio button and trim the value
					const selectedValue = document.querySelector(`[name="response[${questionId}]"]:checked`);
					if (selectedValue) {
						console.log(`Radio (${questionId}) selected value:`, selectedValue.value.trim());
						updateResponse(questionId, selectedValue.value.trim());
					} else {
						console.log(`Radio (${questionId}) selected value:`, ''); // No radio is selected
						updateResponse(questionId, '');
					}
				} else if (input.tagName.toLowerCase() === 'select') {
					if (input.multiple) {
						// Collect multiple selected values and trim each
						const selectedValues = Array.from(input.selectedOptions).map(option => option.value.trim());
						console.log(`Select multiple (${questionId}) selected values:`, selectedValues);
						updateResponse(questionId, selectedValues);
					} else {
						// Trim the value of the single select option
						console.log(`Select (${questionId}) selected value:`, input.value.trim());
						updateResponse(questionId, input.value.trim());
					}
				} else {
					// Trim the value of other input types
					console.log(`Input (${questionId}) value:`, input.value.trim());
					updateResponse(questionId, input.value.trim());
				}
			}
			// Attach event listeners to all relevant inputs
			const inputs = document.querySelectorAll('input, select, textarea');
			inputs.forEach(input => {
				input.addEventListener('change', handleInputChange);
			});



			// Function to evaluate and apply conditional logic based on responses
			function evaluateLogic() {
				const questions = document.querySelectorAll('.question');
				questions.forEach(function (question) {
					const jsonString = question.getAttribute('data-logic');
					try {
						const logicData = JSON.parse(jsonString);

						// Only evaluate conditional logic if there is valid logic data
						if (logicData.groups && logicData.groups.length > 0) {
							const showQuestion = evaluateConditionalLogic(logicData);
							question.style.display = showQuestion ? "block" : "none";
							console.log("Question", question.id, "display:", question.style.display);
						} else {
							// If no logic data, display the question by default
							question.style.display = "block";
						}
					} catch (e) {
						console.error("Error parsing JSON logic for question", question.id, e);
						question.style.display = "none"; // Hide question if logic cannot be parsed
					}
				});
			}

			// Conditional logic evaluation function
			function evaluateConditionalLogic(logicData) {
				if (!logicData.groups || logicData.groups.length === 0) return true;

				let overallResult = true; // Start with `true` to ensure correct combination for "AND" logic initially
				let previousGroupConnector = null; // Initialize previousGroupConnector to null

				// Iterate over each group
				logicData.groups.forEach(function (group, groupIndex) {
					let groupResult = true; // Start with `true` to ensure correct combination for "AND" logic
					let previousConditionConnector = null; // Initialize for each group

					console.log(`Evaluating Group ${groupIndex} with connector: ${group.group_connector}`);

					// Evaluate each condition within the group
					group.conditions.forEach(function (condition, index) {
						const fieldId = condition.field;
						const questionValue = responses[fieldId];
						let conditionMet = false;

						// If question value is undefined or null, skip the evaluation
						if (questionValue === undefined || questionValue === null) {
							conditionMet = false; // Default to false when no response
						} else {
							// Evaluate the condition based on its logic type
							switch (condition.logic) {
								case 'equal':
									conditionMet = Array.isArray(questionValue) ? questionValue.includes(condition.value) : questionValue == condition.value;
									break;
								case 'not_equal':
									conditionMet = Array.isArray(questionValue) ? !questionValue.includes(condition.value) : questionValue != condition.value;
									break;
								case 'greater_than':
									conditionMet = parseFloat(questionValue) > parseFloat(condition.value);
									break;
								case 'less_than':
									conditionMet = parseFloat(questionValue) < parseFloat(condition.value);
									break;
								case 'contains':
									conditionMet = Array.isArray(questionValue) ? questionValue.some(val => val.includes(condition.value)) : questionValue.includes(condition.value);
									break;
								case 'not_contains':
									conditionMet = Array.isArray(questionValue) ? questionValue.every(val => !val.includes(condition.value)) : !questionValue.includes(condition.value);
									break;
								default:
									console.warn(`Unknown condition logic "${condition.logic}"`);
									break;
							}
						}

						// Combine results within the group based on connectors
						if (index === 0) {
							// For the first condition in the group, set the initial result
							groupResult = conditionMet;
						} else {
							if (previousConditionConnector === 'AND') {
								groupResult = groupResult && conditionMet;
							} else if (previousConditionConnector === 'OR') {
								groupResult = groupResult || conditionMet;
							}else {
								groupResult = groupResult || conditionMet;
							}
						}

						console.log(`Condition ${index} (${condition.logic}) result: ${conditionMet}, Group ${groupIndex} intermediate result: ${groupResult}`);

						// Update the previous condition connector for the next iteration
						previousConditionConnector = condition.condition_connector;
					});

					console.log(`Group ${groupIndex} result after conditions evaluation:`, groupResult);

					// Combine the results between groups using group connectors
					if (groupIndex === 0) {
						// For the first group, set overallResult directly
						overallResult = groupResult;
					} else {
						// Combine using the previous group's connector
						if (previousGroupConnector === 'AND') {
							overallResult = overallResult && groupResult;
						} else if (previousGroupConnector === 'OR') {
							overallResult = overallResult || groupResult;
						} else {
							// Handling for null or undefined connectors (default to AND if not specified)
							console.warn(`Group ${groupIndex} connector is null, defaulting to OR.`);
							overallResult = overallResult || groupResult;
						}
					}

					console.log(`Overall result after group ${groupIndex}:`, overallResult);

					// Update the previous group connector for the next group
					previousGroupConnector = group.group_connector;

				});

				console.log('Final overall result:', overallResult);

				return overallResult;
			}




			// Function to validate mandatory fields on form submission
			function validateMandatoryFields() {
				let isValid = true;

				document.querySelectorAll('.mandatory').forEach(function (question) {
					let warning = question.querySelector('.warning-message');
					let inputs = question.querySelectorAll('input, select, textarea');
					let isAnswered = false;

					inputs.forEach(function (input) {
						if ((input.type === 'radio' || input.type === 'checkbox') && input.checked) {
							isAnswered = true;
						} else if (input.type === 'file' && input.files.length > 0) {
							isAnswered = true; // Check if a file has been uploaded
						} else if (input.type === 'date' && input.value !== "") {
							isAnswered = true; // Check if a date has been selected
						} else if (input.type === 'time' && input.value !== "") {
							isAnswered = true; // Check if a time has been selected
						} else if (input.tagName === 'SELECT' && input.value !== "") {
							isAnswered = true;
						} else if ((input.type === 'text' || input.tagName === 'TEXTAREA') && input.value.trim() !== "") {
							isAnswered = true;
						}else if (input.type === 'range'  && input.value.trim() !== "") {
							isAnswered = true;
						}
					});

					if (!isAnswered) {
						isValid = false;
						warning.style.display = 'block';
						question.querySelector('.question-field').classList.add('error');
					} else {
						warning.style.display = 'none';
						question.querySelector('.question-field').classList.remove('error');
					}
				});

				return isValid;
			}

            // Real-time validation when user interacts with inputs
			document.addEventListener('DOMContentLoaded', function () {
				document.querySelectorAll('input, select, textarea').forEach(function (input) {
					input.addEventListener('change', function () {
						let question = input.closest('.mandatory');
						if (question) {
							let warning = question.querySelector('.warning-message');
							let isAnswered = false;

							let inputs = question.querySelectorAll('input, select, textarea');
							inputs.forEach(function (input) {
								if ((input.type === 'radio' || input.type === 'checkbox') && input.checked) {
									isAnswered = true;
								} else if (input.type === 'file' && input.files.length > 0) {
									isAnswered = true; // Check for file uploads
								} else if (input.type === 'date' && input.value !== "") {
									isAnswered = true; // Check for selected date
								} else if (input.type === 'time' && input.value !== "") {
									isAnswered = true; // Check for selected time
								} else if (input.tagName === 'SELECT' && input.value !== "") {
									isAnswered = true;
								} else if ((input.type === 'text' || input.tagName === 'TEXTAREA') && input.value.trim() !== "") {
									isAnswered = true;
								}
							});

							if (isAnswered) {
								warning.style.display = 'none';
								question.querySelector('.question-field').classList.remove('error');
							}
						}
					});
				});
			});

            // Handle form submission
			document.querySelector('form').addEventListener('submit', function (e) {
				// Prevent form submission if validation fails
				if (!validateMandatoryFields()) {
					e.preventDefault();
					alert('Please answer all mandatory questions.');
				}
			});


            // Initial evaluation of conditional logic
			evaluateLogic();


		});

	</script>


<?php else: ?>

	<!-- List of Available Surveys -->
	<?php if (!empty($surveys)): ?>
		<table class="noborder">
			<thead>
			<tr class="liste_titre">
				<th><?php echo $langs->trans('#'); ?></th>
				<th><?php echo $langs->trans('Title'); ?></th>
				<th><?php echo $langs->trans('Description'); ?></th>
				<th><?php echo $langs->trans('Action'); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ($surveys as $survey): ?>
				<tr>
					<td><?php echo htmlspecialchars($survey->rowid); ?></td>
					<td><?php echo htmlspecialchars($survey->title); ?></td>
					<td><?php echo htmlspecialchars($survey->description); ?></td>
					<td>
						<?php if ($survey->has_participated > 0): ?>
							<span class="button disabled"><?php echo $langs->trans('AlreadyParticipated'); ?><br>
							<?php
							// Fetch participation date
							$sql_date = "SELECT date_creation FROM llx_survey_response WHERE fk_survey_question IN (SELECT rowid FROM llx_survey_question WHERE fk_survey = ".$survey->rowid.") AND fk_user = ".$user->id." ORDER BY date_creation DESC LIMIT 1";
							$resql_date = $db->query($sql_date);
							$date_creation = '';
							if ($resql_date) {
								$obj_date = $db->fetch_object($resql_date);
								if ($obj_date) {
									$date_creation = dol_escape_htmltag($obj_date->date_creation);
								}
							}
							echo $langs->trans('Participated On ') . ': ' . $date_creation;
							?>
						</span>
						<?php else: ?>
							<a href="surveyparticipation.php?survey_id=<?php echo $survey->rowid; ?>" class="button"><?php echo $langs->trans('Participate'); ?></a>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php else: ?>
		<p><?php echo $langs->trans('NoSurveysAvailable'); ?></p>
	<?php endif; ?>




<?php endif; ?>

<?php
llxFooter();
?>



