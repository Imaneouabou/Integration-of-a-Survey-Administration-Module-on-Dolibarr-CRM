CREATE TABLE llx_survey_question (
                                     rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
                                     fk_survey INTEGER REFERENCES llx_survey(rowid),
                                     question TEXT NOT NULL,
                                     type VARCHAR(50) NOT NULL,
                                     options TEXT,
                                     conditional_logic TEXT,
                                     position INTEGER,
                                     mandatory TINYINT(1) NOT NULL DEFAULT 0,
                                     trigger_question INT DEFAULT NULL,
                                     trigger_value VARCHAR(255) DEFAULT NULL,

FOREIGN KEY (`fk_survey`) REFERENCES `llx_survey` (`rowid`) ON DELETE CASCADE
);
