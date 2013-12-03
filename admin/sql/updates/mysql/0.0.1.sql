DROP TABLE IF EXISTS #__samlogin;
 
CREATE TABLE #__samlogin (
    id INT(11) NOT NULL AUTO_INCREMENT,
    greeting VARCHAR(25) NOT NULL,
    cat_id INT(11) NOT NULL DEFAULT 0,
    params TEXT NOT NULL DEFAULT '',
    PRIMARY KEY  (id)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
 
INSERT INTO #__samlogin (greeting) VALUES
        ('Hello World!'),
        ('Good bye World!');