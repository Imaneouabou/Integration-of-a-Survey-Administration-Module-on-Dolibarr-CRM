<?php
global $langs, $db, $user;
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once 'class/Survey.class.php';
require_once 'class/question.class.php';
require_once 'class/response.class.php';

// Check if user has permission to create surveys
if (!$user->hasRight('survey',  'surveycreation')) {
	// If the user does not have permission, deny access and redirect or show an error
	accessforbidden(); // This function will block access and show an error
}

$langs->load("admin");
$langs->load("survey@survey");

$page_name = "SurveyCreation";
llxHeader('', $langs->trans($page_name));

// Link the CSS file
echo '<link rel="stylesheet" type="text/css" href="css/surveycreation.css?v='.time().'">';
echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css?v='.time().'">'; // Font Awesome for icons

$form = new Form($db);

$survey = new Survey($db);
$question = new Question($db);
$response = new Response($db);

// Generate CSRF token
$token = newToken();

// Fetch the last rowid from the llx_survey_question table
$sql = "SELECT MAX(rowid) as last_rowid FROM llx_survey_question";
$resql = $db->query($sql);
if ($resql) {
	$obj = $db->fetch_object($resql);
	$lastRowid = $obj->last_rowid ?? 0;
} else {
	dol_print_error($db);
	$lastRowid = 0;
}
?>

