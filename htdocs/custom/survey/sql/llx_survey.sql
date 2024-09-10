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
