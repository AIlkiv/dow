CREATE TABLE IF NOT EXISTS rev_month_settings (
  name varchar(128) NOT NULL,
  value varchar(256) NOT NULL,
  PRIMARY KEY (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


INSERT rev_month_settings (name, value)
VALUES
	('status', 'ok'),
	('known_langs', ''),
	('cur_date', NOW());