<h1><?php echo $langs->trans($page_name); ?></h1>

	<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
		<input type="hidden" name="token" value="<?php echo $token; ?>"> <!-- Include CSRF token -->
		<div class="survey-form">
			<h2><?php echo $langs->trans("Survey Details"); ?></h2>
			<div class="form-group">
				<label for="title"><?php echo $langs->trans("Title"); ?></label>
				<input type="text" id="title" name="title" size="30" required/>
			</div>
			<div class="form-group">
				<label for="description"><?php echo $langs->trans("Description"); ?></label>
				<textarea id="description" name="description" rows="3" cols="30" required></textarea>
			</div>
			<div class="form-group">
				<label for="status"><?php echo $langs->trans("Status"); ?></label>
				<select id="status" name="status">
					<option value="0"><?php echo "Disabled"; ?></option>
					<option value="1"><?php echo "Enabled"; ?></option>
					<option value="2"><?php echo "Terminated"; ?></option>
				</select>
			</div>
			<div class="form-group">
				<label for="date_start"><?php echo $langs->trans("Start Date"); ?></label>
				<input type="date" id="date_start" name="date_start" required/>
			</div>
			<div class="form-group">
				<label for="date_end"><?php echo $langs->trans("End Date"); ?></label>
				<input type="date" id="date_end" name="date_end" required/>
			</div>

			<h2><?php echo $langs->trans("Survey Questions"); ?></h2>

			<div id="questions">
				<!-- Questions will be appended here -->
			</div>
			<button type="button" id="add-question" class="button">Add Question</button>

			<div class="center">
				<input type="submit" name="save_survey" class="button" value="Save" />
			</div>
		</div>
	</form>


	<script>
		var nextRowid = <?php echo $lastRowid + 1; ?>; // Start from the last rowid + 1
		var surveyQuestions = [];  // Store questions with their assigned rowid and text

		document.getElementById('add-question').addEventListener('click', function() {
			var questionIndex = surveyQuestions.length;

			var newQuestion = document.createElement('div');
			newQuestion.className = 'question';
			newQuestion.dataset.rowid = nextRowid; // Assign the next rowid
			newQuestion.innerHTML = `
        <div class="form-group">
            <label>Question Text:</label>
            <input type="text" name="questions[${questionIndex}][text]" placeholder="Question text" required />
        </div>
        <div class="form-group">
            <label>Question Type:</label>
            <select name="questions[${questionIndex}][type]" class="question-type" required>
                <option value="">Select question type</option>
                <option value="text">Short Answer</option>
                <option value="textarea">Paragraph</option>
                <option value="radio">Multiple Choice</option>
                <option value="checkbox">Checkboxes</option>
                <option value="select">Dropdown</option>
                <option value="file">File Upload</option>
                <option value="linear_scale">Linear Scale</option>
                <option value="multiple_choice_grid">Multiple Choice Grid</option>
                <option value="checkbox_grid">Checkbox Grid</option>
                <option value="date">Date</option>
                <option value="time">Time</option>
            </select>
        </div>
        <div class="form-group options" style="display: none;">
            <label>Options:</label>
            <div class="option">
                <input type="text" name="questions[${questionIndex}][option_text][]" placeholder="Option" />
            </div>
            <button type="button" class="add-option" data-question-index="${questionIndex}">Add Option</button>
        </div>
        <div class="form-group">
            <label>Conditional Logic:</label>
            <label class="switch">
                <input type="checkbox" class="conditional-logic-toggle">
                <span class="slider round"></span>
            </label>
        </div>
        <div class="conditional-logic" style="display: none;">
            <div class="condition-group" style="border: 1px solid #ddd; padding: 10px; margin-bottom: 10px;">
                <label>Show this field group if:</label>
                <div class="condition-row">
                    <select name="questions[${questionIndex}][conditional_logic][0][0][field]" class="conditional-field">
                        <option value="">Select a question</option>
                        ${surveyQuestions.map(q => `<option value="${q.rowid}">${q.text || `Question ${q.rowid}`}</option>`).join('')}
                    </select>
                    <select name="questions[${questionIndex}][conditional_logic][0][0][logic]">
                        <option value="equal">is equal to</option>
                        <option value="not_equal">is not equal to</option>
                        <option value="greater_than">is greater than</option>
                        <option value="less_than">is less than</option>
                        <option value="contains">contains</option>
                        <option value="not_contains">does not contain</option>
                    </select>
                    <div class="conditional-value-wrapper">
                        <input type="text" name="questions[${questionIndex}][conditional_logic][0][0][value]" class="conditional-value" />
                    </div>
                    <button type="button" class="delete-condition"><i class="fas fa-trash-alt"></i></button>
                    <button type="button" class="add-condition">add</button>
                </div>
            </div>
            <div class="add-rule-group">
                <span>or</span>
                <button type="button" class="add-rule-group-btn">Add rule group</button>
            </div>
        </div>
        <div class="form-group actions" style="text-align: right; display: flex; align-items: center; justify-content: flex-end;">
            <i class="fas fa-copy duplicate-question" title="Duplicate"></i>
            <i class="fas fa-trash delete-question" title="Delete"></i>
            <div class="separator"></div>
            <label style="margin: 0 10px;">Mandatory:</label>
            <label class="switch">
                <input type="checkbox" name="questions[${questionIndex}][mandatory]" value="1">
                <span class="slider round"></span>
            </label>
        </div>
    `;

			document.getElementById('questions').appendChild(newQuestion);

			// Add this question to the surveyQuestions array
			surveyQuestions.push({ rowid: nextRowid, text: '' });

			// Increment the rowid for the next question
			nextRowid++;

			// Update all dropdowns
			updateConditionalLogicOptions();
		});

		function updateConditionalLogicOptions() {
			document.querySelectorAll('.question').forEach(function(q) {
				var rowid = q.dataset.rowid;
				var textInput = q.querySelector('input[name$="[text]"]');
				if (textInput) {
					var text = textInput.value;
					// Update the surveyQuestions array
					var existingQuestion = surveyQuestions.find(q => q.rowid === parseInt(rowid));
					if (existingQuestion) {
						existingQuestion.text = text;
					}
				}
			});

			// Update the dropdowns
			document.querySelectorAll('select.conditional-field').forEach(function(select) {
				var currentValue = select.value;
				select.innerHTML = '';
				select.appendChild(new Option('Select a question', ''));
				surveyQuestions.forEach(function(q) {
					var option = new Option(q.text || `Question ${q.rowid}`, q.rowid);
					select.appendChild(option);
				});
				select.value = currentValue;  // Restore the selected value
			});
		}

		document.addEventListener('change', function(e) {
			if (e.target.classList.contains('question-type')) {
				var optionsDiv = e.target.closest('.question').querySelector('.options');
				var type = e.target.value;

				if (type === 'radio' || type === 'checkbox' || type === 'select') {
					optionsDiv.style.display = 'block';
				} else {
					optionsDiv.style.display = 'none';
				}
			}

			if (e.target.classList.contains('conditional-logic-toggle')) {
				var conditionalLogicDiv = e.target.closest('.question').querySelector('.conditional-logic');
				conditionalLogicDiv.style.display = e.target.checked ? 'block' : 'none';

				if (e.target.checked) {
					var questionIndex = e.target.closest('.question').dataset.rowid;
					var defaultGroup = conditionalLogicDiv.querySelector('.condition-group');

					if (!defaultGroup) {
						// Create the default group if it doesn't exist
						var defaultGroupHtml = `
                    <div class="condition-group" style="border: 1px solid #ddd; padding: 10px; margin-bottom: 10px;">
                        <label>Show this field group if:</label>
                        <div class="condition-row">
                            <select name="questions[${questionIndex}][conditional_logic][0][0][field]" class="conditional-field">
                                <option value="">Select a question</option>
                                ${surveyQuestions.map(q => `<option value="${q.rowid}">${q.text || `Question ${q.rowid}`}</option>`).join('')}
                            </select>
                            <select name="questions[${questionIndex}][conditional_logic][0][0][logic]">
                                <option value="equal">is equal to</option>
                                <option value="not_equal">is not equal to</option>
                                <option value="greater_than">is greater than</option>
                                <option value="less_than">is less than</option>
                                <option value="contains">contains</option>
                                <option value="not_contains">does not contain</option>
                            </select>
                            <div class="conditional-value-wrapper">
                                <input type="text" name="questions[${questionIndex}][conditional_logic][0][0][value]" class="conditional-value" />
                            </div>
                            <button type="button" class="delete-condition"><i class="fas fa-trash-alt"></i></button>
                            <button type="button" class="add-condition">add</button>
                        </div>
                    </div>
                `;
						conditionalLogicDiv.insertAdjacentHTML('afterbegin', defaultGroupHtml);
					}
				}
			}

			// Ensure that the selected rowid is properly stored in the field
			if (e.target.classList.contains('conditional-field')) {
				var selectedRowid = e.target.value;
				var conditionRow = e.target.closest('.condition-row');

				if (conditionRow) {
					var fieldInput = conditionRow.querySelector('input[name$="[field]"]');
					if (fieldInput) {
						fieldInput.value = selectedRowid;
					} else {
						console.error('Field input not found in condition row');
					}
				} else {
					console.error('Condition row not found');
				}
			}

			// Update the surveyQuestions array when the question text is changed
			if (e.target.name && e.target.name.includes('text')) {
				updateConditionalLogicOptions();
			}
		});

		document.addEventListener('click', function(e) {
			if (e.target.classList.contains('add-option')) {
				var optionsDiv = e.target.closest('.options');
				var questionIndex = e.target.getAttribute('data-question-index');
				var optionIndex = optionsDiv.querySelectorAll('.option').length;
				var newOption = document.createElement('div');
				newOption.className = 'option';
				newOption.innerHTML = `
            <input type="text" name="questions[${questionIndex}][option_text][${optionIndex}]" placeholder="Option" />
        `;
				optionsDiv.insertBefore(newOption, e.target);
			}

			if (e.target.classList.contains('delete-question')) {
				var questionDiv = e.target.closest('.question');
				questionDiv.parentNode.removeChild(questionDiv);
				surveyQuestions = surveyQuestions.filter(q => q.rowid !== parseInt(questionDiv.dataset.rowid));
				updateConditionalLogicOptions();
			}

			if (e.target.classList.contains('duplicate-question')) {
				var questionDiv = e.target.closest('.question');
				var clonedQuestion = questionDiv.cloneNode(true);
				clonedQuestion.dataset.rowid = nextRowid; // Assign the next rowid
				document.getElementById('questions').appendChild(clonedQuestion);

				// Add this duplicate question to the surveyQuestions array
				surveyQuestions.push({ rowid: nextRowid, text: '' });

				// Increment the rowid for the next question
				nextRowid++;

				updateConditionalLogicOptions();
			}

			// Handle adding a new condition within the same rule group
			if (e.target.classList.contains('add-condition')) {
				var conditionRow = e.target.closest('.condition-row').cloneNode(true);
				conditionRow.querySelectorAll('input, select').forEach(function(input) {
					input.value = ''; // Clear out the values
				});

				var conditionGroup = e.target.closest('.condition-group');
				var questionDiv = conditionGroup.closest('.question');
				var questionIndex = Array.prototype.indexOf.call(document.querySelectorAll('.question'), questionDiv);
				var groupIndex = Array.prototype.indexOf.call(conditionGroup.parentElement.querySelectorAll('.condition-group'), conditionGroup);
				var conditionIndex = conditionGroup.querySelectorAll('.condition-row').length;

				// Update the names of the inputs in the cloned row
				conditionRow.querySelectorAll('input, select').forEach(function(input) {
					var name = input.getAttribute('name');
					if (name) {
						input.setAttribute('name', name.replace(/\[\d+\]\[\d+\]/, `[${groupIndex}][${conditionIndex}]`));
					}
				});

				// Only add a connector if this is not the first condition (conditionIndex > 0)
				if (conditionIndex > 0) {
					var connectorDropdown = document.createElement('select');
					connectorDropdown.name = `questions[${questionIndex}][conditional_logic][${groupIndex}][${conditionIndex - 1}][condition_connector]`; // Correct naming
					connectorDropdown.innerHTML = `
                <option value="AND">AND</option>
                <option value="OR">OR</option>
            `;
					connectorDropdown.className = 'connector-dropdown';
					connectorDropdown.style.margin = '10px 0';

					// Append the connector after the last condition
					conditionGroup.appendChild(connectorDropdown);
				}

				// Append the new condition row to the group
				conditionGroup.appendChild(conditionRow);
			}

			// Handle adding a new rule group
			if (e.target.classList.contains('add-rule-group-btn')) {
				var questionDiv = e.target.closest('.question');
				var questionIndex = Array.prototype.indexOf.call(document.querySelectorAll('.question'), questionDiv);
				var conditionalLogicDiv = e.target.closest('.conditional-logic');
				var ruleGroupIndex = conditionalLogicDiv.querySelectorAll('.condition-group').length;

				var ruleGroup = document.createElement('div');
				ruleGroup.className = 'condition-group';
				ruleGroup.style.border = '1px solid #ddd';
				ruleGroup.style.padding = '10px';
				ruleGroup.style.marginBottom = '10px';

				ruleGroup.innerHTML = `
            <label>Show this field group if:</label>
            <div class="condition-row">
                <select name="questions[${questionIndex}][conditional_logic][${ruleGroupIndex}][0][field]" class="conditional-field">
                    <option value="">Select a question</option>
                    ${surveyQuestions.map(q => `<option value="${q.rowid}">${q.text || `Question ${q.rowid}`}</option>`).join('')}
                </select>
                <select name="questions[${questionIndex}][conditional_logic][${ruleGroupIndex}][0][logic]">
                    <option value="equal">is equal to</option>
                    <option value="not_equal">is not equal to</option>
                    <option value="greater_than">is greater than</option>
                    <option value="less_than">is less than</option>
                    <option value="contains">contains</option>
                    <option value="not_contains">does not contain</option>
                </select>
                <div class="conditional-value-wrapper">
                    <input type="text" name="questions[${questionIndex}][conditional_logic][${ruleGroupIndex}][0][value]" class="conditional-value" />
                </div>
                <button type="button" class="delete-condition"><i class="fas fa-trash-alt"></i></button>
                <button type="button" class="add-condition">add</button>
            </div>
        `;
				// Only add the group connector if this is not the first group (ruleGroupIndex > 0)
				if (ruleGroupIndex > 0) {
					var connectorDropdown = document.createElement('select');
					connectorDropdown.name = `questions[${questionIndex}][conditional_logic][${ruleGroupIndex - 1}][group_connector]`; // Store the connector in the previous group
					connectorDropdown.innerHTML = `
            <option value="AND">AND</option>
            <option value="OR">OR</option>
        `;
					connectorDropdown.className = 'connector-dropdown';
					connectorDropdown.style.margin = '10px 0';

					// Insert the connector just before the newly created group
					conditionalLogicDiv.insertBefore(connectorDropdown, conditionalLogicDiv.querySelector('.add-rule-group'));
				}

				// Append the new rule group at the end of the conditional logic div
				conditionalLogicDiv.insertBefore(ruleGroup, conditionalLogicDiv.querySelector('.add-rule-group'));

				updateConditionalLogicOptions();

			}
			// Handle deleting conditions within a group
			if (e.target.classList.contains('delete-condition') || e.target.closest('.delete-condition')) {
				var conditionRow = e.target.closest('.condition-row');
				var conditionGroup = conditionRow.closest('.condition-group');

				// If this is not the first condition, remove the connector before it
				var previousConnector = conditionRow.previousElementSibling;
				if (previousConnector && previousConnector.tagName === 'SELECT') {
					previousConnector.parentNode.removeChild(previousConnector);
				}

				// Remove the condition row
				conditionRow.parentNode.removeChild(conditionRow);

				// If there are no more condition rows left, remove the entire group
				if (conditionGroup.querySelectorAll('.condition-row').length === 0) {
					var previousGroupConnector = conditionGroup.previousElementSibling;
					if (previousGroupConnector && previousGroupConnector.tagName === 'SELECT') {
						previousGroupConnector.parentNode.removeChild(previousGroupConnector);
					}
					conditionGroup.parentNode.removeChild(conditionGroup);
				}
			}

			// Handle deleting groups
			if (e.target.classList.contains('delete-group') || e.target.closest('.delete-group')) {
				var conditionGroup = e.target.closest('.condition-group');
				var conditionalLogic = conditionGroup.closest('.conditional-logic');

				// Remove the condition group
				conditionGroup.parentNode.removeChild(conditionGroup);

				// Remove the previous connector dropdown (between groups)
				var previousConnector = conditionalLogic.querySelector('select[name$="[group_connector]"]:last-of-type');
				if (previousConnector && previousConnector.nextElementSibling !== conditionGroup) {
					previousConnector.parentNode.removeChild(previousConnector);
				}
			}
		});
	</script>





