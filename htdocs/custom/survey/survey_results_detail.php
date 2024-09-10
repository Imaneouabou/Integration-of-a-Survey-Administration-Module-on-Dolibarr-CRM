<?php
global $langs, $db, $user;
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once 'class/Survey.class.php';
require_once 'class/question.class.php';
require_once 'class/response.class.php';

$langs->load("survey@survey");

$id = GETPOST('id', 'int');

if (!$id) {
	header('Location: survey_results_list.php');
	exit;
}

$survey = new Survey($db);
$survey->fetch($id);

$question = new Question($db);
$questions = $question->fetchAllBySurvey($id);

$response = new Response($db);

// Corrected query to get the total number of submissions
$totalSubmissionsQuery = "
    SELECT COUNT(DISTINCT date_creation) AS total_submissions
    FROM llx_survey_response
    WHERE fk_survey_question IN (
        SELECT rowid
        FROM llx_survey_question
        WHERE fk_survey = " . (int)$id . "
    )
";
$totalSubmissionsResult = $db->query($totalSubmissionsQuery);
$totalSubmissions = $totalSubmissionsResult->fetch_object()->total_submissions;

llxHeader('', $langs->trans("SurveyResultsDetail"));

// Link the CSS file with versioning
echo '<link rel="stylesheet" type="text/css" href="css/survey_results_detail.css?v='.time().'">';

echo '<h1>'.$langs->trans("Survey Results Detail").'</h1>';
echo '<h2>'.$survey->title.'</h2>';
echo '<p class="total-submissions"><strong style="color: red">'.$langs->trans("Total Submissions").': '.$totalSubmissions.'</strong></p>';

echo '<div class="survey-results">';
foreach ($questions as $q) {
	echo '<div class="question-result">';
	echo '<h3>'.$q->question.'</h3>';

	if (in_array($q->type, ['radio', 'checkbox', 'select'])) {
		$responses = $response->fetchAllByQuestion($q->id);
		$options = json_decode($q->options, true);
		$responseCounts = array_fill_keys($options, 0);

		foreach ($responses as $resp) {
			$responseCounts[$resp->response]++;
		}

		$totalResponses = count($responses);

		echo '<p>'.$totalResponses.' '.$langs->trans("Responses").'</p>';
		echo '<div class="chart-container">';
		echo '<canvas id="chart'.$q->id.'" width="20" height="20"></canvas>';
		echo '</div>';
		echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                var ctx = document.getElementById("chart'.$q->id.'").getContext("2d");
                new Chart(ctx, {
                    type: "pie",
                    data: {
                        labels: '.json_encode(array_keys($responseCounts)).',
                        datasets: [{
                            data: '.json_encode(array_values($responseCounts)).',
                            backgroundColor: ["#3e95cd", "#8e5ea2", "#3cba9f", "#e8c3b9", "#c45850"]
                        }]
                    },
                    options: {
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        let value = context.raw;
                                        let percentage = (value / total * 100).toFixed(2);
                                        return context.label + ": " + value + " (" + percentage + "%)";
                                    }
                                }
                            },
                            datalabels: {
                                formatter: function(value, context) {
                                    let sum = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                                    let percentage = (value * 100 / sum).toFixed(2);
                                     return percentage === "0.00" ? "" : percentage + "%"; // Hide label for 0%
                                },
                                color: function(context) {
								let value = context.dataset.data[context.dataIndex];
								return value === 0 ? "transparent" : "#fff"; // Transparent text color for 0%
							    },
							    backgroundColor: function(context) {
								let value = context.dataset.data[context.dataIndex];
								return value === 0 ? "transparent" : "#000"; // Transparent background for 0%
							    },
                                borderRadius: 3,
                                padding: 6
                            }
                        },
                        legend: {
                            display: false // Hide default legend
                        }
                    },
                    plugins: [ChartDataLabels]
                });
            });
        </script>';
	} else {
		$responses = $response->fetchAllByQuestion($q->id);
		echo '<ul>';
		foreach ($responses as $resp) {
			echo '<li>'.$resp->response.'</li>';
		}
		echo '</ul>';
	}
	echo '</div>';
}
echo '</div>';

llxFooter();
$db->close();
?>

<link rel="stylesheet" type="text/css" href="css/survey_results_detail.css?v=<?php echo time(); ?>">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
