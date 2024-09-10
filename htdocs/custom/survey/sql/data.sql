ALTER TABLE llx_survey_question
ADD COLUMN mandatory TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE llx_survey_question ADD trigger_question INT DEFAULT NULL;
ALTER TABLE llx_survey_question ADD trigger_value VARCHAR(255) DEFAULT NULL;

ALTER TABLE llx_survey_response
    ADD COLUMN fk_user INT NOT NULL;

ALTER TABLE llx_survey_response
    ADD CONSTRAINT fk_user FOREIGN KEY (fk_user) REFERENCES llx_user(rowid) ON DELETE CASCADE;
