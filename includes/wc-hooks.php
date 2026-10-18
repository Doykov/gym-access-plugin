<?php
defined('ABSPATH') || exit;

// Валидация при checkout
add_action('woocommerce_checkout_process', function () {
    // Проверяваме различни възможни имена на полето за номер на карта
    $card_number = '';
    
    // Стандартни имена на полета за проверка (по приоритет)
    $possible_field_names = [
        'billing_card_number',
        '_billing_card_number',
        'billing_',  // Директно billing_ както спомена потребителят
        'card_number',
        '_card_number'
    ];
    
    // Първо проверяваме стандартните варианти
    foreach ($possible_field_names as $field_name) {
        if (isset($_POST[$field_name])) {
            $value = $_POST[$field_name];
            if (is_string($value) || is_numeric($value)) {
                $trimmed_value = trim((string)$value);
                if (!empty($trimmed_value)) {
                    $card_number = $trimmed_value;
                    break;
                }
            }
        }
    }
    
    // Ако все още няма стойност, проверяваме всички POST параметри
    if (empty($card_number)) {
        // Стандартни billing полета, които НЕ са номер на карта
        $standard_billing_fields = [
            'billing_first_name', 'billing_last_name', 'billing_company', 
            'billing_address_1', 'billing_address_2', 'billing_city', 
            'billing_state', 'billing_postcode', 'billing_country', 
            'billing_email', 'billing_phone', 'billing_phone_2'
        ];
        
        foreach ($_POST as $key => $value) {
            if (!is_string($key) || (!is_string($value) && !is_numeric($value))) {
                continue;
            }
            
            $trimmed_value = trim((string)$value);
            if (empty($trimmed_value)) {
                continue;
            }
            
            // Проверяваме за полета, които съдържат "card" и "number"
            if ((stripos($key, 'billing') !== false && stripos($key, 'card') !== false && stripos($key, 'number') !== false) ||
                (stripos($key, 'card') !== false && stripos($key, 'number') !== false)) {
                $card_number = $trimmed_value;
                break;
            }
            
            // Проверяваме за полета, които започват с billing_ и имат числова стойност
            if (strpos($key, 'billing_') === 0 && !in_array($key, $standard_billing_fields)) {
                // Ако стойността изглежда като номер на карта (числа или комбинация от числа)
                if (is_numeric($trimmed_value) || preg_match('/^[0-9\s-]+$/', $trimmed_value)) {
                    $card_number = $trimmed_value;
                    break;
                }
            }
        }
    }
    
    // Ако все още няма стойност, показваме грешка
    if (empty($card_number)) {
        wc_add_notice('Моля, въведете номер на абонаментна карта.', 'error');
        return;
    }
});

