<?php

class Response extends CommonObject
{
	public $id;
	public $fk_survey_question;
	public $fk_user;
	public $response;
	public $date_creation;
	public $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function create()
	{
		$sql = "INSERT INTO llx_survey_response (fk_survey_question, fk_user, response)
                VALUES (".$this->db->escape($this->fk_survey_question).", ".$this->db->escape($this->fk_user).", '".$this->db->escape($this->response)."')";

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->rowid = $this->db->last_insert_id('llx_survey_response');
			return $this->rowid;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	public function fetch($id)
	{
		$sql = "SELECT rowid, fk_survey_question, fk_user, response, date_creation
                FROM llx_survey_response
                WHERE rowid = " . intval($id);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql) > 0) {
				$obj = $this->db->fetch_object($resql);
				$this->id = $obj->rowid;
				$this->fk_survey_question = $obj->fk_survey_question;
				$this->fk_user = $obj->fk_user;
				$this->response = $obj->response;
				$this->date_creation = $this->db->jdate($obj->date_creation);
				return true;
			}
		}
		return false;
	}

	public function fetchAllByQuestion($question_id)
	{
		$responses = [];
		$sql = "SELECT rowid, fk_survey_question, fk_user, response, date_creation
                FROM llx_survey_response
                WHERE fk_survey_question = " . intval($question_id) . "
                ORDER BY date_creation";
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$response = new self($this->db);
				$response->id = $obj->rowid;
				$response->fk_survey_question = $obj->fk_survey_question;
				$response->fk_user = $obj->fk_user;
				$response->response = $obj->response;
				$response->date_creation = $this->db->jdate($obj->date_creation);
				$responses[] = $response;
			}
		}
		return $responses;
	}
}
?>
