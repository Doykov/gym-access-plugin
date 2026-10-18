<?php
defined('ABSPATH') || exit;

function gym_access_install() {
    global $wpdb;
    $table = $wpdb->prefix . 'gym_cards';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        card_number VARCHAR(50) UNIQUE NOT NULL,
        rfid_uid VARCHAR(50) NULL,
        holder_name VARCHAR(190),
        email VARCHAR(190),
        receive_emails TINYINT(1) NOT NULL DEFAULT 1,
        phone VARCHAR(50),
        valid_from DATETIME,
        valid_until DATETIME,
        max_visits INT UNSIGNED DEFAULT 0,
        visits_count INT UNSIGNED DEFAULT 0,
        last_visit DATETIME,
        status VARCHAR(20) DEFAULT 'inactive',
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) $charset;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
    
    // Миграция: добавяне на липсващи колони ако не съществуват
    $columns_to_add = [
        'valid_from' => "ALTER TABLE $table ADD COLUMN valid_from DATETIME AFTER phone",
        'max_visits' => "ALTER TABLE $table ADD COLUMN max_visits INT UNSIGNED DEFAULT 0 AFTER valid_until",
        'visits_count' => "ALTER TABLE $table ADD COLUMN visits_count INT UNSIGNED DEFAULT 0 AFTER max_visits",
        'last_visit' => "ALTER TABLE $table ADD COLUMN last_visit DATETIME AFTER visits_count",
        'rfid_uid' => "ALTER TABLE $table ADD COLUMN rfid_uid VARCHAR(50) NULL AFTER card_number",
        'receive_emails' => "ALTER TABLE $table ADD COLUMN receive_emails TINYINT(1) NOT NULL DEFAULT 1 AFTER email",
    ];
    
    foreach ($columns_to_add as $column_name => $alter_sql) {
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
            DB_NAME, $table, $column_name
        ));
        
        if (empty($column_exists)) {
            $wpdb->query($alter_sql);
        }
    }
    
    // Добавяне на индекс за rfid_uid за по-бързо търсене (ако не съществува)
    $index_exists = $wpdb->get_results($wpdb->prepare(
        "SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS 
         WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND INDEX_NAME = 'idx_rfid_uid'",
        DB_NAME, $table
    ));
    
    if (empty($index_exists)) {
        $wpdb->query("CREATE INDEX idx_rfid_uid ON $table (rfid_uid)");
    }
    
    // Създаване на таблица за мапване между номера на карти и RFID UID
    $mapping_table = $wpdb->prefix . 'gym_card_mapping';
    $mapping_sql = "CREATE TABLE $mapping_table (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        card_number VARCHAR(50) UNIQUE NOT NULL,
        rfid_uid VARCHAR(50) UNIQUE NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) $charset;";
    
    dbDelta($mapping_sql);
    
    // Добавяне на индекси за по-бързо търсене
    $mapping_indexes = [
        'idx_card_number' => "CREATE INDEX idx_card_number ON $mapping_table (card_number)",
        'idx_rfid_uid_mapping' => "CREATE INDEX idx_rfid_uid_mapping ON $mapping_table (rfid_uid)"
    ];
    
    foreach ($mapping_indexes as $index_name => $index_sql) {
        $index_exists = $wpdb->get_results($wpdb->prepare(
            "SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS 
             WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND INDEX_NAME = %s",
            DB_NAME, $mapping_table, str_replace('idx_', '', $index_name)
        ));
        
        if (empty($index_exists)) {
            $wpdb->query($index_sql);
        }
    }
    
    // Създаване на таблица за лог на достъпа (хронология)
    $log_table = $wpdb->prefix . 'gym_access_log';
    $log_sql = "CREATE TABLE $log_table (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        card_id BIGINT UNSIGNED NULL,
        card_number VARCHAR(50) NOT NULL,
        rfid_uid VARCHAR(50) NULL,
        holder_name VARCHAR(190) NULL,
        access_granted TINYINT(1) DEFAULT 0,
        deny_reason VARCHAR(100) NULL,
        check_count_today INT UNSIGNED DEFAULT 1,
        checked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_card_number (card_number),
        INDEX idx_checked_at (checked_at),
        INDEX idx_card_checked (card_number, checked_at)
    ) $charset;";
    
    dbDelta($log_sql);

    // Лог на изпратени системни имейли (напомняния, оставащи посещения)
    $email_log_table = $wpdb->prefix . 'gym_access_email_log';
    $email_log_sql   = "CREATE TABLE $email_log_table (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        sent_at DATETIME NOT NULL,
        email_type VARCHAR(40) NOT NULL,
        recipient_email VARCHAR(190) NOT NULL,
        subject VARCHAR(500) NOT NULL,
        card_id BIGINT UNSIGNED NULL,
        card_number VARCHAR(50) NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'sent',
        error_message VARCHAR(500) NULL,
        INDEX idx_sent_at (sent_at),
        INDEX idx_email_type (email_type)
    ) $charset;";

    dbDelta($email_log_sql);
}