// Запазване на cwcd_datepicker и номер на карта в order meta
add_action('woocommerce_checkout_update_order_meta', function ($order_id) {
    // Запазване на date picker
    if (!empty($_POST['cwcd_datepicker'])) {
        update_post_meta($order_id, '_cwcd_datepicker', sanitize_text_field($_POST['cwcd_datepicker']));
    }
    
    // Запазване на номер на карта - използваме същата логика като при валидацията
    $card_number = '';
    
    // Стандартни имена на полета за проверка (по приоритет)
    $possible_field_names = [
        'billing_card_number',
        '_billing_card_number',
        'billing_',  // Директно billing_ както спомена потребителят
        'card_number',
        '_card_number'
    ];
    
    // Първо проверяваме стандартните варианти
    foreach ($possible_field_names as $field_name) {
        if (isset($_POST[$field_name])) {
            $value = $_POST[$field_name];
            if (is_string($value) || is_numeric($value)) {
                $trimmed_value = trim((string)$value);
                if (!empty($trimmed_value)) {
                    $card_number = $trimmed_value;
                    break;
                }
            }
        }
    }
    
    // Ако все още няма стойност, проверяваме всички POST параметри
    if (empty($card_number)) {
        // Стандартни billing полета, които НЕ са номер на карта
        $standard_billing_fields = [
            'billing_first_name', 'billing_last_name', 'billing_company', 
            'billing_address_1', 'billing_address_2', 'billing_city', 
            'billing_state', 'billing_postcode', 'billing_country', 
            'billing_email', 'billing_phone', 'billing_phone_2'
        ];
        
        foreach ($_POST as $key => $value) {
            if (!is_string($key) || (!is_string($value) && !is_numeric($value))) {
                continue;
            }
            
            $trimmed_value = trim((string)$value);
            if (empty($trimmed_value)) {
                continue;
            }
            
            // Проверяваме за полета, които съдържат "card" и "number"
            if ((stripos($key, 'billing') !== false && stripos($key, 'card') !== false && stripos($key, 'number') !== false) ||
                (stripos($key, 'card') !== false && stripos($key, 'number') !== false)) {
                $card_number = $trimmed_value;
                break;
            }
            
            // Проверяваме за полета, които започват с billing_ и имат числова стойност
            if (strpos($key, 'billing_') === 0 && !in_array($key, $standard_billing_fields)) {
                // Ако стойността изглежда като номер на карта (числа или комбинация от числа)
                if (is_numeric($trimmed_value) || preg_match('/^[0-9\s-]+$/', $trimmed_value)) {
                    $card_number = $trimmed_value;
                    break;
                }
            }
        }
    }
    
    // Запазваме номера на картата в order meta (и в двата варианта за съвместимост)
    if (!empty($card_number)) {
        update_post_meta($order_id, '_billing_card_number', sanitize_text_field($card_number));
        update_post_meta($order_id, 'billing_card_number', sanitize_text_field($card_number));
        
        // Автоматично мапване: Проверяваме дали има мапване за този номер на карта
        global $wpdb;
        $mapping_table = $wpdb->prefix . 'gym_card_mapping';
        $mapping = $wpdb->get_row($wpdb->prepare(
            "SELECT rfid_uid FROM $mapping_table WHERE card_number=%s LIMIT 1",
            $card_number
        ));
        
        // Ако намерим мапване, запазваме RFID UID в order meta
        if ($mapping && !empty($mapping->rfid_uid)) {
            update_post_meta($order_id, '_rfid_uid', sanitize_text_field($mapping->rfid_uid));
            update_post_meta($order_id, 'rfid_uid', sanitize_text_field($mapping->rfid_uid));
        }
    }
});

// Функция за изчисляване на 1 календарна година (от дата до същата дата следващата година)
function gym_calculate_one_year_from_date($start_date) {
    if (!$start_date) return null;
    
    $start_timestamp = strtotime($start_date);
    if (!$start_timestamp) return null;
    
    // Изчисляваме датата след точно 1 година (от същия ден и месец)
    $end_date = date('Y-m-d H:i:s', strtotime('+1 year', $start_timestamp));
    
    return $end_date;
}

// Мапване на вариации към максимален брой посещения
function gym_get_variation_max_visits($variation_id) {
    // Мапване на variation ID към максимален брой посещения
    // 0 означава неограничен брой посещения
    $variation_max_visits = [
        204 => 8,   // 8 тренировки в рамките на 30 дни
        205 => 12,  // 12 тренировки в рамките на 30 дни
        // Всички останали вариации са неограничени (0)
    ];
    
    if (isset($variation_max_visits[$variation_id])) {
        return $variation_max_visits[$variation_id];
    }
    
    return 0; // Неограничен
}

