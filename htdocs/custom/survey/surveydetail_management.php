<?php

global $db, $langs, $user;
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once 'class/Survey.class.php';
require_once 'class/Question.class.php';

$langs->load("admin");
$langs->load("survey@survey");

$id = GETPOST('id', 'int');

if (!$id) {
	header('Location: surveylist_management.php');
	exit;
}

$survey = new Survey($db);
$survey->fetch($id);

$question = new Question($db);
$questions = $question->fetchAllBySurvey($id);

$token = newToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (GETPOST('token') !== $_SESSION['newtoken']) { // Validate CSRF token
		accessforbidden();
	}

	// Update survey details
	$survey->title = GETPOST('title', 'alpha');
	$survey->description = GETPOST('description', 'alpha');
	$survey->date_start = GETPOST('date_start', 'alpha');
	$survey->date_end = GETPOST('date_end', 'alpha');
	$survey->status = GETPOST('status', 'int');

	// Update survey in the database
	$survey->update($id, $user);

	// Update questions
	foreach ($_POST['questions'] as $question_id => $question_data) {
		$question = new Question($db);
		if ($question->fetch($question_id)) {
			$question->question = $question_data['question'];
			$question->type = $question_data['type'];

			$options = array_map('trim', explode("\n", $question_data['options']));
			$question->options = json_encode($options);

			$question->conditional_logic = $question->conditional_logic ?: '';

			$question->mandatory = isset($question_data['mandatory']) ? 1 : 0;
			$question->update();  // Save changes to the database
		}
	}

	header('Location: surveylist_management.php');
	exit;
}

llxHeader('', $langs->trans("Survey Detail"));

// Link the CSS file
echo '<link rel="stylesheet" type="text/css" href="css/surveydetail_management.css">';
?>

<h1><?php echo $langs->trans("Survey Detail"); ?></h1>

<form method="post" action="surveydetail_management.php?id=<?php echo $id; ?>" class="survey-form">
	<input type="hidden" name="token" value="<?php echo $token; ?>">

	<div class="form-group">
		<label for="title"><?php echo $langs->trans("Title"); ?></label>
		<input type="text" name="title" value="<?php echo $survey->title; ?>" />
	</div>
	<div class="form-group">
		<label for="description"><?php echo $langs->trans("Description"); ?></label>
		<textarea name="description"><?php echo $survey->description; ?></textarea>
	</div>
	<div class="form-group">
		<label for="date_start"><?php echo $langs->trans("StartDate"); ?></label>
		<input type="date" name="date_start" value="<?php echo date('Y-m-d', strtotime($survey->date_start)); ?>"/>
	</div>
	<div class="form-group">
		<label for="date_end"><?php echo $langs->trans("EndDate"); ?></label>
		<input type="date" name="date_end" value="<?php echo date('Y-m-d', strtotime($survey->date_end)); ?>"/>
	</div>
	<div class="form-group">
		<label for="status"><?php echo $langs->trans("Status"); ?></label>
		<select name="status">
			<option value="1" <?php echo $survey->status == 1 ? 'selected' : ''; ?>>Enabled</option>
			<option value="0" <?php echo $survey->status == 0 ? 'selected' : ''; ?>>Disabled</option>
			<option value="2" <?php echo $survey->status == 2 ? 'selected' : ''; ?>>Terminated</option>
		</select>
	</div>

	<h2><?php echo $langs->trans("Questions"); ?></h2>
	<?php if (count($questions) > 0): ?>
		<?php foreach ($questions as $question): ?>
			<div class="question">
				<div class="form-group">
					<label for="question_<?php echo $question->id; ?>"><?php echo $langs->trans("Question"); ?></label>
					<input type="text" name="questions[<?php echo $question->id; ?>][question]" value="<?php echo $question->question; ?>">
				</div>
				<div class="form-group">
					<label for="type_<?php echo $question->id; ?>"><?php echo $langs->trans("Type"); ?></label>
					<select name="questions[<?php echo $question->id; ?>][type]" class="question-type" required>
						<option value="text" <?php echo $question->type == 'text' ? 'selected' : ''; ?>>Short Answer</option>
						<option value="textarea" <?php echo $question->type == 'textarea' ? 'selected' : ''; ?>>Paragraph</option>
						<option value="radio" <?php echo $question->type == 'radio' ? 'selected' : ''; ?>>Multiple Choice</option>
						<option value="checkbox" <?php echo $question->type == 'checkbox' ? 'selected' : ''; ?>>Checkboxes</option>
						<option value="select" <?php echo $question->type == 'select' ? 'selected' : ''; ?>>Dropdown</option>
						<option value="file" <?php echo $question->type == 'file' ? 'selected' : ''; ?>>File Upload</option>
						<option value="linear_scale" <?php echo $question->type == 'linear_scale' ? 'selected' : ''; ?>>Linear Scale</option>
						<option value="multiple_choice_grid" <?php echo $question->type == 'multiple_choice_grid' ? 'selected' : ''; ?>>Multiple Choice Grid</option>
						<option value="checkbox_grid" <?php echo $question->type == 'checkbox_grid' ? 'selected' : ''; ?>>Checkbox Grid</option>
						<option value="date" <?php echo $question->type == 'date' ? 'selected' : ''; ?>>Date</option>
						<option value="time" <?php echo $question->type == 'time' ? 'selected' : ''; ?>>Time</option>
					</select>
				</div>
				<div class="form-group options" style="<?php echo in_array($question->type, ['radio', 'checkbox', 'select', 'multiple_choice_grid', 'checkbox_grid']) ? '' : 'display:none;'; ?>">
					<label for="options_<?php echo $question->id; ?>"><?php echo $langs->trans("Options"); ?></label>
					<textarea name="questions[<?php echo $question->id; ?>][options]"><?php echo implode("\n", json_decode($question->options, true)); ?></textarea>
				</div>
				<div class="form-group">
					<label for="mandatory_<?php echo $question->id; ?>"><?php echo $langs->trans("Mandatory"); ?></label>
					<input type="checkbox" name="questions[<?php echo $question->id; ?>][mandatory]" value="1" <?php echo $question->mandatory ? 'checked' : ''; ?>>
				</div>
			</div>
		<?php endforeach; ?>
	<?php else: ?>
		<p><?php echo $langs->trans("NoQuestionsFound"); ?></p>
	<?php endif; ?>

	<div class="center">
		<input type="submit" class="button" value="<?php echo $langs->trans("UpdateSurvey"); ?>">
	</div>
</form>

<script>
	document.querySelectorAll('.question-type').forEach(function(select) {
		select.addEventListener('change', function() {
			var optionsDiv = this.closest('.question').querySelector('.options');
			if (['radio', 'checkbox', 'select', 'multiple_choice_grid', 'checkbox_grid'].includes(this.value)) {
				optionsDiv.style.display = 'block';
			} else {
				optionsDiv.style.display = 'none';
			}
		});
	});
</script>

<?php
llxFooter();
$db->close();
?>
