CREATE TABLE `llx_survey_response` (
                                       `rowid` INT AUTO_INCREMENT PRIMARY KEY,
                                       `fk_survey_question` INT NOT NULL,
                                       `fk_user` INT NOT NULL,
                                       `response` TEXT NOT NULL,
                                       `date_creation` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                       FOREIGN KEY (`fk_survey_question`) REFERENCES `llx_survey_question` (`rowid`) ON DELETE CASCADE,
                                       FOREIGN KEY (`fk_user`) REFERENCES `llx_user` (`rowid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
