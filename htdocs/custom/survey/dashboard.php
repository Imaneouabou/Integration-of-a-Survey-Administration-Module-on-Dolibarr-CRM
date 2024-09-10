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

function saveQuestion($surveyId, $text, $type, $optionTexts, $conditionalLogic, $isMandatory) {
	global $db;

	$question = new Question($db);

	$question->fk_survey = $surveyId;
	$question->question = $text;
	$question->type = $type;
	$question->options = json_encode($optionTexts, JSON_UNESCAPED_UNICODE);
	$question->conditional_logic = json_encode($conditionalLogic, JSON_UNESCAPED_UNICODE);
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

		$surveyId = $survey->create($user);

		if ($surveyId > 0) {
			if (!empty($_POST['questions'])) {
				foreach ($_POST['questions'] as $question) {
					$text = $question['text'] ?? null;
					$type = $question['type'] ?? null;
					$optionTexts = $question['option_text'] ?? [];
					$conditionalLogic = [];

					if (!empty($question['conditional_logic'])) {
						$conditionalLogic['field'] = $question['conditional_logic']['field'] ?? [];
						$conditionalLogic['logic'] = $question['conditional_logic']['logic'] ?? [];
						$conditionalLogic['value'] = $question['conditional_logic']['value'] ?? [];
					}

					$isMandatory = isset($question['mandatory']) ? 1 : 0;

					saveQuestion($surveyId, $text, $type, $optionTexts, $conditionalLogic, $isMandatory);
				}
			} else {
				dol_print_error($db, "No questions provided.");
			}
		} else {
			dol_print_error($db, "Failed to create survey. Error: " . $survey->error);
		}
	} else {
		echo "Title and description are required.";
	}
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
	document.getElementById('add-question').addEventListener('click', function() {
		var questionIndex = document.querySelectorAll('.question').length;
		var newQuestion = document.createElement('div');
		newQuestion.className = 'question';
		newQuestion.dataset.index = questionIndex; // Add index for tracking
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
                        <select name="questions[${questionIndex}][conditional_logic][field][]" class="conditional-field">
                            <!-- Options will be dynamically added here -->
                        </select>
                        <select name="questions[${questionIndex}][conditional_logic][logic][]">
                            <option value="equal">is equal to</option>
                            <option value="not_equal">is not equal to</option>
                            <option value="greater_than">is greater than</option>
                            <option value="less_than">is less than</option>
                            <option value="contains">contains</option>
                            <option value="not_contains">does not contain</option>
                        </select>
                        <div class="conditional-value-wrapper">
                            <input type="text" name="questions[${questionIndex}][conditional_logic][value][]" class="conditional-value" />
                        </div>
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
		updateConditionalLogicOptions();
	});

	function updateConditionalLogicOptions() {
		var questions = document.querySelectorAll('.question');
		questions.forEach(function(question, index) {
			var fieldSelects = question.querySelectorAll('select[name^="questions["][name$="[conditional_logic][field][]"]');
			fieldSelects.forEach(function(select) {
				select.innerHTML = ''; // Clear previous options
				questions.forEach(function(q, i) {
					if (i < index) { // Only add previous questions
						var optionText = q.querySelector('input[name^="questions["][name$="[text]"]').value;
						var option = document.createElement('option');
						option.value = i;
						option.text = optionText;
						select.appendChild(option);
					}
				});
			});
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

		if (e.target.classList.contains('conditional-field')) {
			var selectedFieldIndex = e.target.value;
			var valueWrapper = e.target.closest('.condition-row').querySelector('.conditional-value-wrapper');
			valueWrapper.innerHTML = ''; // Clear previous inputs

			var selectedQuestion = document.querySelector(`.question[data-index="${selectedFieldIndex}"]`);
			var selectedQuestionType = selectedQuestion.querySelector('.question-type').value;
			var options = selectedQuestion.querySelectorAll('.options .option input');

			if (['radio', 'checkbox', 'select'].includes(selectedQuestionType)) {
				var valueSelect = document.createElement('select');
				valueSelect.name = e.target.name.replace('field', 'value');

				options.forEach(function(option) {
					var opt = document.createElement('option');
					opt.value = option.value;
					opt.text = option.value;
					valueSelect.appendChild(opt);
				});

				valueWrapper.appendChild(valueSelect);
			} else {
				var valueInput = document.createElement('input');
				valueInput.type = 'text';
				valueInput.name = e.target.name.replace('field', 'value');
				valueWrapper.appendChild(valueInput);
			}
		}
	});

	document.addEventListener('click', function(e) {
		if (e.target.classList.contains('add-option')) {
			var optionsDiv = e.target.closest('.options');
			var questionIndex = optionsDiv.closest('.question').dataset.index;
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
			updateConditionalLogicOptions();
		}

		if (e.target.classList.contains('duplicate-question')) {
			var questionDiv = e.target.closest('.question');
			var clonedQuestion = questionDiv.cloneNode(true);
			var newIndex = document.querySelectorAll('.question').length;
			clonedQuestion.dataset.index = newIndex;
			clonedQuestion.querySelectorAll('input, select').forEach(function(input) {
				input.name = input.name.replace(/\[\d+\]/, `[${newIndex}]`);
			});
			document.getElementById('questions').appendChild(clonedQuestion);
			updateConditionalLogicOptions();
		}

		if (e.target.classList.contains('and-condition')) {
			var conditionRow = e.target.closest('.condition-row').cloneNode(true);
			conditionRow.querySelector('.and-condition').remove();
			e.target.closest('.condition-group').appendChild(conditionRow);
		}

		if (e.target.classList.contains('add-rule-group-btn')) {
			var questionIndex = e.target.closest('.question').dataset.index;
			var ruleGroup = document.createElement('div');
			ruleGroup.className = 'condition-group';
			ruleGroup.innerHTML = `
                <label>Show this field group if:</label>
                <div class="condition-row">
                    <select name="questions[${questionIndex}][conditional_logic][field][]" class="conditional-field">
                        <!-- Options will be dynamically added here -->
                    </select>
                    <select name="questions[${questionIndex}][conditional_logic][logic][]">
                        <option value="equal">is equal to</option>
                        <option value="not_equal">is not equal to</option>
                        <option value="greater_than">is greater than</option>
                        <option value="less_than">is less than</option>
                        <option value="contains">contains</option>
                        <option value="not_contains">does not contain</option>
                    </select>
                    <div class="conditional-value-wrapper">
                        <input type="text" name="questions[${questionIndex}][conditional_logic][value][]" class="conditional-value" />
                    </div>
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