<?php
llxFooter();
?>

<?php

// Now we handle the saving of the survey and questions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_survey'])) {
	if (empty($token) || $token !== $_SESSION['newtoken']) {
		accessforbidden();
	}
	// Debugging: Print out the entire $_POST array to see the structure
	echo '<pre>';
	print_r($_POST['questions']);
	echo '</pre>';

	if (isset($_POST['title']) && isset($_POST['description'])) {
		$survey->title = $_POST['title'];
		$survey->description = $_POST['description'];
		$survey->status = $_POST['status'];
		$survey->date_start = $_POST['date_start'];
		$survey->date_end = $_POST['date_end'];

		$surveyId = $survey->create($user);
		if ($surveyId > 0) {
			if (!empty($_POST['questions'])) {
				foreach ($_POST['questions'] as $index => $question) {
					$text = $question['text'] ?? null;
					$type = $question['type'] ?? null;
					$optionTexts = $question['option_text'] ?? [];
					$conditionalLogic = formatConditionalLogic($question['conditional_logic'] ?? []);
					$isMandatory = isset($question['mandatory']) ? 1 : 0;
					$rowid = saveQuestion($surveyId, $text, $type, $optionTexts, $conditionalLogic, $isMandatory);

					if ($rowid > 0) {
						// You can remove this script if it's not necessary
						echo "<script>
                            if (!surveyQuestions[$index]) {
                                surveyQuestions[$index] = {};
                            }
                            surveyQuestions[$index].rowid = $rowid;
                            updateConditionalLogicOptions();
                        </script>";
					}
				}
			} else {
				dol_print_error($db, "No questions provided.");
			}
		} else {
			echo "Failed to create survey. Error: " . $survey->error . "<br>";
		}
	} else {
		echo "Title and description are required.";
	}

}

