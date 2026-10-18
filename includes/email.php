<?php
defined('ABSPATH') || exit;

function gym_access_mail_set_html_content_type(): string {
    return 'text/html; charset=UTF-8';
}

/**
 * Дали за картата са разрешени автоматични имейли (колона receive_emails в gym_cards).
 */
function gym_access_card_accepts_emails(object $card): bool {
    if (!property_exists($card, 'receive_emails') || $card->receive_emails === null) {
        return true;
    }
    return (int) $card->receive_emails === 1;
}

/**
 * Стойности по подразбиране за имейл настройките (сливат се в gym_access_settings).
 */
function gym_access_email_setting_defaults(): array {
    return [
        'email_expiry_enabled'       => 0,
        'email_visits_enabled'       => 0,
        'email_days_before_expiry'   => 7,
        'email_subject_expiry'       => 'Напомняне: абонаментът Ви изтича скоро',
        'email_body_expiry'          => "Здравейте, {holder_name},\n\nАбонаментът Ви с карта № {card_number} изтича на {valid_until}.\nОстават още {days_remaining} календарни дни до изтичане.\n\nПоздрави,\n{site_name}",
        'email_subject_visits'       => 'Оставащи посещения по абонамента',
        'email_body_visits'          => "Здравейте, {holder_name},\n\nУспешно чекиране. По абонамент с карта № {card_number} имате още {visits_remaining} посещения от общо {max_visits}.\nИзползвани посещения: {visits_used}.\n\nПоздрави,\n{site_name}",
    ];
}

/**
 * @param object $card Ред от gym_cards (трябва id, email, holder_name, card_number, valid_until, max_visits, visits_count).
 */
function gym_access_merge_email_template_vars(object $card, array $extra = []): array {
    $site_name = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
    $site_url  = esc_url(home_url('/'));
    $holder    = isset($card->holder_name) ? (string) $card->holder_name : '';
    $card_no   = isset($card->card_number) ? (string) $card->card_number : '';
    $until     = isset($card->valid_until) ? (string) $card->valid_until : '';
    $max_v     = isset($card->max_visits) ? (int) $card->max_visits : 0;
    $used_v    = isset($card->visits_count) ? (int) $card->visits_count : 0;

    $vars = [
        '{site_name}'    => $site_name,
        '{site_url}'     => $site_url,
        '{holder_name}'  => $holder,
        '{card_number}'  => $card_no,
        // По заявка показваме само дата на изтичане, без час.
        '{valid_until}'  => $until ? date_i18n(get_option('date_format'), strtotime($until)) : '',
        '{max_visits}'   => (string) $max_v,
        '{visits_used}'  => (string) $used_v,
    ];

    $days = gym_access_calendar_days_until($until);
    $vars['{days_remaining}'] = $days !== null ? (string) max(0, $days) : '';

    $rem = $max_v > 0 ? max(0, $max_v - $used_v) : '';
    $vars['{visits_remaining}'] = $rem === '' ? '' : (string) $rem;

    return array_merge($vars, $extra);
}

/**
 * Календарни дни от днес (според WP) до края на деня на valid_until.
 */
function gym_access_calendar_days_until(?string $valid_until): ?int {
    if ($valid_until === null || $valid_until === '' || $valid_until === '0000-00-00 00:00:00') {
        return null;
    }
    $until_day = substr($valid_until, 0, 10);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $until_day)) {
        return null;
    }
    $today = current_time('Y-m-d');
    try {
        $tz = wp_timezone();
        $d0 = new DateTimeImmutable($today, $tz);
        $d1 = new DateTimeImmutable($until_day, $tz);
    } catch (Exception $e) {
        return null;
    }
    $diff = $d0->diff($d1);
    if ($diff->invert) {
        return null;
    }
    return (int) $diff->days;
}

function gym_access_replace_email_placeholders(string $text, array $vars): string {
    return str_replace(array_keys($vars), array_values($vars), $text);
}

/**
 * Запис в лога на изпратени имейли (таблица gym_access_email_log).
 *
 * @param array $ctx Ключове: email_type, опционално card_id, card_number.
 */