// Мапване на вариации към продължителност в дни
function gym_get_variation_duration_days($variation_id) {
    // Мапване на variation ID към продължителност в дни
    // Актуализирайте този масив, ако добавите нови вариации
    $variation_durations = [
        // 30 дни
        204 => 30,  // 8 тренировки в рамките на 30 дни
        205 => 30,  // 12 тренировки в рамките на 30 дни
        206 => 30,  // Неограничен брой тренировки в рамките на 30 дни (Мъж)
        1606 => 30, // Неограничен брой тренировки в рамките на 30 дни (Жена)
        1607 => 30, // Неограничен брой тренировки в рамките на 30 дни (Ученик)
        167 => 30,  // Неограничен брой тренировки в рамките на 30 дни (Ученик)
        
        // 90 дни
        207 => 90,  // Неограничен брой тренировки в рамките на 90 дни (Мъж)
        1611 => 90, // Неограничен брой тренировки в рамките на 90 дни (Жена)
        168 => 90,  // Неограничен брой тренировки в рамките на 90 дни (Ученик)
        
        // 180 дни
        1609 => 180, // Неограничен брой тренировки в рамките на 180 дни (Мъж)
        
        // 1 календарна година (365 дни) - използваме специална стойност 'year'
        1610 => 'year', // Неограничен брой тренировки в рамките на една календарна година
        1612 => 'year', // Неограничен брой тренировки в рамките на една календарна година
    ];
    
    if (isset($variation_durations[$variation_id])) {
        return $variation_durations[$variation_id];
    }
    
    return null;
}

// Функция за нормализиране на дата
function gym_normalize_date($date_string) {
    if (!$date_string) return null;
    
    // Премахваме излишни интервали
    $date_string = trim($date_string);
    
    // Първо опитваме формат от date picker плъгина: dd-mm-yy (например "26-12-2025")
    // Трябва да конвертираме yy в YYYY (ако е 2 цифри, приемаме 20xx)
    if (preg_match('/^(\d{2})-(\d{2})-(\d{2,4})$/', $date_string, $matches)) {
        $day = $matches[1];
        $month = $matches[2];
        $year = $matches[3];
        
        // Ако годината е 2 цифри, добавяме 20
        if (strlen($year) == 2) {
            $year = '20' . $year;
        }
        
        $date_string = "$day-$month-$year";
    }
    
    // Първо опитваме стандартния strtotime
    $timestamp = strtotime($date_string);
    if ($timestamp) {
        return date('Y-m-d H:i:s', $timestamp);
    }
    
    // Ако не работи, опитваме различни формати
    $date_formats = ['d-m-Y', 'Y-m-d', 'd/m/Y', 'Y/m/d', 'd.m.Y', 'Y.m.d', 'dd-mm-yy'];
    foreach ($date_formats as $format) {
        // За dd-mm-yy формат, използваме специален парсинг
        if ($format === 'dd-mm-yy') {
            if (preg_match('/^(\d{2})-(\d{2})-(\d{2,4})$/', $date_string, $matches)) {
                $day = $matches[1];
                $month = $matches[2];
                $year = $matches[3];
                if (strlen($year) == 2) {
                    $year = '20' . $year;
                }
                $date_string = "$day-$month-$year";
                $format = 'd-m-Y';
            }
        }
        
        $date_obj = DateTime::createFromFormat($format, $date_string);
        if ($date_obj) {
            return $date_obj->format('Y-m-d H:i:s');
        }
    }
    
    return null;
}

