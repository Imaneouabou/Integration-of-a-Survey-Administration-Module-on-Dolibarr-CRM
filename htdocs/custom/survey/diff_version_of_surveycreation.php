best code for surveycreation .php

<?php
global $langs, $db, $user;
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once 'class/Survey.class.php';
require_once 'class/question.class.php';
require_once 'class/response.class.php';

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
**********************************************



<?php  // this version good but it has problem with fields and options arent saved ina list?>
<?php
global $langs, $db, $user;
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once 'class/Survey.class.php';
require_once 'class/question.class.php';
require_once 'class/response.class.php';

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

	<div class="intropage"><h1><?php echo $langs->trans($page_name); ?></h1></div>

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
                <button type="button" class="add-option">Add Option</button>
            </div>
            <div class="form-group">
                <label>Conditional Logic:</label>
                <label class="switch">
                    <input type="checkbox" class="conditional-logic-toggle">
                    <span class="slider round"></span>
                </label>
            </div>
            <div class="conditional-logic" style="display: none;">
                <div class="condition-group">
                    <label>Show this field group if:</label>
                    <div class="condition-row">
                        <select name="questions[${questionIndex}][conditional_logic][0][field]" class="conditional-field">
                            <option value="">Select a question</option>
                          ${surveyQuestions.map(q => `<option value="${q.rowid}">${q.text || `Question ${q.rowid}`}</option>`).join('')}
                        </select>
                        <select name="questions[${questionIndex}][conditional_logic][0][logic]">
                            <option value="equal">is equal to</option>
                            <option value="not_equal">is not equal to</option>
                            <option value="greater_than">is greater than</option>
                            <option value="less_than">is less than</option>
                            <option value="contains">contains</option>
                            <option value="not_contains">does not contain</option>
                        </select>
                        <div class="conditional-value-wrapper">
                            <input type="text" name="questions[${questionIndex}][conditional_logic][0][value]" class="conditional-value" />
                        </div>
                        <select name="questions[${questionIndex}][conditional_logic][0][connector]">
                            <option value="AND">AND</option>
                            <option value="OR">OR</option>
                        </select>
                        <button type="button" class="delete-condition"><i class="fas fa-trash-alt"></i></button>
                        <button type="button" class="and-condition">and</button>
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
			}

			// Ensure that the selected rowid is properly stored in the field
			if (e.target.classList.contains('conditional-field')) {
				var selectedRowid = e.target.value;
				if (selectedRowid) {
					e.target.closest('.condition-row').querySelector('input[name$="[field]"]').value = selectedRowid;
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
				var questionIndex = optionsDiv.closest('.question').dataset.rowid;
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

			if (e.target.classList.contains('and-condition')) {
				var conditionRow = e.target.closest('.condition-row').cloneNode(true);
				conditionRow.querySelector('.and-condition').remove();
				e.target.closest('.condition-group').appendChild(conditionRow);
			}

			if (e.target.classList.contains('add-rule-group-btn')) {
				var questionIndex = e.target.closest('.question').dataset.rowid;
				var ruleGroup = document.createElement('div');
				ruleGroup.className = 'condition-group';
				ruleGroup.innerHTML = `
            <label>Show this field group if:</label>
            <div class="condition-row">
                <select name="questions[${questionIndex}][conditional_logic][0][field]" class="conditional-field">
                    <option value="">Select a question</option>
                    ${surveyQuestions.map(q => `<option value="${q.rowid}">${q.text || `Question ${q.rowid}`}</option>`).join('')}
                </select>
                <select name="questions[${questionIndex}][conditional_logic][0][logic]">
                    <option value="equal">is equal to</option>
                    <option value="not_equal">is not equal to</option>
                    <option value="greater_than">is greater than</option>
                    <option value="less_than">is less than</option>
                    <option value="contains">contains</option>
                    <option value="not_contains">does not contain</option>
                </select>
                <div class="conditional-value-wrapper">
                    <input type="text" name="questions[${questionIndex}][conditional_logic][0][value]" class="conditional-value" />
                </div>
                <select name="questions[${questionIndex}][conditional_logic][0][connector]">
                    <option value="AND">AND</option>
                    <option value="OR">OR</option>
                </select>
                <button type="button" class="delete-condition"><i class="fas fa-trash-alt"></i></button>
                <button type="button" class="and-condition">and</button>
            </div>
        `;
				e.target.closest('.conditional-logic').insertBefore(ruleGroup, e.target.closest('.add-rule-group'));
				updateConditionalLogicOptions();
			}

			if (e.target.classList.contains('delete-condition') || e.target.closest('.delete-condition')) {
				var conditionRow = e.target.closest('.condition-row');
				var conditionGroup = conditionRow.closest('.condition-group');

				if (conditionGroup.querySelectorAll('.condition-row').length === 1) {
					// If there's only one condition row, remove the entire condition group
					conditionGroup.parentNode.removeChild(conditionGroup);
				} else {
					// Otherwise, just remove the specific condition row
					conditionRow.parentNode.removeChild(conditionRow);
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
	if (empty($token) || $token !== $_SESSION['newtoken']) { // Validate CSRF token
		accessforbidden();
	}

	if (isset($_POST['title']) && isset($_POST['description'])) {
		$survey->title = $_POST['title'];
		$survey->description = $_POST['description'];
		$survey->status = $_POST['status'];
		$survey->date_start = $_POST['date_start'];
		$survey->date_end = $_POST['date_end'];

		$surveyId = $survey->create($user);  // This is where the survey is created
		if ($surveyId > 0) {
			echo "Survey created with ID: $surveyId<br>";

			if (!empty($_POST['questions'])) {
				foreach ($_POST['questions'] as $index => $question) {
					$text = $question['text'] ?? null;
					$type = $question['type'] ?? null;
					$optionTexts = $question['option_text'] ?? [];
					$conditionalLogic = $question['conditional_logic'] ?? [];
					$isMandatory = isset($question['mandatory']) ? 1 : 0;

					// Save the question and get the actual rowid
					$rowid = saveQuestion($surveyId, $text, $type, $optionTexts, $conditionalLogic, $isMandatory);

					if ($rowid > 0) {
						echo "Question created with rowid: $rowid<br>";

						// Update the conditional logic fields with the actual rowid
						echo "<script>
                        surveyQuestions[$index].rowid = $rowid;
                        updateConditionalLogicOptions();
                        </script>";
					} else {
						echo "Failed to create question: $text<br>";
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

	$question = new Question($db);

	$question->fk_survey = $surveyId;
	$question->question = $text;
	$question->type = $type;
	$question->options = json_encode($optionTexts, JSON_UNESCAPED_UNICODE);

	// Properly escape the JSON string
	$question->conditional_logic = $db->escape(json_encode($conditionalLogic, JSON_UNESCAPED_UNICODE));

	$question->position = 0;  // Position could be assigned dynamically
	$question->mandatory = $isMandatory ? 1 : 0;  // Store 1 if mandatory, else 0

	$result = $question->create();

	if ($result > 0) {
		return $question->rowid;  // Return the newly created question's rowid
	} else {
		dol_print_error($db, "Failed to create question: $text. Error: " . $question->error);
		return -1;
	}
}
?>



<?php //this version is quite good no problem with the options storage?>


<?php
/*global $langs, $db, $user;
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once 'class/Survey.class.php';
require_once 'class/question.class.php';
require_once 'class/response.class.php';

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

	<div class="intropage"><h1><?php echo $langs->trans($page_name); ?></h1></div>

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
                <div class="condition-group">
                    <label>Show this field group if:</label>
                    <div class="condition-row">
                        <select name="questions[${questionIndex}][conditional_logic][0][field]" class="conditional-field">
                            <option value="">Select a question</option>
                          ${surveyQuestions.map(q => `<option value="${q.rowid}">${q.text || `Question ${q.rowid}`}</option>`).join('')}
                        </select>
                        <select name="questions[${questionIndex}][conditional_logic][0][logic]">
                            <option value="equal">is equal to</option>
                            <option value="not_equal">is not equal to</option>
                            <option value="greater_than">is greater than</option>
                            <option value="less_than">is less than</option>
                            <option value="contains">contains</option>
                            <option value="not_contains">does not contain</option>
                        </select>
                        <div class="conditional-value-wrapper">
                            <input type="text" name="questions[${questionIndex}][conditional_logic][0][value]" class="conditional-value" />
                        </div>
                        <select name="questions[${questionIndex}][conditional_logic][0][connector]">
                            <option value="AND">AND</option>
                            <option value="OR">OR</option>
                        </select>
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
			}

			// Ensure that the selected rowid is properly stored in the field
			if (e.target.classList.contains('conditional-field')) {
				var selectedRowid = e.target.value;
				var conditionRow = e.target.closest('.condition-row');

				// Check if conditionRow and input field exist before setting value
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

			if (e.target.classList.contains('add-condition')) {
				var conditionRow = e.target.closest('.condition-row').cloneNode(true);
				conditionRow.querySelector('.add-condition').remove();
				e.target.closest('.condition-group').appendChild(conditionRow);
			}

			if (e.target.classList.contains('add-rule-group-btn')) {
				var questionIndex = e.target.closest('.question').dataset.rowid;
				var ruleGroup = document.createElement('div');
				ruleGroup.className = 'condition-group';
				ruleGroup.innerHTML = `
                <label>Show this field group if:</label>
                <div class="condition-row">
                    <select name="questions[${questionIndex}][conditional_logic][0][field]" class="conditional-field">
                        <option value="">Select a question</option>
                        ${surveyQuestions.map(q => `<option value="${q.rowid}">${q.text || `Question ${q.rowid}`}</option>`).join('')}
                    </select>
                    <select name="questions[${questionIndex}][conditional_logic][0][logic]">
                        <option value="equal">is equal to</option>
                        <option value="not_equal">is not equal to</option>
                        <option value="greater_than">is greater than</option>
                        <option value="less_than">is less than</option>
                        <option value="contains">contains</option>
                        <option value="not_contains">does not contain</option>
                    </select>
                    <div class="conditional-value-wrapper">
                        <input type="text" name="questions[${questionIndex}][conditional_logic][0][value]" class="conditional-value" />
                    </div>
                    <select name="questions[${questionIndex}][conditional_logic][0][connector]">
                        <option value="AND">AND</option>
                        <option value="OR">OR</option>
                    </select>
                    <button type="button" class="delete-condition"><i class="fas fa-trash-alt"></i></button>
                    <button type="button" class="add-condition">and</button>
                </div>
            `;
				e.target.closest('.conditional-logic').insertBefore(ruleGroup, e.target.closest('.add-rule-group'));
				updateConditionalLogicOptions();
			}

			if (e.target.classList.contains('delete-condition') || e.target.closest('.delete-condition')) {
				var conditionRow = e.target.closest('.condition-row');
				var conditionGroup = conditionRow.closest('.condition-group');

				if (conditionGroup.querySelectorAll('.condition-row').length === 1) {
					// If there's only one condition row, remove the entire condition group
					conditionGroup.parentNode.removeChild(conditionGroup);
				} else {
					// Otherwise, just remove the specific condition row
					conditionRow.parentNode.removeChild(conditionRow);
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
	if (empty($token) || $token !== $_SESSION['newtoken']) { // Validate CSRF token
		accessforbidden();
	}
	print_r($_POST);
	if (isset($_POST['title']) && isset($_POST['description'])) {
		$survey->title = $_POST['title'];
		$survey->description = $_POST['description'];
		$survey->status = $_POST['status'];
		$survey->date_start = $_POST['date_start'];
		$survey->date_end = $_POST['date_end'];

		$surveyId = $survey->create($user);  // This is where the survey is created
		if ($surveyId > 0) {
			echo "Survey created with ID: $surveyId<br>";

			if (!empty($_POST['questions'])) {
				foreach ($_POST['questions'] as $index => $question) {
					$text = $question['text'] ?? null;
					$type = $question['type'] ?? null;
					$optionTexts = $question['option_text'] ?? [];
					$conditionalLogic = $question['conditional_logic'] ?? [];
					$isMandatory = isset($question['mandatory']) ? 1 : 0;

					// Save the question and get the actual rowid
					$rowid = saveQuestion($surveyId, $text, $type, $optionTexts, $conditionalLogic, $isMandatory);

					if ($rowid > 0) {
						echo "Question created with rowid: $rowid<br>";

						// Update the conditional logic fields with the actual rowid
						echo "<script>
						if (!surveyQuestions[$index]) {
							surveyQuestions[$index] = {}; // Initialize the object if it doesn't exist
						}
						surveyQuestions[$index].rowid = $rowid;
						updateConditionalLogicOptions();
						</script>";

					} else {
						echo "Failed to create question: $text<br>";
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

	$question = new Question($db);

	$question->fk_survey = $surveyId;
	$question->question = $text;
	$question->type = $type;

	// Make sure the options are correctly formatted into a JSON array
	$question->options = json_encode(array_values($optionTexts), JSON_UNESCAPED_UNICODE);

	// Properly escape the JSON string for conditional logic
	$question->conditional_logic = $db->escape(json_encode($conditionalLogic, JSON_UNESCAPED_UNICODE));

	$question->position = 0;  // Position could be assigned dynamically
	$question->mandatory = $isMandatory ? 1 : 0;  // Store 1 if mandatory, else 0

	$result = $question->create();

	if ($result > 0) {
		return $question->rowid;
	} else {
		dol_print_error($db, "Failed to create question: $text. Error: " . $question->error);
		return -1;
	}
}








*/

/*This code with good css of connectors between conditions and between groups  */

/*
<?php
global $langs, $db, $user;
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once 'class/Survey.class.php';
require_once 'class/question.class.php';
require_once 'class/response.class.php';

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

	<div class="intropage"><h1><?php echo $langs->trans($page_name); ?></h1></div>

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
                        <select name="questions[${questionIndex}][conditional_logic][0][field]" class="conditional-field">
                            <option value="">Select a question</option>
                            ${surveyQuestions.map(q => `<option value="${q.rowid}">${q.text || `Question ${q.rowid}`}</option>`).join('')}
                        </select>
                        <select name="questions[${questionIndex}][conditional_logic][0][logic]">
                            <option value="equal">is equal to</option>
                            <option value="not_equal">is not equal to</option>
                            <option value="greater_than">is greater than</option>
                            <option value="less_than">is less than</option>
                            <option value="contains">contains</option>
                            <option value="not_contains">does not contain</option>
                        </select>
                        <div class="conditional-value-wrapper">
                            <input type="text" name="questions[${questionIndex}][conditional_logic][0][value]" class="conditional-value" />
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
			}

			// Ensure that the selected rowid is properly stored in the field
			if (e.target.classList.contains('conditional-field')) {
				var selectedRowid = e.target.value;
				var conditionRow = e.target.closest('.condition-row');

				// Check if conditionRow and input field exist before setting value
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
				var conditionIndex = conditionGroup.querySelectorAll('.condition-row').length;

				// Update the name attributes for the new condition
				conditionRow.querySelectorAll('input, select').forEach(function(input) {
					var name = input.getAttribute('name');
					if (name) {
						input.setAttribute('name', name.replace(/\[\d+\]\[(\d+)\]/, `[$1][${conditionIndex}]`));
					}
				});

				// Insert the connector dropdown between conditions
				var connectorDropdown = document.createElement('select');
				connectorDropdown.name = `questions[${conditionGroup.closest('.question').dataset.rowid}][conditional_logic][${conditionIndex}][connector]`;
				connectorDropdown.innerHTML = `
                <option value="AND">AND</option>
                <option value="OR">OR</option>
            `;
				connectorDropdown.className = 'connector-dropdown';
				connectorDropdown.style.margin = '10px 0';

				conditionGroup.appendChild(connectorDropdown);
				conditionGroup.appendChild(conditionRow);
			}

			// Handle adding a new rule group
			if (e.target.classList.contains('add-rule-group-btn')) {
				var questionIndex = e.target.closest('.question').dataset.rowid;
				var ruleGroupIndex = e.target.closest('.conditional-logic').querySelectorAll('.condition-group').length;
				var ruleGroup = document.createElement('div');
				ruleGroup.className = 'condition-group';
				ruleGroup.style.border = '1px solid #ddd'; // Add border for visual distinction
				ruleGroup.style.padding = '10px'; // Add padding for better appearance
				ruleGroup.style.marginBottom = '10px'; // Space between rule groups

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

				// Insert the connector dropdown between groups
				var connectorDropdown = document.createElement('select');
				connectorDropdown.name = `questions[${questionIndex}][conditional_logic][${ruleGroupIndex}][group_connector]`;
				connectorDropdown.innerHTML = `
                <option value="AND">AND</option>
                <option value="OR">OR</option>
            `;
				connectorDropdown.className = 'connector-dropdown';
				connectorDropdown.style.margin = '10px 0';

				e.target.closest('.conditional-logic').insertBefore(connectorDropdown, e.target.closest('.add-rule-group'));

				e.target.closest('.conditional-logic').insertBefore(ruleGroup, e.target.closest('.add-rule-group'));

				updateConditionalLogicOptions();
			}

			// Handle deleting conditions
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
	if (empty($token) || $token !== $_SESSION['newtoken']) { // Validate CSRF token
		accessforbidden();
	}
	print_r($_POST);
	if (isset($_POST['title']) && isset($_POST['description'])) {
		$survey->title = $_POST['title'];
		$survey->description = $_POST['description'];
		$survey->status = $_POST['status'];
		$survey->date_start = $_POST['date_start'];
		$survey->date_end = $_POST['date_end'];

		$surveyId = $survey->create($user);  // This is where the survey is created
		if ($surveyId > 0) {
			echo "Survey created with ID: $surveyId<br>";

			if (!empty($_POST['questions'])) {
				foreach ($_POST['questions'] as $index => $question) {
					$text = $question['text'] ?? null;
					$type = $question['type'] ?? null;
					$optionTexts = $question['option_text'] ?? [];
					$conditionalLogic = $question['conditional_logic'] ?? [];
					$isMandatory = isset($question['mandatory']) ? 1 : 0;

					// Save the question and get the actual rowid
					$rowid = saveQuestion($surveyId, $text, $type, $optionTexts, $conditionalLogic, $isMandatory);

					if ($rowid > 0) {
						echo "Question created with rowid: $rowid<br>";

						// Update the conditional logic fields with the actual rowid
						echo "<script>
						if (!surveyQuestions[$index]) {
							surveyQuestions[$index] = {}; // Initialize the object if it doesn't exist
						}
						surveyQuestions[$index].rowid = $rowid;
						updateConditionalLogicOptions();
						</script>";

					} else {
						echo "Failed to create question: $text<br>";
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

	$question = new Question($db);

	$question->fk_survey = $surveyId;
	$question->question = $text;
	$question->type = $type;

	// Make sure the options are correctly formatted into a JSON array
	$question->options = json_encode(array_values($optionTexts), JSON_UNESCAPED_UNICODE);

	// Properly escape the JSON string for conditional logic
	$question->conditional_logic = $db->escape(json_encode($conditionalLogic, JSON_UNESCAPED_UNICODE));

	$question->position = 0;  // Position could be assigned dynamically
	$question->mandatory = $isMandatory ? 1 : 0;  // Store 1 if mandatory, else 0

	$result = $question->create();

	if ($result > 0) {
		return $question->rowid;
	} else {
		dol_print_error($db, "Failed to create question: $text. Error: " . $question->error);
		return -1;
	}
}





///noot that good script but has the delete groups / conditions good

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
                        <select name="questions[${questionIndex}][conditional_logic][0][field]" class="conditional-field">
                            <option value="">Select a question</option>
                            ${surveyQuestions.map(q => `<option value="${q.rowid}">${q.text || `Question ${q.rowid}`}</option>`).join('')}
                        </select>
                        <select name="questions[${questionIndex}][conditional_logic][0][logic]">
                            <option value="equal">is equal to</option>
                            <option value="not_equal">is not equal to</option>
                            <option value="greater_than">is greater than</option>
                            <option value="less_than">is less than</option>
                            <option value="contains">contains</option>
                            <option value="not_contains">does not contain</option>
                        </select>
                        <div class="conditional-value-wrapper">
                            <input type="text" name="questions[${questionIndex}][conditional_logic][0][value]" class="conditional-value" />
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
			}

			// Ensure that the selected rowid is properly stored in the field
			if (e.target.classList.contains('conditional-field')) {
				var selectedRowid = e.target.value;
				var conditionRow = e.target.closest('.condition-row');

				// Check if conditionRow and input field exist before setting value
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
				var conditionIndex = conditionGroup.querySelectorAll('.condition-row').length;

				// Update the name attributes for the new condition
				conditionRow.querySelectorAll('input, select').forEach(function(input) {
					var name = input.getAttribute('name');
					if (name) {
						input.setAttribute('name', name.replace(/\[\d+\]\[(\d+)\]/, `[$1][${conditionIndex}]`));
					}
				});

				// Insert the connector dropdown between conditions
				var connectorDropdown = document.createElement('select');
				connectorDropdown.name = `questions[${conditionGroup.closest('.question').dataset.rowid}][conditional_logic][${conditionIndex}][connector]`;
				connectorDropdown.innerHTML = `
                <option value="AND">AND</option>
                <option value="OR">OR</option>
            `;
				connectorDropdown.className = 'connector-dropdown';
				connectorDropdown.style.margin = '10px 0';

				conditionGroup.appendChild(connectorDropdown);
				conditionGroup.appendChild(conditionRow);
			}

			// Handle adding a new rule group
			if (e.target.classList.contains('add-rule-group-btn')) {
				var questionIndex = e.target.closest('.question').dataset.rowid;
				var ruleGroupIndex = e.target.closest('.conditional-logic').querySelectorAll('.condition-group').length;
				var ruleGroup = document.createElement('div');
				ruleGroup.className = 'condition-group';
				ruleGroup.style.border = '1px solid #ddd'; // Add border for visual distinction
				ruleGroup.style.padding = '10px'; // Add padding for better appearance
				ruleGroup.style.marginBottom = '10px'; // Space between rule groups

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

				// Insert the connector dropdown between groups
				var connectorDropdown = document.createElement('select');
				connectorDropdown.name = `questions[${questionIndex}][conditional_logic][${ruleGroupIndex}][group_connector]`;
				connectorDropdown.innerHTML = `
                <option value="AND">AND</option>
                <option value="OR">OR</option>
            `;
				connectorDropdown.className = 'connector-dropdown';
				connectorDropdown.style.margin = '10px 0';

				e.target.closest('.conditional-logic').insertBefore(connectorDropdown, e.target.closest('.add-rule-group'));

				e.target.closest('.conditional-logic').insertBefore(ruleGroup, e.target.closest('.add-rule-group'));

				updateConditionalLogicOptions();
			}

			// Handle deleting conditions
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



// perfect script but first group not being stored

<script>var nextRowid = <?php echo $lastRowid + 1; ?>; // Start from the last rowid + 1
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
                    <select name="questions[${questionIndex}][conditional_logic][0][field]" class="conditional-field">
                        <option value="">Select a question</option>
                        ${surveyQuestions.map(q => `<option value="${q.rowid}">${q.text || `Question ${q.rowid}`}</option>`).join('')}
                    </select>
                    <select name="questions[${questionIndex}][conditional_logic][0][logic]">
                        <option value="equal">is equal to</option>
                        <option value="not_equal">is not equal to</option>
                        <option value="greater_than">is greater than</option>
                        <option value="less_than">is less than</option>
                        <option value="contains">contains</option>
                        <option value="not_contains">does not contain</option>
                    </select>
                    <div class="conditional-value-wrapper">
                        <input type="text" name="questions[${questionIndex}][conditional_logic][0][value]" class="conditional-value" />
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
				var groupIndex = Array.prototype.indexOf.call(conditionGroup.parentElement.querySelectorAll('.condition-group'), conditionGroup);
				var conditionIndex = conditionGroup.querySelectorAll('.condition-row').length;

				conditionRow.querySelectorAll('input, select').forEach(function(input) {
					var name = input.getAttribute('name');
					if (name) {
						input.setAttribute('name', name.replace(/\[\d+\]\[\d+\]/, `[${groupIndex}][${conditionIndex}]`));
					}
				});

				var connectorDropdown = document.createElement('select');
				connectorDropdown.name = `questions[${conditionGroup.closest('.question').dataset.rowid}][conditional_logic][${groupIndex}][${conditionIndex}][connector]`;
				connectorDropdown.innerHTML = `
            <option value="AND">AND</option>
            <option value="OR">OR</option>
        `;
				connectorDropdown.className = 'connector-dropdown';
				connectorDropdown.style.margin = '10px 0';

				conditionGroup.appendChild(connectorDropdown);
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

				var connectorDropdown = document.createElement('select');
				connectorDropdown.name = `questions[${questionIndex}][conditional_logic][${ruleGroupIndex}][group_connector]`;
				connectorDropdown.innerHTML = `
            <option value="AND">AND</option>
            <option value="OR">OR</option>
        `;
				connectorDropdown.className = 'connector-dropdown';
				connectorDropdown.style.margin = '10px 0';

				conditionalLogicDiv.insertBefore(connectorDropdown, e.target.closest('.add-rule-group'));
				conditionalLogicDiv.insertBefore(ruleGroup, e.target.closest('.add-rule-group'));

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



/// code script that handles good the first group


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
				var groupIndex = Array.prototype.indexOf.call(conditionGroup.parentElement.querySelectorAll('.condition-group'), conditionGroup);
				var conditionIndex = conditionGroup.querySelectorAll('.condition-row').length;

				conditionRow.querySelectorAll('input, select').forEach(function(input) {
					var name = input.getAttribute('name');
					if (name) {
						input.setAttribute('name', name.replace(/\[\d+\]\[\d+\]/, `[${groupIndex}][${conditionIndex}]`));
					}
				});

				var connectorDropdown = document.createElement('select');
				connectorDropdown.name = `questions[${conditionGroup.closest('.question').dataset.rowid}][conditional_logic][${groupIndex}][${conditionIndex}][connector]`;
				connectorDropdown.innerHTML = `
            <option value="AND">AND</option>
            <option value="OR">OR</option>
        `;
				connectorDropdown.className = 'connector-dropdown';
				connectorDropdown.style.margin = '10px 0';

				conditionGroup.appendChild(connectorDropdown);
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

				var connectorDropdown = document.createElement('select');
				connectorDropdown.name = `questions[${questionIndex}][conditional_logic][${ruleGroupIndex}][group_connector]`;
				connectorDropdown.innerHTML = `
            <option value="AND">AND</option>
            <option value="OR">OR</option>
        `;
				connectorDropdown.className = 'connector-dropdown';
				connectorDropdown.style.margin = '10px 0';

				conditionalLogicDiv.insertBefore(connectorDropdown, e.target.closest('.add-rule-group'));
				conditionalLogicDiv.insertBefore(ruleGroup, e.target.closest('.add-rule-group'));

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




//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
nice code for survey participation :

<?php
global $langs, $db, $user;
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once 'class/Survey.class.php';
require_once 'class/question.class.php';

$langs->load("survey@survey");

$page_name = "Survey Participation";
llxHeader('', $langs->trans($page_name));

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
	// Fetch available surveys
	$sql = "SELECT rowid, title, description FROM llx_survey WHERE status = 1 AND NOW() BETWEEN date_start AND date_end";
	$resql = $db->query($sql);

	$surveys = [];
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$surveys[] = $obj;
		}
	}
}
?>

<div class="intropage">
	<h1><?php echo $langs->trans($page_name); ?></h1>
</div>

<?php if ($survey_id > 0 && !empty($questions)): ?>

	<!-- Survey Form -->
	<form method="post" action="submit_survey.php">
		<input type="hidden" name="survey_id" value="<?php echo $survey_id; ?>">

		<h2><?php echo $survey->title; ?></h2>
		<p><?php echo $survey->description; ?></p>

		<?php foreach ($questions as $question): ?>
			<div class="question" id="question-<?php echo $question->rowid; ?>" data-logic='<?php echo htmlspecialchars(stripslashes($question->conditional_logic), ENT_QUOTES, 'UTF-8'); ?>'>
				<label><?php echo $question->question; ?><?php if ($question->mandatory) echo ' *'; ?></label>
				<?php if ($question->type == 'text'): ?>
					<input type="text" name="response[<?php echo $question->rowid; ?>]" data-question-id="<?php echo $question->rowid; ?>" />
				<?php elseif ($question->type == 'textarea'): ?>
					<textarea name="response[<?php echo $question->rowid; ?>]" data-question-id="<?php echo $question->rowid; ?>"></textarea>
				<?php elseif ($question->type == 'radio' || $question->type == 'checkbox'):
					$options = json_decode($question->options, true);
					foreach ($options as $option): ?>
						<label>
							<input type="<?php echo $question->type; ?>" name="response[<?php echo $question->rowid; ?>][]" value="<?php echo $option; ?>" data-question-id="<?php echo $question->rowid; ?>" />
							<?php echo $option; ?>
						</label>
					<?php endforeach; ?>
				<?php elseif ($question->type == 'select'):
					$options = json_decode($question->options, true); ?>
					<select name="response[<?php echo $question->rowid; ?>]" data-question-id="<?php echo $question->rowid; ?>">
						<?php foreach ($options as $option): ?>
							<option value="<?php echo $option; ?>"><?php echo $option; ?></option>
						<?php endforeach; ?>
					</select>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>

		<div class="center">
			<button type="submit" class="button">Submit Survey</button>
		</div>
	</form>

	<script>
		document.addEventListener("DOMContentLoaded", function() {
			const responses = {};

			function updateResponse(key, value) {
				responses[key] = value;
				evaluateLogic();
			}

			function evaluateLogic() {
				const questions = document.querySelectorAll('.question');
				questions.forEach(function(question) {
					const jsonString = question.getAttribute('data-logic');
					console.log("Evaluating logic for:", question.id, jsonString);
					try {
						const logicData = JSON.parse(jsonString);
						if (logicData.groups && logicData.groups.length > 0) {
							const showQuestion = evaluateConditionalLogic(logicData);
							question.style.display = showQuestion ? "block" : "none";
							console.log("Question", question.id, "display:", question.style.display);
						} else {
							question.style.display = "block"; // Show if no logic
						}
					} catch (e) {
						console.error("Error parsing JSON logic for question", question.id, e);
						question.style.display = "none"; // Hide question if logic cannot be parsed
					}
				});
			}

			function evaluateConditionalLogic(logicData) {
				if (!logicData.groups || logicData.groups.length === 0) return true;

				return logicData.groups.some(function(group) {
					let groupResult = group.conditions.reduce(function(result, condition) {
						const fieldId = condition.field;
						const questionValue = responses[fieldId];

						let conditionMet = false;

						if (questionValue === undefined || questionValue === null) return false;

						switch (condition.logic) {
							case 'equal':
								conditionMet = (Array.isArray(questionValue) ? questionValue.includes(condition.value) : questionValue == condition.value);
								break;
							case 'not_equal':
								conditionMet = (Array.isArray(questionValue) ? !questionValue.includes(condition.value) : questionValue != condition.value);
								break;
							case 'greater_than':
								conditionMet = (parseFloat(questionValue) > parseFloat(condition.value));
								break;
							case 'less_than':
								conditionMet = (parseFloat(questionValue) < parseFloat(condition.value));
								break;
							case 'contains':
								conditionMet = (Array.isArray(questionValue) ? questionValue.some(val => val.includes(condition.value)) : questionValue.includes(condition.value));
								break;
							case 'not_contains':
								conditionMet = (Array.isArray(questionValue) ? questionValue.every(val => !val.includes(condition.value)) : !questionValue.includes(condition.value));
								break;
							default:
								console.warn(`Unknown condition logic "${condition.logic}"`);
								break;
						}

						if (condition.condition_connector === 'AND') {
							return result && conditionMet;
						} else {
							return result || conditionMet;
						}
					}, true);

					if (group.group_connector === 'AND') {
						return groupResult;
					} else {
						return groupResult || false;
					}
				});
			}

			const inputs = document.querySelectorAll('input, select');
			inputs.forEach(function(input) {
				input.addEventListener('change', function() {
					const questionId = this.getAttribute('data-question-id');
					if (this.type === 'checkbox' || this.type === 'radio') {
						const checkedValues = Array.from(document.querySelectorAll(`[name="response[${questionId}]"]:checked`))
							.map(el => el.value);
						updateResponse(questionId, checkedValues);
					} else {
						updateResponse(questionId, this.value);
					}
				});
			});

			evaluateLogic(); // Initial evaluation on page load
		});
	</script>

<?php else: ?>

	<!-- List of Available Surveys -->
	<?php if (!empty($surveys)): ?>
		<ul>
			<?php foreach ($surveys as $survey): ?>
				<li>
					<h3><?php echo $survey->title; ?></h3>
					<p><?php echo $survey->description; ?></p>
					<a href="surveyparticipation.php?survey_id=<?php echo $survey->rowid; ?>" class="button">Participate</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php else: ?>
		<p>No surveys available at the moment.</p>
	<?php endif; ?>

<?php endif; ?>

<?php
llxFooter();
?>


**************************************************************************************************
	very good version:::surveyparticipation



<?php
global $langs, $db, $user;
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once 'class/Survey.class.php';
require_once 'class/question.class.php';

$langs->load("survey@survey");

$page_name = "Survey Participation";
llxHeader('', $langs->trans($page_name));

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
	// Fetch available surveys
	$sql = "SELECT rowid, title, description FROM llx_survey WHERE status = 1 AND NOW() BETWEEN date_start AND date_end";
	$resql = $db->query($sql);

	$surveys = [];
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$surveys[] = $obj;
		}
	}
}
?>

<div class="intropage">
	<h1><?php echo $langs->trans($page_name); ?></h1>
</div>

<?php if ($survey_id > 0 && !empty($questions)): ?>

	<!-- Survey Form -->
	<form method="post" action="">
		<input type="hidden" name="survey_id" value="<?php echo $survey_id; ?>">

		<h2><?php echo $survey->title; ?></h2>
		<p><?php echo $survey->description; ?></p>

		<?php foreach ($questions as $question): ?>
			<div class="question" id="question-<?php echo $question->rowid; ?>" data-logic='<?php echo htmlspecialchars(stripslashes($question->conditional_logic), ENT_QUOTES, 'UTF-8'); ?>'>
				<label><?php echo $question->question; ?><?php if ($question->mandatory) echo ' *'; ?></label>

				<?php if ($question->type == 'text'): ?>
					<input type="text" name="response[<?php echo $question->rowid; ?>]" data-question-id="<?php echo $question->rowid; ?>"/>

				<?php elseif ($question->type == 'textarea'): ?>
					<textarea name="response[<?php echo $question->rowid; ?>]" data-question-id="<?php echo $question->rowid; ?>"></textarea>

				<?php elseif ($question->type == 'checkbox'):
					$options = json_decode($question->options, true);
					foreach ($options as $option): ?>
						<label>
							<input type="<?php echo $question->type; ?>" name="response[<?php echo $question->rowid; ?>][]" value="<?php echo $option; ?>" data-question-id="<?php echo $question->rowid; ?>"/>
							<?php echo $option; ?>
						</label>
					<?php endforeach; ?>


				<?php elseif ($question->type == 'radio'):
					$options = json_decode($question->options, true);
					foreach ($options as $option): ?>
						<label>
							<input type="<?php echo $question->type; ?>" name="response[<?php echo $question->rowid; ?>]" value="<?php echo $option; ?>" data-question-id="<?php echo $question->rowid; ?>"/>
							<?php echo $option; ?>
						</label>
					<?php endforeach; ?>


				<?php elseif ($question->type == 'select'):
					$options = json_decode($question->options, true); ?>
					<select name="response[<?php echo $question->rowid; ?>]" data-question-id="<?php echo $question->rowid; ?>">
						<?php foreach ($options as $option): ?>
							<option value="<?php echo $option; ?>"><?php echo $option; ?></option>
						<?php endforeach; ?>
					</select>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>

		<div class="center">
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
						if (logicData.groups && logicData.groups.length > 0) {
							const showQuestion = evaluateConditionalLogic(logicData);
							question.style.display = showQuestion ? "block" : "none";
							console.log("Question", question.id, "display:", question.style.display);
						} else {
							question.style.display = "block"; // Show if no logic
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

				return logicData.groups.some(function (group) {
					let groupResult = group.conditions.reduce(function (result, condition) {
						const fieldId = condition.field;
						const questionValue = responses[fieldId];

						let conditionMet = false;

						if (questionValue === undefined || questionValue === null) return false;

						switch (condition.logic) {
							case 'equal':
								conditionMet = (Array.isArray(questionValue) ? questionValue.includes(condition.value) : questionValue == condition.value);
								break;
							case 'not_equal':
								conditionMet = (Array.isArray(questionValue) ? !questionValue.includes(condition.value) : questionValue != condition.value);
								break;
							case 'greater_than':
								conditionMet = (parseFloat(questionValue) > parseFloat(condition.value));
								break;
							case 'less_than':
								conditionMet = (parseFloat(questionValue) < parseFloat(condition.value));
								break;
							case 'contains':
								conditionMet = (Array.isArray(questionValue) ? questionValue.some(val => val.includes(condition.value)) : questionValue.includes(condition.value));
								break;
							case 'not_contains':
								conditionMet = (Array.isArray(questionValue) ? questionValue.every(val => !val.includes(condition.value)) : !questionValue.includes(condition.value));
								break;
							default:
								console.warn(`Unknown condition logic "${condition.logic}"`);
								break;
						}

						if (condition.condition_connector === 'AND') {
							return result && conditionMet;
						} else {
							return result || conditionMet;
						}
					}, true);

					if (group.group_connector === 'AND') {
						return groupResult;
					} else {
						return groupResult || false;
					}
				});
			}

			// Initial evaluation of conditional logic
			evaluateLogic();
		});

	</script>

<?php else: ?>

	<!-- List of Available Surveys -->
	<?php if (!empty($surveys)): ?>
		<ul>
			<?php foreach ($surveys as $survey): ?>
				<li>
					<h3><?php echo $survey->title; ?></h3>
					<p><?php echo $survey->description; ?></p>
					<a href="surveyparticipation.php?survey_id=<?php echo $survey->rowid; ?>" class="button">Participate</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php else: ?>
		<p>No surveys available at the moment.</p>
	<?php endif; ?>

<?php endif; ?>

<?php
llxFooter();
?>


******************************************************************************************************
 *
 * this coode is woow but problem with coonectors between conditions and between groups
 */



<?php
global $langs, $db, $user;
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once 'class/Survey.class.php';
require_once 'class/question.class.php';

$langs->load("survey@survey");

$page_name = "Survey Participation";
llxHeader('', $langs->trans($page_name));

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
	// Fetch available surveys
	$sql = "SELECT rowid, title, description FROM llx_survey WHERE status = 1 AND NOW() BETWEEN date_start AND date_end";
	$resql = $db->query($sql);

	$surveys = [];
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$surveys[] = $obj;
		}
	}
}
?>

<div class="intropage">
	<h1><?php echo $langs->trans($page_name); ?></h1>
</div>

<?php if ($survey_id > 0 && !empty($questions)): ?>

	<!-- Survey Form -->
	<form method="post" action="">
		<input type="hidden" name="survey_id" value="<?php echo $survey_id; ?>">

		<h2><?php echo $survey->title; ?></h2>
		<p><?php echo $survey->description; ?></p>

		<?php foreach ($questions as $question): ?>
			<div class="question" id="question-<?php echo $question->rowid; ?>" data-logic='<?php echo htmlspecialchars(stripslashes($question->conditional_logic), ENT_QUOTES, 'UTF-8'); ?>'>
				<label><?php echo $question->question; ?><?php if ($question->mandatory) echo ' *'; ?></label>

				<?php if ($question->type == 'text'): ?>
					<input type="text" name="response[<?php echo $question->rowid; ?>]" data-question-id="<?php echo $question->rowid; ?>"/>

				<?php elseif ($question->type == 'textarea'): ?>
					<textarea name="response[<?php echo $question->rowid; ?>]" data-question-id="<?php echo $question->rowid; ?>"></textarea>

				<?php elseif ($question->type == 'checkbox'):
					$options = json_decode($question->options, true);
					foreach ($options as $option): ?>
						<label>
							<input type="<?php echo $question->type; ?>" name="response[<?php echo $question->rowid; ?>][]" value="<?php echo $option; ?>" data-question-id="<?php echo $question->rowid; ?>"/>
							<?php echo $option; ?>
						</label>
					<?php endforeach; ?>


				<?php elseif ($question->type == 'radio'):
					$options = json_decode($question->options, true);
					foreach ($options as $option): ?>
						<label>
							<input type="<?php echo $question->type; ?>" name="response[<?php echo $question->rowid; ?>]" value="<?php echo $option; ?>" data-question-id="<?php echo $question->rowid; ?>"/>
							<?php echo $option; ?>
						</label>
					<?php endforeach; ?>


				<?php elseif ($question->type == 'select'):
					$options = json_decode($question->options, true); ?>
					<select name="response[<?php echo $question->rowid; ?>]" data-question-id="<?php echo $question->rowid; ?>">
						<?php foreach ($options as $option): ?>
							<option value="<?php echo $option; ?>"><?php echo $option; ?></option>
						<?php endforeach; ?>
					</select>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>

		<div class="center">
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

						// Only evaluate conditional logic if there is a valid logic data
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

				return logicData.groups.some(function (group) {
					let groupResult = group.conditions.reduce(function (result, condition, index, conditions) {
						const fieldId = condition.field;
						const questionValue = responses[fieldId];

						let conditionMet = false;

						if (questionValue === undefined || questionValue === null) return false;

						// Evaluate condition based on logic type
						switch (condition.logic) {
							case 'equal':
								conditionMet = (Array.isArray(questionValue) ? questionValue.includes(condition.value) : questionValue == condition.value);
								break;
							case 'not_equal':
								conditionMet = (Array.isArray(questionValue) ? !questionValue.includes(condition.value) : questionValue != condition.value);
								break;
							case 'greater_than':
								conditionMet = (parseFloat(questionValue) > parseFloat(condition.value));
								break;
							case 'less_than':
								conditionMet = (parseFloat(questionValue) < parseFloat(condition.value));
								break;
							case 'contains':
								conditionMet = (Array.isArray(questionValue) ? questionValue.some(val => val.includes(condition.value)) : questionValue.includes(condition.value));
								break;
							case 'not_contains':
								conditionMet = (Array.isArray(questionValue) ? questionValue.every(val => !val.includes(condition.value)) : !questionValue.includes(condition.value));
								break;
							default:
								console.warn(`Unknown condition logic "${condition.logic}"`);
								break;
						}

						// Apply condition connector if not null
						if (condition.condition_connector === 'AND') {
							return result && conditionMet;
						} else if (condition.condition_connector === 'OR') {
							return result || conditionMet;
						} else {
							// No connector between conditions
							return conditionMet;
						}
					}, true);

					// Apply group connector if not null
					if (group.group_connector === 'AND') {
						return groupResult;
					} else if (group.group_connector === 'OR') {
						return groupResult || false;
					} else {
						// No connector between groups
						return groupResult;
					}
				});
			}



			// Initial evaluation of conditional logic
			evaluateLogic();
		});

	</script>

<?php else: ?>

	<!-- List of Available Surveys -->
	<?php if (!empty($surveys)): ?>
		<ul>
			<?php foreach ($surveys as $survey): ?>
				<li>
					<h3><?php echo $survey->title; ?></h3>
					<p><?php echo $survey->description; ?></p>
					<a href="surveyparticipation.php?survey_id=<?php echo $survey->rowid; ?>" class="button">Participate</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php else: ?>
		<p>No surveys available at the moment.</p>
	<?php endif; ?>

<?php endif; ?>

<?php
llxFooter();
?>

************************************************************************************************
good last version for surveyparticipation ::

<?php
global $langs, $db, $user;
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once 'class/Survey.class.php';
require_once 'class/question.class.php';

$langs->load("survey@survey");

$page_name = "Survey Participation";
llxHeader('', $langs->trans($page_name));

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
	// Fetch available surveys
	$sql = "SELECT rowid, title, description FROM llx_survey WHERE status = 1 AND NOW() BETWEEN date_start AND date_end";
	$resql = $db->query($sql);

	$surveys = [];
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$surveys[] = $obj;
		}
	}
}
?>

µµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµµ
VERY GOOD FORM FOR THE SURVEY PARTICIPATION

<?php
global $langs, $db, $user;
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once 'class/Survey.class.php';
require_once 'class/question.class.php';

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

		// Optionally, unset the token after processing
		unset($_SESSION['newtoken']);

		// Example user and survey rowids
		$userRowId =$fk_user; // Replace with the actual user rowid
		$surveyRowId = $survey_id; // Replace with the actual survey rowid

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
        }, 4000); // Delay redirect to allow message to be seen
    });
       </script>";
		exit; // Ensure the script stops after redirection
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
		// Fetch available surveys
		$sql = "SELECT rowid, title, description FROM llx_survey WHERE status = 1 AND NOW() BETWEEN date_start AND date_end";
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
	<!-- Survey Form -->
	<form method="post" action="">
		<input type="hidden" name="survey_id" value="<?php echo $survey_id; ?>">
		<input type="hidden" name="token" value="<?php echo $token; ?>"> <!-- CSRF token -->

		<h2><?php echo $survey->title; ?></h2>
		<p><?php echo $survey->description; ?></p>

		<?php foreach ($questions as $question): ?>
			<div class="question" id="question-<?php echo $question->rowid; ?>" data-logic='<?php echo htmlspecialchars(stripslashes($question->conditional_logic), ENT_QUOTES, 'UTF-8'); ?>'>
				<label><?php echo $question->question; ?><?php if ($question->mandatory) echo ' *'; ?></label>

				<?php if ($question->type == 'text'): ?>
					<input type="text" name="response[<?php echo $question->rowid; ?>]" data-question-id="<?php echo $question->rowid; ?>"/>

				<?php elseif ($question->type == 'textarea'): ?>
					<textarea name="response[<?php echo $question->rowid; ?>]" data-question-id="<?php echo $question->rowid; ?>"></textarea>

				<?php elseif ($question->type == 'checkbox'):
					$options = json_decode($question->options, true);
					foreach ($options as $option): ?>
						<label>
							<input type="<?php echo $question->type; ?>" name="response[<?php echo $question->rowid; ?>][]" value="<?php echo $option; ?>" data-question-id="<?php echo $question->rowid; ?>"/>
							<?php echo $option; ?>
						</label>
					<?php endforeach; ?>

				<?php elseif ($question->type == 'radio'):
					$options = json_decode($question->options, true);
					foreach ($options as $option): ?>
						<label>
							<input type="<?php echo $question->type; ?>" name="response[<?php echo $question->rowid; ?>]" value="<?php echo $option; ?>" data-question-id="<?php echo $question->rowid; ?>"/>
							<?php echo $option; ?>
						</label>
					<?php endforeach; ?>

				<?php elseif ($question->type == 'select'):
					$options = json_decode($question->options, true); ?>
					<select name="response[<?php echo $question->rowid; ?>]" data-question-id="<?php echo $question->rowid; ?>">
						<?php foreach ($options as $option): ?>
							<option value="<?php echo $option; ?>"><?php echo $option; ?></option>
						<?php endforeach; ?>
					</select>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>

		<div class="center" id="form-button">
			<button type="submit" class="button" >Submit Survey</button>
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
				<th><?php echo $langs->trans('Title'); ?></th>
				<th><?php echo $langs->trans('Description'); ?></th>
				<th><?php echo $langs->trans('Action'); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ($surveys as $survey): ?>
				<tr>
					<td><?php echo htmlspecialchars($survey->title); ?></td>
					<td><?php echo htmlspecialchars($survey->description); ?></td>
					<td>
						<a href="surveyparticipation.php?survey_id=<?php echo $survey->rowid; ?>" class="button"><?php echo $langs->trans('Participate'); ?></a>
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



