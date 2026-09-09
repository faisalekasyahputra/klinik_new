-- Counter kegagalan lookup tiket per hash IP untuk jendela satu menit.

CREATE TABLE IF NOT EXISTS `sys_ticket_lookup_limits` (
  `ip_hash` CHAR(64) NOT NULL,
  `window_started_at` DATETIME NOT NULL,
  `failed_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`ip_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
