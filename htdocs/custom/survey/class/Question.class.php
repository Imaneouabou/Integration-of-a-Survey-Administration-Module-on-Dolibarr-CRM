<?php

class Question extends CommonObject
{
	public $id;
	public $fk_survey;
	public $question;
	public $type;
	public $options;
	public $conditional_logic;
	public $position;
	public $db;
	public $mandatory;

	public function __construct($db)
	{
		$this->db = $db;
	}
	public function create()
	{
		$sql = "INSERT INTO llx_survey_question (fk_survey, question, type, options, conditional_logic, position, mandatory)
            VALUES (".$this->db->escape($this->fk_survey).", '".$this->db->escape($this->question)."', '".$this->db->escape($this->type)."', '".$this->db->escape($this->options)."', '".$this->db->escape($this->conditional_logic)."', ".$this->db->escape($this->position).", ".$this->db->escape($this->mandatory).")";

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->rowid = $this->db->last_insert_id('llx_survey_question');
			return $this->rowid;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}
	public function update()
	{
		$sql = "UPDATE llx_survey_question SET
        question = '".$this->db->escape($this->question)."',
        type = '".$this->db->escape($this->type)."',
        options = '".$this->db->escape($this->options)."',
        mandatory = ".(int)$this->mandatory.",
        conditional_logic = '".$this->db->escape($this->conditional_logic)."'
        WHERE rowid = ".(int)$this->id;

		if ($this->db->query($sql)) {
			return true;
		} else {
			$this->error = $this->db->lasterror();
			return false;
		}
	}



	public function fetch($id)
	{
		$sql = "SELECT rowid, fk_survey, question, type, options, conditional_logic, position, mandatory
            FROM llx_survey_question
            WHERE rowid = " . intval($id);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql) > 0) {
				$obj = $this->db->fetch_object($resql);
				$this->id = $obj->rowid;
				$this->fk_survey = $obj->fk_survey;
				$this->question = $obj->question;
				$this->type = $obj->type;
				$this->options = $obj->options;
				$this->conditional_logic = $obj->conditional_logic;
				$this->position = $obj->position;
				$this->mandatory = $obj->mandatory; // Fetch and set mandatory field
				return true;
			}
		}
		return false;
	}

	public function fetchAllBySurvey($survey_id)
	{
		$questions = [];
		$sql = "SELECT rowid, fk_survey, question, type, options, conditional_logic, position, mandatory
            FROM llx_survey_question
            WHERE fk_survey = " . intval($survey_id) . "
            ORDER BY position";
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$question = new self($this->db);
				$question->id = $obj->rowid;
				$question->fk_survey = $obj->fk_survey;
				$question->question = $obj->question;
				$question->type = $obj->type;
				$question->options = $obj->options;
				$question->conditional_logic = $obj->conditional_logic;
				$question->position = $obj->position;
				$question->mandatory = $obj->mandatory; // Fetch and set mandatory field
				$questions[] = $question;
			}
		}
		return $questions;
	}

}
?>
