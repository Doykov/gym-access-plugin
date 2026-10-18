<?php
defined('ABSPATH') || exit;

add_action('rest_api_init', function () {
    register_rest_route('gym/v1', '/check', [
        'methods' => 'GET',
        'callback' => 'gym_check_access',
        'permission_callback' => '__return_true'
    ]);
    
    register_rest_route('gym/v1', '/visit', [
        'methods' => 'POST',
        'callback' => 'gym_register_visit',
        'permission_callback' => '__return_true'
    ]);
    
    // Списък с валидни активни карти за локален кеш на ESP32
    register_rest_route('gym/v1', '/cards', [
        'methods' => 'GET',
        'callback' => 'gym_get_cards',
        'permission_callback' => '__return_true'
    ]);
    
    // OTA информация за ESP32 (версия и URL на firmware)
    register_rest_route('gym/v1', '/ota', [
        'methods' => 'GET',
        'callback' => 'gym_get_ota_info',
        'permission_callback' => '__return_true'
    ]);
});

/**
 * Връща списък с активни и валидни карти
 * Използва се от ESP32 за локален кеш.
 */
function gym_get_cards(WP_REST_Request $req) {
    global $wpdb;
    $table = $wpdb->prefix . 'gym_cards';
    
    // Текущо време според настройките на WordPress
    $now_mysql = current_time('mysql');
    
    // Вземаме само активни карти, които са в периода си на валидност
    // и не са изчерпали лимита посещения (ако имат лимит).
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT 
                card_number,
                rfid_uid,
                holder_name,
                valid_from,
                valid_until,
                max_visits,
                visits_count,
                last_visit,
                status
             FROM $table
             WHERE status = 'active'
               AND (
                    valid_from IS NULL
                    OR valid_from = ''
                    OR valid_from = '0000-00-00 00:00:00'
                    OR valid_from <= %s
               )
               AND (
                    valid_until IS NULL
                    OR valid_until = ''
                    OR valid_until = '0000-00-00 00:00:00'
                    OR valid_until >= %s
               )
               AND (
                    max_visits IS NULL
                    OR max_visits = 0
                    OR visits_count < max_visits
               )",
            $now_mysql,
            $now_mysql
        ),
        ARRAY_A
    );
    
    return [
        'server_time' => $now_mysql,
        'timezone'    => wp_timezone_string(),
        'cards'       => $rows,
    ];
}

/**
 * Връща информация за последния качен firmware за ESP32
 * Използва се за OTA ъпдейти.
 */
function gym_get_ota_info(WP_REST_Request $req) {
    $settings = get_option('gym_access_settings', []);
    
    $version  = isset($settings['ota_firmware_version']) ? trim($settings['ota_firmware_version']) : '';
    $url      = isset($settings['ota_firmware_url']) ? esc_url_raw($settings['ota_firmware_url']) : '';
    $required = !empty($settings['ota_required']);
    
    // Ако няма качен firmware, връщаме празни стойности
    if (empty($version) || empty($url)) {
        return [
            'version'      => '',
            'required'     => false,
            'firmware_url' => '',
        ];
    }
    
    return [
        'version'      => $version,
        'required'     => (bool) $required,
        'firmware_url' => $url,
    ];
}

/**
 * Записва лог запис за достъп (успешен или отказан)
 */
function gym_log_access($card_id, $card_number, $rfid_uid, $holder_name, $access_granted, $deny_reason = null) {
    global $wpdb;
    $log_table = $wpdb->prefix . 'gym_access_log';
    
    // Броим колко пъти тази карта е чекирала днес
    $today_start = current_time('Y-m-d') . ' 00:00:00';
    $today_end = current_time('Y-m-d') . ' 23:59:59';
    
    $check_count_today = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $log_table WHERE card_number = %s AND checked_at BETWEEN %s AND %s",
        $card_number,
        $today_start,
        $today_end
    ));
    
    // Новият запис ще бъде следващият по ред
    $check_count_today++;
    
    $wpdb->insert($log_table, [
        'card_id'         => $card_id,
        'card_number'     => $card_number,
        'rfid_uid'        => $rfid_uid,
        'holder_name'     => $holder_name,
        'access_granted'  => $access_granted ? 1 : 0,
        'deny_reason'     => $deny_reason,
        'check_count_today' => $check_count_today,
        'checked_at'      => current_time('mysql'),
    ]);
}

