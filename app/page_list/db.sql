CREATE TABLE IF NOT EXISTS page_list_settings (
  name varchar(128) NOT NULL,
  value varchar(256) NOT NULL,
  PRIMARY KEY (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS page_list_queue (
  lang varchar(128) NOT NULL,
  PRIMARY KEY (lang)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT page_list_settings (name, value)
VALUES
	('status', 'ok'),
	('known_langs', ''),
	('cur_date', NOW());