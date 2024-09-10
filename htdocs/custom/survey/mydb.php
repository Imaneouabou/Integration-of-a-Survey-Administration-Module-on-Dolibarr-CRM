CREATE TABLE llx_survey (
rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
title VARCHAR(255) NOT NULL,
description TEXT,
status INTEGER DEFAULT 0,
date_creation DATETIME NOT NULL,
date_start DATETIME,
date_end DATETIME,
fk_user_author INTEGER REFERENCES llx_user(rowid)
);

CREATE TABLE `llx_survey_response` (
`rowid` INT AUTO_INCREMENT PRIMARY KEY,
`fk_survey_question` INT NOT NULL,
`response` TEXT NOT NULL,
`date_creation` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (`fk_survey_question`) REFERENCES `llx_survey_question` (`rowid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSETa=utf8;

CREATE TABLE llx_survey_question (
rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
fk_survey INTEGER REFERENCES llx_survey(rowid),
question TEXT NOT NULL,
type VARCHAR(50) NOT NULL,
options TEXT,
conditional_logic TEXT,
position INTEGER,
mandatory TINYINT(1) NOT NULL DEFAULT 0,
);