function gym_check_access($req) {
    global $wpdb;
    $card = sanitize_text_field($req->get_param('card'));
    if (!$card) return ['access' => false];
    
    $register_visit = $req->get_param('register') === 'true' || $req->get_param('register') === '1';

    $table = $wpdb->prefix . 'gym_cards';
    $mapping_table = $wpdb->prefix . 'gym_card_mapping';
    
    // Зареждаме настройки на плъгина
    $settings = get_option('gym_access_settings', []);
    $one_visit_per_day = !empty($settings['one_visit_per_day']);
    
    // ESP32 изпраща Wiegand код (DEC формат)
    // Търсим ПЪРВО по rfid_uid (Wiegand код), защото това е каквото изпраща ESP32
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT id, valid_from, valid_until, status, max_visits, visits_count, last_visit, card_number, rfid_uid, holder_name, email, receive_emails 
         FROM $table 
         WHERE rfid_uid=%s 
         LIMIT 1", 
        $card
    ));
    
    // Ако не намерим по Wiegand код, опитваме се да намерим по card_number
    // (за обратна съвместимост, ако някой е въвел card_number вместо Wiegand код)
    if (!$row) {
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT id, valid_from, valid_until, status, max_visits, visits_count, last_visit, card_number, rfid_uid, holder_name, email, receive_emails 
             FROM $table 
             WHERE card_number=%s 
             LIMIT 1", 
            $card
        ));
    }

    if (!$row || $row->status !== 'active') {
        // Логваме отказания достъп
        $deny_reason = !$row ? 'card_not_found' : 'card_inactive';
        gym_log_access(
            $row ? $row->id : null,
            $row ? $row->card_number : $card,
            $card,
            $row ? $row->holder_name : null,
            false,
            $deny_reason
        );
        return ['access' => false, 'reason' => $deny_reason];
    }

    // Използваме WordPress функции за работа с дати (които вземат предвид timezone настройките)
    $now = current_time('timestamp');
    
    // Проверка дали картата е валидна (между valid_from и valid_until)
    // Ако valid_from е NULL или празно, приемаме че картата е валидна от момента на създаването
    if (!empty($row->valid_from) && $row->valid_from !== '0000-00-00 00:00:00' && $row->valid_from !== null) {
        // Конвертираме датата от базата данни в timestamp
        // mysql2date с true конвертира MySQL datetime в Unix timestamp в local timezone
        $valid_from_timestamp = mysql2date('U', $row->valid_from, true);
        
        // Ако mysql2date не работи, опитваме с strtotime
        if ($valid_from_timestamp === false || $valid_from_timestamp === '' || $valid_from_timestamp === null) {
            $valid_from_timestamp = strtotime($row->valid_from);
        }
        
        // Проверяваме дали датата е валидна и е в бъдещето
        if ($valid_from_timestamp !== false && $valid_from_timestamp > 0 && $valid_from_timestamp > $now) {
            gym_log_access($row->id, $row->card_number, $row->rfid_uid, $row->holder_name, false, 'not_started');
            return [
                'access' => false, 
                'reason' => 'not_started',
                'debug' => [
                    'valid_from_raw' => $row->valid_from,
                    'valid_from_timestamp' => $valid_from_timestamp,
                    'now' => $now,
                    'now_formatted' => date('Y-m-d H:i:s', $now),
                    'valid_from_formatted' => date('Y-m-d H:i:s', $valid_from_timestamp),
                    'diff_seconds' => $valid_from_timestamp - $now,
                    'timezone' => wp_timezone_string()
                ]
            ];
        }
    }
    
    // Проверка за valid_until
    if (!empty($row->valid_until) && $row->valid_until !== '0000-00-00 00:00:00' && $row->valid_until !== null) {
        // Конвертираме датата от базата данни в timestamp
        $valid_until_timestamp = mysql2date('U', $row->valid_until, true);
        
        // Ако mysql2date не работи, опитваме с strtotime
        if ($valid_until_timestamp === false || $valid_until_timestamp === '' || $valid_until_timestamp === null) {
            $valid_until_timestamp = strtotime($row->valid_until);
        }
        
        // Проверяваме дали датата е валидна и е в миналото
        if ($valid_until_timestamp !== false && $valid_until_timestamp > 0 && $valid_until_timestamp < $now) {
            gym_log_access($row->id, $row->card_number, $row->rfid_uid, $row->holder_name, false, 'expired');
            return [
                'access' => false, 
                'reason' => 'expired',
                'debug' => [
                    'valid_until_raw' => $row->valid_until,
                    'valid_until_timestamp' => $valid_until_timestamp,
                    'now' => $now,
                    'now_formatted' => date('Y-m-d H:i:s', $now),
                    'valid_until_formatted' => date('Y-m-d H:i:s', $valid_until_timestamp),
                    'diff_seconds' => $now - $valid_until_timestamp,
                    'timezone' => wp_timezone_string()
                ]
            ];
        }
    }
    
    // Проверка за ограничение: максимум едно посещение на ден
    if ($one_visit_per_day && !empty($row->last_visit)) {
        // Конвертираме последното посещение в timestamp в локалната часова зона
        $last_visit_timestamp = mysql2date('U', $row->last_visit, true);
        
        if ($last_visit_timestamp === false || $last_visit_timestamp === '' || $last_visit_timestamp === null) {
            $last_visit_timestamp = strtotime($row->last_visit);
        }
        
        if ($last_visit_timestamp) {
            $current_date = date('Y-m-d', $now);
            $last_visit_date = date('Y-m-d', $last_visit_timestamp);
            
            if ($current_date === $last_visit_date) {
                // Вече има успешно посещение за днешния ден
                gym_log_access($row->id, $row->card_number, $row->rfid_uid, $row->holder_name, false, 'daily_limit_reached');
                return [
                    'access' => false,
                    'reason' => 'daily_limit_reached',
                    'last_visit' => $row->last_visit,
                    'next_allowed_date' => date('Y-m-d', strtotime('+1 day', $last_visit_timestamp)),
                ];
            }
        }
    }
    
    // Проверка за лимит на посещения (ако има лимит)
    $max_visits = isset($row->max_visits) ? (int)$row->max_visits : 0;
    $current_visits = isset($row->visits_count) ? (int)$row->visits_count : 0;
    if ($max_visits > 0 && $current_visits >= $max_visits) {
        gym_log_access($row->id, $row->card_number, $row->rfid_uid, $row->holder_name, false, 'visits_limit_reached');
        return ['access' => false, 'reason' => 'visits_limit_reached', 'max_visits' => $max_visits, 'current_visits' => $current_visits];
    }

    // Ако е поискано, регистрираме посещението
    if ($register_visit) {
        $current_visits = isset($row->visits_count) ? (int)$row->visits_count : 0;
        $new_visits = $current_visits + 1;
        $now_datetime = current_time('mysql');

        $wpdb->update(
            $table,
            [
                'visits_count' => $new_visits,
                'last_visit' => $now_datetime
            ],
            ['id' => $row->id]
        );
        
        $row->visits_count = $new_visits;
        $row->last_visit = $now_datetime;
        
        // Логваме успешния достъп
        gym_log_access($row->id, $row->card_number, $row->rfid_uid, $row->holder_name, true, null);

        if ($max_visits > 0) {
            gym_access_maybe_send_visits_remaining_email($row, $max_visits, $new_visits);
        }
    }

    $report_visits = isset($row->visits_count) ? (int) $row->visits_count : $current_visits;

    return [
        'access' => true,
        'valid_from' => $row->valid_from,
        'valid_until' => $row->valid_until,
        'max_visits' => $max_visits,
        'visits_count' => $report_visits,
        'last_visit' => $row->last_visit,
        'remaining_visits' => $max_visits > 0 ? ($max_visits - $report_visits) : null,
        'debug' => [
            'card_number' => $row->card_number,
            'rfid_uid' => isset($row->rfid_uid) ? $row->rfid_uid : null,
            'now' => $now,
            'now_formatted' => date('Y-m-d H:i:s', $now)
        ]
    ];
}