function gym_access_log_email_attempt(string $to, string $subject, bool $sent, array $ctx): void {
    global $wpdb;
    $table = $wpdb->prefix . 'gym_access_email_log';
    // Ако таблицата още не е създадена (стара инсталация без повторна активация).
    if (!$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table))) {
        return;
    }

    $error = '';
    if (!$sent) {
        if (isset($GLOBALS['phpmailer']) && is_object($GLOBALS['phpmailer']) && !empty($GLOBALS['phpmailer']->ErrorInfo)) {
            $error = (string) $GLOBALS['phpmailer']->ErrorInfo;
        } else {
            $error = 'wp_mail() върна false';
        }
    }

    $type = isset($ctx['email_type']) ? sanitize_key((string) $ctx['email_type']) : 'unknown';
    if ($type === '') {
        $type = 'unknown';
    }

    $cid = isset($ctx['card_id']) ? (int) $ctx['card_id'] : 0;
    $sub = wp_strip_all_tags($subject);
    $sub = function_exists('mb_substr') ? mb_substr($sub, 0, 500) : substr($sub, 0, 500);
    $err = $error !== '' ? (function_exists('mb_substr') ? mb_substr($error, 0, 500) : substr($error, 0, 500)) : null;

    $wpdb->insert(
        $table,
        [
            'sent_at'         => current_time('mysql'),
            'email_type'      => $type,
            'recipient_email' => $to,
            'subject'         => $sub,
            'card_id'         => $cid > 0 ? $cid : null,
            'card_number'     => isset($ctx['card_number']) ? sanitize_text_field((string) $ctx['card_number']) : null,
            'status'          => $sent ? 'sent' : 'failed',
            'error_message'   => $err,
        ]
    );
}

/**
 * Изпраща HTML имейл (съдържанието е от админ шаблон — ограничен kses).
 *
 * @param array $log_context За лог: email_type, card_id, card_number. Празно = без запис в лога.
 */
function gym_access_wp_mail_formatted(string $to, string $subject, string $body, array $log_context = []): bool {
    $to = sanitize_email($to);
    if ($to === '' || !is_email($to)) {
        return false;
    }

    $html = nl2br(esc_html($body));

    add_filter('wp_mail_content_type', 'gym_access_mail_set_html_content_type');

    $sent = wp_mail($to, wp_specialchars_decode($subject, ENT_QUOTES), $html);

    remove_filter('wp_mail_content_type', 'gym_access_mail_set_html_content_type');

    if (!empty($log_context['email_type'])) {
        gym_access_log_email_attempt($to, $subject, $sent, $log_context);
    }

    return $sent;
}

/**
 * След успешно чекиране: имейл за оставащи посещения (ако има лимит и имейл).
 */
function gym_access_maybe_send_visits_remaining_email(object $card, int $max_visits, int $visits_after_checkin): void {
    if ($max_visits <= 0) {
        return;
    }

    $settings = get_option('gym_access_settings', []);
    if (!is_array($settings) || empty($settings['email_visits_enabled'])) {
        return;
    }

    $defaults = gym_access_email_setting_defaults();
    if (!gym_access_card_accepts_emails($card)) {
        return;
    }

    $to = isset($card->email) ? trim((string) $card->email) : '';
    if ($to === '' || !is_email($to)) {
        return;
    }

    $card_copy = clone $card;
    $card_copy->visits_count = $visits_after_checkin;
    $card_copy->max_visits   = $max_visits;

    $vars = gym_access_merge_email_template_vars($card_copy, [
        '{visits_remaining}' => (string) max(0, $max_visits - $visits_after_checkin),
        '{visits_used}'      => (string) $visits_after_checkin,
        '{max_visits}'       => (string) $max_visits,
    ]);

    $subject_tpl = isset($settings['email_subject_visits']) ? (string) $settings['email_subject_visits'] : $defaults['email_subject_visits'];
    $body_tpl    = isset($settings['email_body_visits']) ? (string) $settings['email_body_visits'] : $defaults['email_body_visits'];

    $subject = gym_access_replace_email_placeholders($subject_tpl, $vars);
    $body    = gym_access_replace_email_placeholders($body_tpl, $vars);

    gym_access_wp_mail_formatted(
        $to,
        $subject,
        $body,
        [
            'email_type'  => 'visits_remaining',
            'card_id'     => isset($card->id) ? (int) $card->id : 0,
            'card_number' => isset($card->card_number) ? (string) $card->card_number : '',
        ]
    );
}

