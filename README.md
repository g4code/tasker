Tasker
==========

> Tasker - Application asynchronous tasks manager, cron-like PHP implementation with ability to run task in seconds (unlike cron in minutes)

Requires
--------

* MySQL tables

```sql
CREATE TABLE tasks (
    task_id       INT(10)      UNSIGNED          AUTO_INCREMENT,
    recu_id       INT(10)      UNSIGNED NOT NULL DEFAULT 0,
    identifier    VARCHAR(255)          NOT NULL DEFAULT '',
    task          VARCHAR(255)          NOT NULL DEFAULT '',
    data          TEXT,
    status        TINYINT(3)   UNSIGNED NOT NULL DEFAULT 0,
    priority      TINYINT(3)   UNSIGNED NOT NULL DEFAULT 0,
    ts_created    INT(10)      UNSIGNED NOT NULL DEFAULT 0,
    ts_started    INT(10)      UNSIGNED NOT NULL DEFAULT 0,
    exec_time     FLOAT(10,6)  UNSIGNED NOT NULL DEFAULT 0.000000,
    started_count TINYINT(3)   UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (task_id),
    KEY (recu_id),
    KEY (priority),
    KEY (status),
    KEY pending (status, ts_created),
    KEY started_count (started_count)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

```sql
CREATE TABLE tasks_recurrings (
    recu_id   INT(10)      UNSIGNED          AUTO_INCREMENT,
    frequency VARCHAR(255)          NOT NULL DEFAULT '',
    task      VARCHAR(255)          NOT NULL DEFAULT '',
    data      TEXT,
    status    TINYINT(3)   UNSIGNED NOT NULL DEFAULT 0,
    priority  TINYINT(3)   UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (recu_id),
    UNIQUE (task),
    KEY (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

```sql
CREATE TABLE tasks_error_log (
    tel_id       INT(10)      UNSIGNED          AUTO_INCREMENT,
    task_id      INT(10)      UNSIGNED NOT NULL DEFAULT 0,
    identifier   VARCHAR(255)          NOT NULL DEFAULT '',
    task         VARCHAR(255)          NOT NULL DEFAULT '',
    data         TEXT,
    ts_started   INT(10)      UNSIGNED NOT NULL DEFAULT 0,
    date_started DATETIME                      DEFAULT NULL,
    exec_time    FLOAT(10,6)  UNSIGNED NOT NULL DEFAULT 0.00,
    log          TEXT,
    PRIMARY KEY (tel_id),
    KEY (task_id),
    KEY (identifier),
    KEY (task),
    KEY (ts_started),
    KEY (date_started),
    KEY (exec_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

* Sample data

```sql
INSERT INTO tasks_recurrings
    (task, frequency, data, status)
VALUES
    ('Dummy\\Task\\Foo', '0 3-59/15 2,6-12 */15 1 2-5', '{\"foo\":123}', '1'),
    ('Dummy\\Task\\Bar', '0 */5 * * * *',               '{\"bar\":234}', '1'); # runs every 5 minutes
```


## License

(The MIT License)
see LICENSE file for details...