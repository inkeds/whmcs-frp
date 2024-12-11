-- FRP 用户配置表
CREATE TABLE IF NOT EXISTS `mod_frp_user_configs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `service_id` int(11) NOT NULL,
    `client_id` int(11) NOT NULL,
    `token` varchar(255) NOT NULL,
    `bandwidth_limit` int(11) NOT NULL DEFAULT 10,
    `max_tunnels` int(11) NOT NULL DEFAULT 5,
    `allowed_protocols` varchar(255) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `service_id` (`service_id`),
    KEY `client_id` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- FRP 隧道配置表
CREATE TABLE IF NOT EXISTS `mod_frp_tunnels` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `config_id` int(11) NOT NULL,
    `name` varchar(255) NOT NULL,
    `type` varchar(50) NOT NULL,
    `local_port` int(11) NOT NULL,
    `remote_port` int(11) DEFAULT NULL,
    `domain` varchar(255) DEFAULT NULL,
    `enabled` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `config_id` (`config_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- FRP 使用统计表
CREATE TABLE IF NOT EXISTS `mod_frp_usage_stats` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `config_id` int(11) NOT NULL,
    `tunnel_id` int(11) DEFAULT NULL,
    `bytes_in` bigint(20) NOT NULL DEFAULT 0,
    `bytes_out` bigint(20) NOT NULL DEFAULT 0,
    `connections` int(11) NOT NULL DEFAULT 0,
    `date` date NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `stats_date` (`config_id`, `tunnel_id`, `date`),
    KEY `config_id` (`config_id`),
    KEY `tunnel_id` (`tunnel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