/**
 * Ключове за вече изпратени напомняния за изтичане (по карта + дата на изтичане).
 */
function gym_access_get_expiry_reminder_sent_keys(): array {
    $raw = get_option('gym_access_email_expiry_sent_keys', []);
    return is_array($raw) ? $raw : [];
}

function gym_access_mark_expiry_reminder_sent(int $card_id, string $valid_until_day): void {
    $key = $card_id . '|' . $valid_until_day;
    $keys = gym_access_get_expiry_reminder_sent_keys();
    if (!isset($keys[$key])) {
        $keys[$key] = time();
        if (count($keys) > 2000) {
            asort($keys);
            $keys = array_slice($keys, -1000, null, true);
        }
        update_option('gym_access_email_expiry_sent_keys', $keys, false);
    }
}

function gym_access_expiry_reminder_already_sent(int $card_id, string $valid_until_day): bool {
    $key = $card_id . '|' . $valid_until_day;
    return isset(gym_access_get_expiry_reminder_sent_keys()[$key]);
}

/**
 * Ежедневен cron: напомняне N дни преди изтичане.
 */
function gym_access_run_expiry_reminder_cron(): void {
    $settings = get_option('gym_access_settings', []);
    if (!is_array($settings) || empty($settings['email_expiry_enabled'])) {
        return;
    }

    $days_before = isset($settings['email_days_before_expiry']) ? (int) $settings['email_days_before_expiry'] : 7;
    $days_before = max(1, min(90, $days_before));

    $defaults = gym_access_email_setting_defaults();

    global $wpdb;
    $table = $wpdb->prefix . 'gym_cards';

    $rows = $wpdb->get_results(
        "SELECT id, email, holder_name, card_number, valid_until, status, receive_emails 
         FROM $table 
         WHERE status = 'active' 
           AND (receive_emails IS NULL OR receive_emails = 1)
           AND email IS NOT NULL AND email != '' 
           AND valid_until IS NOT NULL 
           AND valid_until != '' 
           AND valid_until != '0000-00-00 00:00:00'"
    );

    if (!$rows) {
        return;
    }

    foreach ($rows as $card) {
        if (!gym_access_card_accepts_emails($card)) {
            continue;
        }
        $days_left = gym_access_calendar_days_until($card->valid_until);
        if ($days_left === null || $days_left !== $days_before) {
            continue;
        }

        $until_day = substr($card->valid_until, 0, 10);
        if (gym_access_expiry_reminder_already_sent((int) $card->id, $until_day)) {
            continue;
        }

        $to = sanitize_email($card->email);
        if ($to === '' || !is_email($to)) {
            continue;
        }

        $vars = gym_access_merge_email_template_vars($card, [
            '{days_remaining}' => (string) $days_left,
        ]);

        $subject_tpl = isset($settings['email_subject_expiry']) ? (string) $settings['email_subject_expiry'] : $defaults['email_subject_expiry'];
        $body_tpl    = isset($settings['email_body_expiry']) ? (string) $settings['email_body_expiry'] : $defaults['email_body_expiry'];

        $subject = gym_access_replace_email_placeholders($subject_tpl, $vars);
        $body    = gym_access_replace_email_placeholders($body_tpl, $vars);

        if (gym_access_wp_mail_formatted(
            $to,
            $subject,
            $body,
            [
                'email_type'  => 'expiry_reminder',
                'card_id'     => (int) $card->id,
                'card_number' => isset($card->card_number) ? (string) $card->card_number : '',
            ]
        )) {
            gym_access_mark_expiry_reminder_sent((int) $card->id, $until_day);
        }
    }
}

add_action('gym_access_daily_emails', 'gym_access_run_expiry_reminder_cron');