$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$

<?php
global $langs, $db, $user;
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once 'class/Survey.class.php';
require_once 'class/question.class.php';

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

		// Optionally, unset the token after processing
		unset($_SESSION['newtoken']);

		// Example user and survey rowids
		$userRowId =$fk_user; // Replace with the actual user rowid
		$surveyRowId = $survey_id; // Replace with the actual survey rowid

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
        }, 1000); // Delay redirect to allow message to be seen
    });
       </script>";
		exit; // Ensure the script stops after redirection
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
	<!-- Survey Form -->
	<form method="post" action="">
		<input type="hidden" name="survey_id" value="<?php echo $survey_id; ?>">
		<input type="hidden" name="token" value="<?php echo $token; ?>"> <!-- CSRF token -->

		<h2><?php echo $survey->title; ?></h2>
		<p><?php echo $survey->description; ?></p>

		<?php foreach ($questions as $question): ?>
			<div class="question" id="question-<?php echo $question->rowid; ?>" data-logic='<?php echo htmlspecialchars(stripslashes($question->conditional_logic), ENT_QUOTES, 'UTF-8'); ?>'>
				<label><?php echo $question->question; ?><?php if ($question->mandatory) echo ' *'; ?></label>

				<?php if ($question->type == 'text'): ?>
					<input type="text" name="response[<?php echo $question->rowid; ?>]" data-question-id="<?php echo $question->rowid; ?>"/>

				<?php elseif ($question->type == 'textarea'): ?>
					<textarea name="response[<?php echo $question->rowid; ?>]" data-question-id="<?php echo $question->rowid; ?>"></textarea>

				<?php elseif ($question->type == 'checkbox'):
					$options = json_decode($question->options, true);
					foreach ($options as $option): ?>
						<label>
							<input type="<?php echo $question->type; ?>" name="response[<?php echo $question->rowid; ?>][]" value="<?php echo $option; ?>" data-question-id="<?php echo $question->rowid; ?>"/>
							<?php echo $option; ?>
						</label>
					<?php endforeach; ?>

				<?php elseif ($question->type == 'radio'):
					$options = json_decode($question->options, true);
					foreach ($options as $option): ?>
						<label>
							<input type="<?php echo $question->type; ?>" name="response[<?php echo $question->rowid; ?>]" value="<?php echo $option; ?>" data-question-id="<?php echo $question->rowid; ?>"/>
							<?php echo $option; ?>
						</label>
					<?php endforeach; ?>

				<?php elseif ($question->type == 'select'):
					$options = json_decode($question->options, true); ?>
					<select name="response[<?php echo $question->rowid; ?>]" data-question-id="<?php echo $question->rowid; ?>">
						<?php foreach ($options as $option): ?>
							<option value="<?php echo $option; ?>"><?php echo $option; ?></option>
						<?php endforeach; ?>
					</select>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>

		<div class="center" id="form-button">
			<button type="submit" class="button" >Submit Survey</button>
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





