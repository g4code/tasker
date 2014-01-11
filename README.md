Tasker
==========

> Tasker - Application asynchronous tasks manager and runner, cron-like PHP implementation

Requires
--------

* MySQL tables

```sql
CREATE TABLE tasks (
    task_id     INT(10)      UNSIGNED          AUTO_INCREMENT,
    recu_id     INT(10)      UNSIGNED NOT NULL DEFAULT 0,
    task        VARCHAR(255)          NOT NULL DEFAULT '',
    data        TEXT,
    status      TINYINT(3)   UNSIGNED NOT NULL DEFAULT 0,
    priority    TINYINT(3)   UNSIGNED NOT NULL DEFAULT 0,
    created_ts  INT(10)      UNSIGNED NOT NULL DEFAULT 0,
    exec_time   FLOAT(10,6)  UNSIGNED NOT NULL DEFAULT 0.00,
    PRIMARY KEY (task_id),
    KEY (recu_id),
    KEY (priority),
    KEY (status),
    KEY pending (status, created_ts)
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

* Sample data

```sql
INSERT INTO tasks_recurrings
    (task, frequency, data, status)
VALUES
    ('\\Dumy\\Task\\Foo', '3-59/15 2,6-12 */15 1 2-5', '{\"foo\":123}', '1'),
    ('\\Dumy\\Task\\Bar', '*/5 * * * * *',             '{\"bar\":234}', '1');
```


## License

(The MIT License)
see LICENSE file for details...