// Функция за извличане на всички данни от поръчката
function gym_extract_subscription_data_from_order($order) {
    $data = [
        'card_number' => null,
        'rfid_uid' => null,
        'holder_name' => null,
        'email' => null,
        'phone' => null,
        'valid_from' => null,
        'valid_until' => null,
        'max_visits' => 0
    ];
    
    // Номер на картата - проверяваме различни възможни места
    // Приоритет: специфични имена за карта, след това общи варианти
    $card_number_keys = [
        '_billing_card_number',  // Стандартен начин за custom billing полета (с подчертавка)
        'billing_card_number',   // Без подчертавка в началото
        'billing_',              // Директно billing_ както спомена потребителят
        '_card_number',
        'card_number'
    ];
    
    // Първо проверяваме order meta с приоритет на специфичните имена
    foreach ($card_number_keys as $key) {
        $value = $order->get_meta($key);
        if ($value) {
            $data['card_number'] = sanitize_text_field($value);
            break;
        }
    }
    
    // Ако не е намерен, опитваме се да го извлечем директно от order данните
    if (!$data['card_number']) {
        // WooCommerce може да запазва custom billing полета като order meta
        // Пробваме и с get_post_meta директно
        $order_id = $order->get_id();
        foreach ($card_number_keys as $key) {
            $value = get_post_meta($order_id, $key, true);
            if ($value) {
                $data['card_number'] = sanitize_text_field($value);
                break;
            }
        }
    }
    
    // Fallback: Проверяваме за стари поръчки с префикс billing_ (без card_number)
    // Това е по-рисково, затова го проверяваме последно и само ако стойността изглежда като номер на карта
    if (!$data['card_number']) {
        $order_id = $order->get_id();
        $billing_card = get_post_meta($order_id, 'billing_', true);
        // Проверяваме дали стойността изглежда като номер на карта (число или комбинация от числа)
        if ($billing_card && (is_numeric($billing_card) || preg_match('/^[0-9\s-]+$/', $billing_card))) {
            $data['card_number'] = sanitize_text_field($billing_card);
        }
    }
    
    // Проверяваме и в order items (ако е запазено там)
    if (!$data['card_number']) {
        foreach ($order->get_items() as $item) {
            foreach ($card_number_keys as $key) {
                $value = $item->get_meta($key);
                if ($value) {
                    $data['card_number'] = sanitize_text_field($value);
                    break 2;
                }
            }
        }
    }
    
    // RFID UID - проверяваме в order meta
    $rfid_uid_keys = ['_rfid_uid', 'rfid_uid'];
    foreach ($rfid_uid_keys as $key) {
        $value = $order->get_meta($key);
        if ($value) {
            $data['rfid_uid'] = sanitize_text_field(strtoupper($value));
            break;
        }
    }
    
    // Ако няма rfid_uid в order meta, проверяваме директно в post meta
    if (!$data['rfid_uid']) {
        $order_id = $order->get_id();
        foreach ($rfid_uid_keys as $key) {
            $value = get_post_meta($order_id, $key, true);
            if ($value) {
                $data['rfid_uid'] = sanitize_text_field(strtoupper($value));
                break;
            }
        }
    }
    
    // Име
    $data['holder_name'] = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
    
    // Имейл
    $data['email'] = $order->get_billing_email();
    
    // Телефон
    $data['phone'] = $order->get_billing_phone();
    
    // Валидна от дата - проверяваме различни възможни полета
    $valid_from_keys = [
        '_cwcd_datepicker', 'cwcd_datepicker',
        '_subscription_start_date', '_start_date', '_valid_from',
        '_subscription_valid_from', '_subscription_date',
        'subscription_start_date', 'start_date', 'valid_from'
    ];
    
    foreach ($valid_from_keys as $key) {
        $value = $order->get_meta($key);
        if ($value) {
            $data['valid_from'] = gym_normalize_date($value);
            if ($data['valid_from']) break;
        }
    }
    
    // Проверяваме и в order items
    if (!$data['valid_from']) {
        foreach ($order->get_items() as $item_id => $item) {
            // Първо проверяваме специфичния ключ от date picker плъгина (на български)
            // Използваме wc_get_order_item_meta() както в date picker плъгина
            $date_value = wc_get_order_item_meta($item_id, 'Начална дата на абонамента', true);
            if ($date_value) {
                $data['valid_from'] = gym_normalize_date($date_value);
                if ($data['valid_from']) break;
            }
            
            // Също проверяваме чрез get_meta() метода
            $date_value = $item->get_meta('Начална дата на абонамента');
            if ($date_value) {
                $data['valid_from'] = gym_normalize_date($date_value);
                if ($data['valid_from']) break;
            }
            
            // След това проверяваме другите ключове
            foreach ($valid_from_keys as $key) {
                $value = $item->get_meta($key);
                if ($value) {
                    $data['valid_from'] = gym_normalize_date($value);
                    if ($data['valid_from']) break 2;
                }
            }
        }
    }
    
    // Валидна до дата - проверяваме различни възможни полета
    $valid_until_keys = [
        '_subscription_valid_until', '_valid_until', '_end_date',
        '_subscription_end_date', 'subscription_valid_until',
        'valid_until', 'end_date', 'subscription_end_date'
    ];
    
    foreach ($valid_until_keys as $key) {
        $value = $order->get_meta($key);
        if ($value) {
            $data['valid_until'] = gym_normalize_date($value);
            if ($data['valid_until']) break;
        }
    }
    
    // Проверяваме и в order items
    if (!$data['valid_until']) {
        foreach ($order->get_items() as $item) {
            // Първо проверяваме за директна крайна дата в meta полетата
            foreach ($valid_until_keys as $key) {
                $value = $item->get_meta($key);
                if ($value) {
                    $data['valid_until'] = gym_normalize_date($value);
                    if ($data['valid_until']) break 2;
                }
            }
            
            // Ако имаме valid_from, опитваме да изчислим valid_until от variation ID
            if (!$data['valid_until'] && $data['valid_from']) {
                $variation_id = $item->get_variation_id();
                if ($variation_id) {
                    $duration = gym_get_variation_duration_days($variation_id);
                    if ($duration) {
                        // Ако е 'year', изчисляваме 1 календарна година
                        if ($duration === 'year') {
                            $data['valid_until'] = gym_calculate_one_year_from_date($data['valid_from']);
                        } else {
                            // Иначе използваме броя дни
                            $data['valid_until'] = date('Y-m-d H:i:s', strtotime($data['valid_from'] . " +" . $duration . " days"));
                        }
                        if ($data['valid_until']) break;
                    }
                    
                    // Fallback: Проверяваме името на вариацията за "календарна година" или "1 година"
                    if (!$data['valid_until']) {
                        $variation = wc_get_product($variation_id);
                        if ($variation) {
                            $variation_name = strtolower($variation->get_name());
                            if (stripos($variation_name, 'календарна година') !== false || 
                                stripos($variation_name, '1 година') !== false ||
                                stripos($variation_name, 'една година') !== false ||
                                stripos($variation_name, 'година') !== false && stripos($variation_name, 'календарна') !== false) {
                                $data['valid_until'] = gym_calculate_one_year_from_date($data['valid_from']);
                                if ($data['valid_until']) break;
                            }
                        }
                    }
                }
            }
            
            // Fallback: Ако имаме продължителност в дни/месеци и valid_from, изчисляваме valid_until
            if (!$data['valid_until'] && $data['valid_from']) {
                $days = $item->get_meta('_subscription_days');
                if ($days) {
                    $data['valid_until'] = date('Y-m-d H:i:s', strtotime($data['valid_from'] . " +" . (int)$days . " days"));
                    break;
                }
                
                $months = $item->get_meta('_subscription_months');
                if ($months) {
                    $data['valid_until'] = date('Y-m-d H:i:s', strtotime($data['valid_from'] . " +" . (int)$months . " months"));
                    break;
                }
            }
        }
    }
    
    // Извличане на max_visits от вариациите
    foreach ($order->get_items() as $item) {
        $variation_id = $item->get_variation_id();
        if ($variation_id) {
            $max_visits = gym_get_variation_max_visits($variation_id);
            if ($max_visits > 0) {
                // Ако имаме няколко продукта, използваме най-големия лимит
                if ($data['max_visits'] == 0 || $max_visits > $data['max_visits']) {
                    $data['max_visits'] = $max_visits;
                }
            }
        }
    }
    
    // Автоматично мапване: Ако имаме номер на карта, но няма rfid_uid, проверяваме в mapping таблицата
    if (!empty($data['card_number']) && empty($data['rfid_uid'])) {
        global $wpdb;
        $mapping_table = $wpdb->prefix . 'gym_card_mapping';
        $mapping = $wpdb->get_row($wpdb->prepare(
            "SELECT rfid_uid FROM $mapping_table WHERE card_number=%s LIMIT 1",
            $data['card_number']
        ));
        
        if ($mapping && !empty($mapping->rfid_uid)) {
            $data['rfid_uid'] = $mapping->rfid_uid;
        }
    }
    
    return $data;
}