function saveQuestion($surveyId, $text, $type, $optionTexts, $conditionalLogic, $isMandatory) {
	global $db;

	// Skip saving if the text is empty
	if (empty(trim($text))) {
		return -1; // Indicate that no question was saved
	}

	$question = new Question($db);
	$question->fk_survey = $surveyId;
	$question->question = $text;
	$question->type = $type;
	$question->options = json_encode(array_values($optionTexts), JSON_UNESCAPED_UNICODE);
	$question->conditional_logic = $db->escape($conditionalLogic);
	$question->position = 0; // Set position, can be updated later if necessary
	$question->mandatory = $isMandatory ? 1 : 0;

	$result = $question->create();

	if ($result > 0) {
		return $question->rowid;
	} else {
		dol_print_error($db, "Failed to create question: $text. Error: " . $question->error);
		return -1;
	}
}

function formatConditionalLogic($conditionalLogic) {
	if (empty($conditionalLogic)) return '';

	$groups = [];
	foreach ($conditionalLogic as $group) {
		$conditions = [];
		foreach ($group as $index => $condition) {
			if (!empty($condition['field'])) {
				$conditions[] = [
					'field' => $condition['field'],
					'logic' => $condition['logic'],
					'value' => $condition['value'],
					'condition_connector' => isset($condition['condition_connector']) ? $condition['condition_connector'] : null, // Store the condition connector
				];
			}
		}

		if (!empty($conditions)) {
			$groups[] = [
				'conditions' => $conditions,
				'group_connector' => $group['group_connector'] ?? null,
			];
		}
	}

	return json_encode(['groups' => $groups], JSON_UNESCAPED_UNICODE);
}
