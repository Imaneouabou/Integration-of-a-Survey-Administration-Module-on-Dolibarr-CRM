<?php

class Survey
{
	public $db;
	public $id;
	public $title;
	public $description;
	public $status;
	public $date_creation;
	public $date_start;
	public $date_end;
	public $questions = array();
	private $last_sql;
	public function __construct($db)
	{
		$this->db = $db;
	}

	public function create($user)
	{
		// Ensure date format is correct
		$date_creation = date('Y-m-d H:i:s');
		$date_start = date('Y-m-d H:i:s', strtotime($this->date_start));
		$date_end = date('Y-m-d H:i:s', strtotime($this->date_end));

		$sql = "INSERT INTO llx_survey (title, description, status, date_creation, date_start, date_end)
                VALUES ('".$this->db->escape($this->title)."', '".$this->db->escape($this->description)."', ".$this->db->escape($this->status).", '".$this->db->escape($date_creation)."', '".$this->db->escape($date_start)."', '".$this->db->escape($date_end)."')";
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->rowid = $this->db->last_insert_id('llx_survey');
			return $this->rowid;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	public function fetch($id)
	{
		$sql = "SELECT * FROM llx_survey WHERE rowid = ".$id;
		$res = $this->db->query($sql);
		if ($res) {
			$obj = $this->db->fetch_object($res);
			$this->id = $obj->rowid;
			$this->title = $obj->title;
			$this->description = $obj->description;
			$this->status = $obj->status;
			$this->date_creation = $obj->date_creation;
			$this->date_start = $obj->date_start;
			$this->date_end = $obj->date_end;
			return true;
		} else {
			return false;
		}
	}

	public function addQuestion($question, $type, $options = '', $position = 0)
	{
		$sql = "INSERT INTO llx_survey_question (fk_survey, question, type, options, position)
                VALUES (".$this->id.", '".$this->db->escape($question)."', '".$this->db->escape($type)."',
                        '".$this->db->escape($options)."', ".$position.")";
		$this->db->query($sql);
		$question_id = $this->db->last_insert_id('llx_survey_question');
		$this->questions[] = array('id' => $question_id, 'question' => $question, 'type' => $type, 'options' => $options, 'position' => $position);
	}
	public function addDependency($question_id, $dependency)
	{
		// Implementation for adding a dependency to a question in the survey
		$sql = "UPDATE llx_survey_question SET dependency = '".$this->db->escape($dependency)."' WHERE rowid = ".$question_id;
		$this->db->query($sql);
	}

	public function update($id, $user) {
		// Format the dates without time, setting time to '00:00:00'
		$formatted_date_start = !empty($this->date_start) ? date('Y-m-d 00:00:00', strtotime($this->date_start)) : null;
		$formatted_date_end = !empty($this->date_end) ? date('Y-m-d 00:00:00', strtotime($this->date_end)) : null;

		$sql = "UPDATE " . MAIN_DB_PREFIX . "survey SET
            title = '" . $this->db->escape($this->title) . "',
            description = '" . $this->db->escape($this->description) . "',
            status = " . (int)$this->status . ",
            date_start = " . ($formatted_date_start ? "'".$formatted_date_start."'" : 'null') . ",
            date_end = " . ($formatted_date_end ? "'".$formatted_date_end."'" : 'null') . "
            WHERE rowid = " . (int)$id;

		$this->db->begin();
		if ($this->db->query($sql)) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			$this->error = $this->db->lasterror();
			return -1;
		}
	}




	public function delete($user)
	{
		$sql = "DELETE FROM llx_survey WHERE rowid = " . intval($this->id);
		return $this->db->query($sql);
	}

	public function getLastSql() {
		return $this->last_sql;
	}

}