// Функция за автоматично извличане и запазване на данни от поръчка
function gym_process_order_data($order_id) {
    if (!$order_id) {
        return;
    }
    
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }

    // Обработваме само поръчки със статус "приключена" (completed)
    if ($order->get_status() !== 'completed') {
        return;
    }

    // Проверяваме дали вече е обработена тази поръчка (за да избегнем дублиране)
    $order_processed = get_post_meta($order_id, '_gym_order_processed', true);
    if ($order_processed === '1') {
        return; // Вече е обработена
    }

    // Извличаме всички данни директно от поръчката
    $data = gym_extract_subscription_data_from_order($order);
    
    // Проверяваме дали има номер на карта
    if (empty($data['card_number'])) {
        return;
    }
    
    // Ако няма valid_from, използваме текущата дата
    if (empty($data['valid_from'])) {
        $data['valid_from'] = current_time('mysql');
    }
    
    // Ако няма valid_until, не можем да създадем/актуализираме картата
    if (empty($data['valid_until'])) {
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'gym_cards';
    $mapping_table = $wpdb->prefix . 'gym_card_mapping';
    
    // Автоматично мапване: Проверяваме дали има мапване за този номер на карта
    // Ако няма rfid_uid в данните, но има мапване, попълваме го автоматично
    if (empty($data['rfid_uid']) && !empty($data['card_number'])) {
        $mapping = $wpdb->get_row($wpdb->prepare(
            "SELECT rfid_uid FROM $mapping_table WHERE card_number=%s LIMIT 1",
            $data['card_number']
        ));
        
        if ($mapping && !empty($mapping->rfid_uid)) {
            $data['rfid_uid'] = $mapping->rfid_uid;
        }
    }
    
    // Запазваме данните
    $result = $wpdb->replace($table, [
        'card_number' => $data['card_number'],
        'rfid_uid' => isset($data['rfid_uid']) ? $data['rfid_uid'] : null,
        'holder_name' => $data['holder_name'],
        'email' => $data['email'],
        'phone' => $data['phone'],
        'valid_from' => $data['valid_from'],
        'valid_until' => $data['valid_until'],
        'max_visits' => $data['max_visits'],
        'status' => 'active'
    ]);
    
    // Маркираме поръчката като обработена само ако запазването е успешно
    if ($result !== false) {
        update_post_meta($order_id, '_gym_order_processed', '1');
    }
}

// Извикваме функцията при различни hooks за по-надеждно автоматично извличане
// Използваме различни приоритети, за да гарантираме че данните са запазени

// Hook след обработка на поръчката (след запазване на meta данни)
add_action('woocommerce_checkout_order_processed', 'gym_process_order_data', 30, 1);

// Hook при завършване на плащането
add_action('woocommerce_payment_complete', 'gym_process_order_data', 20, 1);

// Hook при промяна на статуса на поръчката (само приключени поръчки)
add_action('woocommerce_order_status_completed', 'gym_process_order_data', 20, 1);

// Hook на страницата за благодарност (thank you page) - за случаи когато плащането е асинхронно
add_action('woocommerce_thankyou', function ($order_id) {
    if ($order_id) {
        gym_process_order_data($order_id);
    }
}, 20, 1);