function gym_register_visit($req) {
    global $wpdb;
    $card = sanitize_text_field($req->get_param('card'));
    if (!$card) {
        return new WP_Error('missing_card', 'Номерът на картата е задължителен', ['status' => 400]);
    }

    $table = $wpdb->prefix . 'gym_cards';
    
    // Зареждаме настройки на плъгина
    $settings = get_option('gym_access_settings', []);
    $one_visit_per_day = !empty($settings['one_visit_per_day']);
    
    // ESP32 изпраща Wiegand код (DEC формат)
    // Търсим ПЪРВО по rfid_uid (Wiegand код), защото това е каквото изпраща ESP32
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT id, valid_from, valid_until, status, max_visits, visits_count, last_visit, card_number, rfid_uid, holder_name, email, receive_emails 
         FROM $table 
         WHERE rfid_uid=%s 
         LIMIT 1", 
        $card
    ));
    
    // Ако не намерим по Wiegand код, опитваме се да намерим по card_number
    // (за обратна съвместимост)
    if (!$row) {
        $row = $wpdb->get_row($wpdb->prepare(
        "SELECT id, valid_from, valid_until, status, max_visits, visits_count, last_visit, card_number, rfid_uid, holder_name, email, receive_emails 
             FROM $table 
             WHERE card_number=%s 
             LIMIT 1", 
            $card
        ));
    }

    if (!$row || $row->status !== 'active') {
        $deny_reason = !$row ? 'card_not_found' : 'card_inactive';
        gym_log_access(
            $row ? $row->id : null,
            $row ? $row->card_number : $card,
            $card,
            $row ? $row->holder_name : null,
            false,
            $deny_reason
        );
        return new WP_Error('invalid_card', 'Картата не е намерена или не е активна', ['status' => 404]);
    }

    // Използваме WordPress време според настройките на сайта
    $now = current_time('timestamp');
    
    // Проверка дали картата е валидна
    if ($row->valid_from && strtotime($row->valid_from) > $now) {
        gym_log_access($row->id, $row->card_number, $row->rfid_uid, $row->holder_name, false, 'not_started');
        return new WP_Error('not_started', 'Картата все още не е валидна', ['status' => 403]);
    }
    
    if ($row->valid_until && strtotime($row->valid_until) < $now) {
        gym_log_access($row->id, $row->card_number, $row->rfid_uid, $row->holder_name, false, 'expired');
        return new WP_Error('expired', 'Картата е изтекла', ['status' => 403]);
    }
    
    // Проверка за ограничение: максимум едно посещение на ден
    if ($one_visit_per_day && !empty($row->last_visit)) {
        $last_visit_timestamp = mysql2date('U', $row->last_visit, true);
        
        if ($last_visit_timestamp === false || $last_visit_timestamp === '' || $last_visit_timestamp === null) {
            $last_visit_timestamp = strtotime($row->last_visit);
        }
        
        if ($last_visit_timestamp) {
            $current_date = date('Y-m-d', $now);
            $last_visit_date = date('Y-m-d', $last_visit_timestamp);
            
            if ($current_date === $last_visit_date) {
                gym_log_access($row->id, $row->card_number, $row->rfid_uid, $row->holder_name, false, 'daily_limit_reached');
                return new WP_Error(
                    'daily_limit_reached',
                    'Картата вече е използвана днес',
                    [
                        'status' => 403,
                        'last_visit' => $row->last_visit,
                    ]
                );
            }
        }
    }
    
    // Проверка за лимит на посещения
    $max_visits = isset($row->max_visits) ? (int)$row->max_visits : 0;
    $current_visits = isset($row->visits_count) ? (int)$row->visits_count : 0;
    if ($max_visits > 0 && $current_visits >= $max_visits) {
        gym_log_access($row->id, $row->card_number, $row->rfid_uid, $row->holder_name, false, 'visits_limit_reached');
        return new WP_Error('visits_limit_reached', 'Достигнат е максималният брой посещения', ['status' => 403, 'max_visits' => $max_visits, 'current_visits' => $current_visits]);
    }

    // Регистрираме посещението
    $new_visits = $current_visits + 1;
    $now_datetime = current_time('mysql');

    $wpdb->update(
        $table,
        [
            'visits_count' => $new_visits,
            'last_visit' => $now_datetime
        ],
        ['id' => $row->id]
    );
    
    // Логваме успешния достъп
    gym_log_access($row->id, $row->card_number, $row->rfid_uid, $row->holder_name, true, null);

    if ($max_visits > 0) {
        $row->visits_count = $new_visits;
        gym_access_maybe_send_visits_remaining_email($row, $max_visits, $new_visits);
    }

    return [
        'success' => true,
        'card_number' => $card,
        'visits_count' => $new_visits,
        'last_visit' => $now_datetime
    ];
}
