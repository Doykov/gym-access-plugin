<?php
defined('ABSPATH') || exit;

add_action('admin_menu', function () {
    add_menu_page(
        'Fitness Access',
        'Fitness Access',
        'manage_woocommerce', // Позволява достъп за администратори и shop_manager
        'gym-access',
        'gym_access_page',
        'dashicons-id'
    );
});

// Добавяне на CSS стилове за мобилни устройства
add_action('admin_head', function () {
    $screen = get_current_screen();
    if ($screen && $screen->id === 'toplevel_page_gym-access') {
        ?>
        <style>
            /* Responsive стилове за мобилни устройства */
            @media screen and (max-width: 782px) {
                /* Премахваме ограниченията по ширина */
                .gym-access-container .card {
                    max-width: 100% !important;
                    width: 100%;
                }
                
                /* Табове - вертикални на мобилни */
                .nav-tab-wrapper {
                    display: flex;
                    flex-direction: column;
                    border-bottom: 1px solid #ccc;
                }
                
                .nav-tab {
                    display: block;
                    margin-bottom: 5px;
                    text-align: center;
                    padding: 10px;
                    border-radius: 4px;
                }
                
                .nav-tab-active {
                    background-color: #2271b1;
                    color: white;
                }
                
                /* Формата за търсене */
                .gym-search-form {
                    padding: 10px !important;
                }
                
                .gym-search-form .form-table th,
                .gym-search-form .form-table td {
                    display: block;
                    width: 100% !important;
                    padding: 5px 0;
                }
                
                .gym-search-form .form-table th {
                    margin-bottom: 5px;
                }
                
                .gym-search-form input[type="text"] {
                    width: 100% !important;
                    max-width: 100% !important;
                }
                
                /* Таблица със списък с карти - картки на мобилни */
                .gym-cards-table-wrapper {
                    overflow-x: auto;
                    -webkit-overflow-scrolling: touch;
                }
                
                .gym-cards-table {
                    min-width: 800px;
                }
                
                /* Таблица с поръчки - картки на мобилни */
                .gym-orders-table-wrapper {
                    overflow-x: auto;
                    -webkit-overflow-scrolling: touch;
                }
                
                .gym-orders-table {
                    min-width: 600px;
                }
                
                /* Картични стилове за мобилни (алтернативен подход) */
                @media screen and (max-width: 600px) {
                    .gym-orders-table-wrapper,
                    .gym-cards-table-wrapper {
                        overflow-x: visible;
                    }
                    
                    .gym-orders-table,
                    .gym-cards-table {
                        display: block;
                        min-width: 100%;
                        border: none;
                    }
                    
                    .gym-orders-table thead,
                    .gym-cards-table thead {
                        display: none;
                    }
                    
                    .gym-orders-table tbody,
                    .gym-cards-table tbody {
                        display: block;
                    }
                    
                    .gym-orders-table tr,
                    .gym-cards-table tr {
                        display: block;
                        margin-bottom: 15px;
                        border: 1px solid #ddd;
                        border-radius: 4px;
                        padding: 12px;
                        background: #fff;
                        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                    }
                    
                    .gym-orders-table td,
                    .gym-cards-table td {
                        display: flex;
                        justify-content: space-between;
                        align-items: flex-start;
                        padding: 10px 0;
                        border: none;
                        border-bottom: 1px solid #f0f0f0;
                        word-break: break-word;
                    }
                    
                    .gym-orders-table td:last-child,
                    .gym-cards-table td:last-child {
                        border-bottom: none;
                        padding-bottom: 0;
                    }
                    
                    .gym-orders-table td::before,
                    .gym-cards-table td::before {
                        content: attr(data-label);
                        font-weight: 600;
                        margin-right: 15px;
                        flex-shrink: 0;
                        min-width: 130px;
                        color: #555;
                    }
                    
                    /* Подобрени стилове за действията */
                    .gym-orders-table td.gym-actions,
                    .gym-cards-table td.gym-actions {
                        flex-direction: column;
                        align-items: stretch;
                    }
                    
                    .gym-orders-table td.gym-actions::before,
                    .gym-cards-table td.gym-actions::before {
                        margin-bottom: 10px;
                    }
                }
                
                /* Бутони - пълна ширина на мобилни */
                .gym-search-form .button,
                .gym-search-form p .button {
                    width: 100%;
                    margin-bottom: 10px;
                    text-align: center;
                }
                
                /* Форми за тестване */
                .gym-test-form {
                    margin: 10px 0;
                    padding: 10px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                }
                
                .gym-test-form label {
                    display: block;
                    margin-bottom: 5px;
                }
                
                .gym-test-form .button {
                    width: 100%;
                    margin-top: 10px;
                }
                
                /* Форма за добавяне/редактиране */
                .form-table th,
                .form-table td {
                    display: block;
                    width: 100% !important;
                    padding: 5px 0;
                }
                
                .form-table th {
                    margin-bottom: 5px;
                    padding-top: 10px;
                }
                
                .form-table input,
                .form-table select,
                .form-table textarea {
                    width: 100% !important;
                    max-width: 100% !important;
                }
                
                /* Бутони в действията */
                .gym-actions {
                    display: flex;
                    flex-direction: column;
                    gap: 5px;
                }
                
                .gym-actions .button {
                    width: 100%;
                    margin: 0;
                }
                
                .gym-actions form {
                    width: 100%;
                }
                
                /* Submit бутони */
                .submit {
                    margin-top: 20px;
                }
                
                .submit .button {
                    width: 100%;
                    margin-bottom: 10px;
                }
                
                /* Подобрени стилове за notice съобщения */
                .notice {
                    margin: 15px 0;
                    padding: 12px;
                }
            }
            
            /* Tablet стилове */
            @media screen and (min-width: 783px) and (max-width: 1024px) {
                .gym-access-container .card {
                    max-width: 100% !important;
                }
                
                .gym-cards-table-wrapper,
                .gym-orders-table-wrapper {
                    overflow-x: auto;
                }
            }
            
            /* Общи стилове */
            .gym-access-container {
                position: relative;
            }
            
            /* Премахване на ограничението по ширина в таба за тестване */
            .gym-test-tab .card {
                max-width: 100% !important;
            }
            
            /* Подобрени стилове за tab content */
            .tab-content {
                margin-top: 20px;
            }
            
            /* По-добри стилове за datetime-local input на мобилни */
            @media screen and (max-width: 782px) {
                input[type="datetime-local"] {
                    font-size: 16px; /* Предотвратява zoom на iOS */
                }
                
                input[type="number"] {
                    font-size: 16px; /* Предотвратява zoom на iOS */
                }
            }
            
            /* Оптимизация за по-малки екрани */
            @media screen and (max-width: 480px) {
                .wrap h1 {
                    font-size: 20px;
                    margin-bottom: 15px;
                }
                
                .wrap h2,
                .wrap h3 {
                    font-size: 18px;
                }
                
                .card {
                    padding: 15px;
                }
            }
        </style>
        <?php
    }
});

// Обработка на POST заявки
add_action('admin_init', function () {
    if (!isset($_POST['gym_action']) || !current_user_can('manage_woocommerce')) {
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'gym_cards';
    
    check_admin_referer('gym_access_action');

    if ($_POST['gym_action'] === 'save') {
        $id = isset($_POST['card_id']) ? intval($_POST['card_id']) : 0;

        // Подготвяме датите от datetime-local полетата към MySQL формат
        $valid_from_raw  = isset($_POST['valid_from']) ? sanitize_text_field($_POST['valid_from']) : '';
        $valid_until_raw = isset($_POST['valid_until']) ? sanitize_text_field($_POST['valid_until']) : '';

        $valid_from = null;
        if (!empty($valid_from_raw)) {
            // Пример вход: 2025-03-01T12:30 -> 2025-03-01 12:30:00
            $valid_from = str_replace('T', ' ', $valid_from_raw);
            if (strlen($valid_from) === 16) {
                $valid_from .= ':00';
            }
        }

        $valid_until = null;
        if (!empty($valid_until_raw)) {
            $valid_until = str_replace('T', ' ', $valid_until_raw);
            if (strlen($valid_until) === 16) {
                $valid_until .= ':00';
            }
        }

        $card_number = sanitize_text_field($_POST['card_number']);

        $data = [
            'card_number' => $card_number,
            'rfid_uid' => isset($_POST['rfid_uid']) ? sanitize_text_field($_POST['rfid_uid']) : null,
            'holder_name' => sanitize_text_field($_POST['holder_name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'valid_from' => $valid_from,
            'valid_until' => $valid_until,
            'max_visits' => isset($_POST['max_visits']) ? intval($_POST['max_visits']) : 0,
            'status' => sanitize_text_field($_POST['status'])
        ];

        if ($id > 0) {
            // Ясно редактиране на съществуваща карта
            $wpdb->update($table, $data, ['id' => $id]);
            set_transient('gym_card_saved_' . get_current_user_id(), 'Картата е актуализирана успешно!', 300);
            wp_safe_redirect(admin_url('admin.php?page=gym-access&tab=list'));
            exit;
        } else {
            // Нов запис от формата – ако вече има карта с този номер, я реактивираме (update),
            // вместо да създаваме нов ред и да удряме UNIQUE грешка.
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $table WHERE card_number = %s LIMIT 1",
                $card_number
            ));

            if ($existing) {
                $wpdb->update($table, $data, ['id' => $existing->id]);
                set_transient(
                    'gym_card_saved_' . get_current_user_id(),
                    'Картата е реактивирана и актуализирана успешно!',
                    300
                );
            } else {
                $wpdb->insert($table, $data);
                set_transient('gym_card_saved_' . get_current_user_id(), 'Картата е добавена успешно!', 300);
            }

            wp_safe_redirect(admin_url('admin.php?page=gym-access&tab=list'));
            exit;
        }
    } elseif ($_POST['gym_action'] === 'delete' && isset($_POST['card_id'])) {
        $wpdb->delete($table, ['id' => intval($_POST['card_id'])]);
        set_transient('gym_card_deleted_' . get_current_user_id(), 'Картата е изтрита успешно!', 300);
        wp_safe_redirect(admin_url('admin.php?page=gym-access&tab=list'));
        exit;
    } elseif ($_POST['gym_action'] === 'save_card_email_prefs') {
        $card_ids = isset($_POST['card_email_ids']) ? array_map('intval', (array) $_POST['card_email_ids']) : [];
        foreach ($card_ids as $cid) {
            if ($cid <= 0) {
                continue;
            }
            $on = !empty($_POST['receive_email'][ $cid ]) ? 1 : 0;
            $wpdb->update($table, ['receive_emails' => $on], ['id' => $cid], ['%d'], ['%d']);
        }
        set_transient('gym_card_saved_' . get_current_user_id(), 'Маркировките за имейли са запазени.', 300);
        $return = isset($_POST['gym_list_return_url']) ? wp_unslash($_POST['gym_list_return_url']) : '';
        $fallback = admin_url('admin.php?page=gym-access&tab=list');
        wp_safe_redirect(wp_validate_redirect($return, $fallback));
        exit;
    } elseif ($_POST['gym_action'] === 'test_order') {
        $order_id = isset($_POST['test_order_id']) ? intval($_POST['test_order_id']) : 0;
        $save_to_db = isset($_POST['save_to_db']) && $_POST['save_to_db'] === '1';
        
        // Събираме параметрите за търсене за редирект
        $search_params = [];
        if (isset($_POST['search_client_name']) && $_POST['search_client_name']) {
            $search_params['search_client_name'] = sanitize_text_field($_POST['search_client_name']);
        }
        
        if ($order_id > 0) {
            require_once GYM_ACCESS_PATH . 'includes/wc-hooks.php';
            $order = wc_get_order($order_id);
            
            if ($order) {
                $extracted_data = gym_extract_subscription_data_from_order($order);
                
                // Запазваме резултатите в transient за показване
                set_transient('gym_test_result_' . get_current_user_id(), [
                    'order_id' => $order_id,
                    'data' => $extracted_data,
                    'save_to_db' => $save_to_db
                ], 300);
                
                // Ако е избрано, записваме в базата само при поръчка със статус "приключена"
                if ($save_to_db && $extracted_data['card_number'] && $extracted_data['valid_until']) {
                    if ($order->get_status() !== 'completed') {
                        set_transient('gym_test_error_' . get_current_user_id(), 'Картата се добавя само от поръчки със статус „Приключена“. Тази поръчка е: ' . wc_get_order_status_name($order->get_status()), 300);
                    } else {
                        if (!$extracted_data['valid_from']) {
                            $extracted_data['valid_from'] = current_time('mysql');
                        }
                        
                        $wpdb->replace($table, [
                            'card_number' => $extracted_data['card_number'],
                            'holder_name' => $extracted_data['holder_name'],
                            'email' => $extracted_data['email'],
                            'phone' => $extracted_data['phone'],
                            'valid_from' => $extracted_data['valid_from'],
                            'valid_until' => $extracted_data['valid_until'],
                            'status' => 'active'
                        ]);
                        
                        set_transient('gym_test_saved_' . get_current_user_id(), true, 300);
                    }
                }
                
                // Редирект за запазване на параметрите за търсене
                $redirect_url = admin_url('admin.php?page=gym-access&tab=test');
                if (!empty($search_params)) {
                    $redirect_url = add_query_arg($search_params, $redirect_url);
                }
                wp_safe_redirect($redirect_url);
                exit;
            } else {
                set_transient('gym_test_error_' . get_current_user_id(), 'Поръчката не е намерена!', 300);
                $redirect_url = admin_url('admin.php?page=gym-access&tab=test');
                if (!empty($search_params)) {
                    $redirect_url = add_query_arg($search_params, $redirect_url);
                }
                wp_safe_redirect($redirect_url);
                exit;
            }
        }
    } elseif ($_POST['gym_action'] === 'import_mapping') {
        // Обработка на импортиране на XLS/CSV файл за мапване
        if (!isset($_FILES['mapping_file'])) {
            set_transient('gym_import_error_' . get_current_user_id(), 'Файлът не е качен. Моля, изберете файл за импортиране.', 300);
            wp_safe_redirect(admin_url('admin.php?page=gym-access&tab=mapping'));
            exit;
        }
        
        if ($_FILES['mapping_file']['error'] !== UPLOAD_ERR_OK) {
            $error_messages = [
                UPLOAD_ERR_INI_SIZE => 'Файлът надвишава максималния размер, разрешен от PHP.',
                UPLOAD_ERR_FORM_SIZE => 'Файлът надвишава максималния размер, разрешен от формата.',
                UPLOAD_ERR_PARTIAL => 'Файлът е качен частично.',
                UPLOAD_ERR_NO_FILE => 'Не е избран файл.',
                UPLOAD_ERR_NO_TMP_DIR => 'Липсва временна директория.',
                UPLOAD_ERR_CANT_WRITE => 'Неуспешно записване на файла на диск.',
                UPLOAD_ERR_EXTENSION => 'Качването е спряно от разширение.',
            ];
            $error_msg = isset($error_messages[$_FILES['mapping_file']['error']]) 
                ? $error_messages[$_FILES['mapping_file']['error']] 
                : 'Неизвестна грешка при качване на файла.';
            set_transient('gym_import_error_' . get_current_user_id(), $error_msg, 300);
            wp_safe_redirect(admin_url('admin.php?page=gym-access&tab=mapping'));
            exit;
        }
        
        if (isset($_FILES['mapping_file']) && $_FILES['mapping_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['mapping_file'];
            $mapping_table = $wpdb->prefix . 'gym_card_mapping';
            
            $imported = 0;
            $updated = 0;
            $errors = [];
            
            // Проверяваме типа на файла
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if ($file_ext === 'csv' || $file_ext === 'txt') {
                // CSV/TXT файл - опитваме се с различни разделители
                $delimiters = [',', ';', "\t"];
                $handle = false;
                $delimiter = ',';
                
                // Опитваме се да отворим файла и да определим разделителя
                foreach ($delimiters as $delim) {
                    $test_handle = fopen($file['tmp_name'], 'r');
                    if ($test_handle !== false) {
                        $test_line = fgets($test_handle);
                        fclose($test_handle);
                        if ($test_line && strpos($test_line, $delim) !== false) {
                            $delimiter = $delim;
                            break;
                        }
                    }
                }
                
                $handle = fopen($file['tmp_name'], 'r');
                if ($handle !== false) {
                    $line_number = 0;
                    while (($data = fgetcsv($handle, 1000, $delimiter)) !== false) {
                        $line_number++;
                        
                        // Пропускаме празни редове
                        if (count($data) < 2 || (count($data) === 2 && empty(trim($data[0])) && empty(trim($data[1])))) {
                            continue;
                        }
                        
                        // Пропускаме първия ред ако е header
                        if ($line_number === 1 && (
                            strtolower(trim($data[0])) === 'card_number' || 
                            strtolower(trim($data[0])) === 'номер' || 
                            strtolower(trim($data[0])) === 'card' ||
                            strtolower(trim($data[0])) === 'wiegand_code' ||
                            strtolower(trim($data[0])) === 'wiegand'
                        )) {
                            continue;
                        }
                        
                        if (count($data) >= 2) {
                            $card_number = trim($data[0]);
                            $rfid_uid = trim($data[1]); // Wiegand код в DEC формат (десетично число)
                            
                            // Валидация: card_number трябва да е число или низ, rfid_uid трябва да е число
                            if (!empty($card_number) && !empty($rfid_uid)) {
                                // Проверяваме дали rfid_uid е валидно число
                                if (!is_numeric($rfid_uid)) {
                                    $errors[] = "Ред $line_number: Wiegand кодът '$rfid_uid' не е валидно число";
                                    continue;
                                }
                                
                                // Проверяваме дали вече съществува
                                $existing = $wpdb->get_row($wpdb->prepare(
                                    "SELECT id FROM $mapping_table WHERE card_number=%s OR rfid_uid=%s",
                                    $card_number, $rfid_uid
                                ));
                                
                                if ($existing) {
                                    // Актуализираме съществуващ запис
                                    $wpdb->update($mapping_table, [
                                        'card_number' => $card_number,
                                        'rfid_uid' => $rfid_uid
                                    ], ['id' => $existing->id]);
                                    $updated++;
                                } else {
                                    // Добавяме нов запис
                                    $result = $wpdb->insert($mapping_table, [
                                        'card_number' => $card_number,
                                        'rfid_uid' => $rfid_uid
                                    ]);
                                    if ($result === false) {
                                        $errors[] = "Ред $line_number: Грешка при запис в базата данни: " . $wpdb->last_error;
                                    } else {
                                        $imported++;
                                    }
                                }
                            } else {
                                $errors[] = "Ред $line_number: Липсват данни (card_number: '$card_number', wiegand_code: '$rfid_uid')";
                            }
                        } else {
                            $errors[] = "Ред $line_number: Недостатъчно колони (очаквани: 2, намерени: " . count($data) . ")";
                        }
                    }
                    fclose($handle);
                } else {
                    set_transient('gym_import_error_' . get_current_user_id(), 'Неуспешно отваряне на файла.', 300);
                    wp_safe_redirect(admin_url('admin.php?page=gym-access&tab=mapping'));
                    exit;
                }
            } elseif ($file_ext === 'xls' || $file_ext === 'xlsx') {
                // XLS/XLSX файл - опитваме се да го прочетем като CSV
                // Excel файлове могат да се отворят като CSV, но разделителят може да е различен
                $delimiters = [',', ';', "\t"];
                $handle = false;
                $delimiter = ',';
                
                // Опитваме се да отворим файла и да определим разделителя
                foreach ($delimiters as $delim) {
                    $test_handle = @fopen($file['tmp_name'], 'r');
                    if ($test_handle !== false) {
                        $test_line = fgets($test_handle);
                        fclose($test_handle);
                        if ($test_line && strpos($test_line, $delim) !== false) {
                            $delimiter = $delim;
                            break;
                        }
                    }
                }
                
                $handle = @fopen($file['tmp_name'], 'r');
                if ($handle !== false) {
                    $line_number = 0;
                    while (($data = fgetcsv($handle, 1000, $delimiter)) !== false) {
                        $line_number++;
                        
                        // Пропускаме празни редове
                        if (count($data) < 2 || (count($data) === 2 && empty(trim($data[0])) && empty(trim($data[1])))) {
                            continue;
                        }
                        
                        // Пропускаме първия ред ако е header
                        if ($line_number === 1 && (
                            strtolower(trim($data[0])) === 'card_number' || 
                            strtolower(trim($data[0])) === 'номер' || 
                            strtolower(trim($data[0])) === 'card' ||
                            strtolower(trim($data[0])) === 'wiegand_code' ||
                            strtolower(trim($data[0])) === 'wiegand'
                        )) {
                            continue;
                        }
                        
                        if (count($data) >= 2) {
                            $card_number = trim($data[0]);
                            $rfid_uid = trim($data[1]); // Wiegand код в DEC формат (десетично число)
                            
                            // Валидация: rfid_uid трябва да е число
                            if (!empty($card_number) && !empty($rfid_uid)) {
                                if (!is_numeric($rfid_uid)) {
                                    $errors[] = "Ред $line_number: Wiegand кодът '$rfid_uid' не е валидно число";
                                    continue;
                                }
                                
                                $existing = $wpdb->get_row($wpdb->prepare(
                                    "SELECT id FROM $mapping_table WHERE card_number=%s OR rfid_uid=%s",
                                    $card_number, $rfid_uid
                                ));
                                
                                if ($existing) {
                                    $wpdb->update($mapping_table, [
                                        'card_number' => $card_number,
                                        'rfid_uid' => $rfid_uid
                                    ], ['id' => $existing->id]);
                                    $updated++;
                                } else {
                                    $result = $wpdb->insert($mapping_table, [
                                        'card_number' => $card_number,
                                        'rfid_uid' => $rfid_uid
                                    ]);
                                    if ($result === false) {
                                        $errors[] = "Ред $line_number: Грешка при запис в базата данни: " . $wpdb->last_error;
                                    } else {
                                        $imported++;
                                    }
                                }
                            } else {
                                $errors[] = "Ред $line_number: Липсват данни";
                            }
                        } else {
                            $errors[] = "Ред $line_number: Недостатъчно колони";
                        }
                    }
                    fclose($handle);
                } else {
                    set_transient('gym_import_error_' . get_current_user_id(), 'Грешка при четене на XLS/XLSX файла. Моля, конвертирайте файла в CSV формат (File → Save As → CSV) или използвайте CSV файл.', 300);
                }
            } else {
                set_transient('gym_import_error_' . get_current_user_id(), 'Неподдържан формат на файл. Моля, използвайте CSV или XLS файл.', 300);
            }
            
            if ($imported > 0 || $updated > 0) {
                $message = "Импортирани: $imported нови записа, актуализирани: $updated записа.";
                if (!empty($errors)) {
                    $message .= " Грешки: " . count($errors);
                }
                set_transient('gym_import_success_' . get_current_user_id(), $message, 300);
            } elseif (!empty($errors)) {
                // Ако има грешки, но няма импортирани записи
                $error_message = "Грешки при импортиране: " . implode('<br>', array_slice($errors, 0, 10));
                if (count($errors) > 10) {
                    $error_message .= "<br>... и още " . (count($errors) - 10) . " грешки.";
                }
                set_transient('gym_import_error_' . get_current_user_id(), $error_message, 300);
            } elseif ($imported == 0 && $updated == 0) {
                set_transient('gym_import_error_' . get_current_user_id(), 'Не са намерени валидни данни за импортиране. Моля, проверете формата на файла.', 300);
            }
            
            wp_safe_redirect(admin_url('admin.php?page=gym-access&tab=mapping'));
            exit;
        }
    } elseif ($_POST['gym_action'] === 'delete_mapping' && isset($_POST['mapping_id'])) {
        $mapping_table = $wpdb->prefix . 'gym_card_mapping';
        $wpdb->delete($mapping_table, ['id' => intval($_POST['mapping_id'])]);
        set_transient('gym_mapping_deleted_' . get_current_user_id(), 'Мапването е изтрито успешно!', 300);
        wp_safe_redirect(admin_url('admin.php?page=gym-access&tab=mapping'));
        exit;
    } elseif ($_POST['gym_action'] === 'add_mapping') {
        // Ръчно добавяне/актуализиране на мапване
        $card_number = isset($_POST['manual_card_number']) ? trim(sanitize_text_field($_POST['manual_card_number'])) : '';
        $rfid_uid    = isset($_POST['manual_rfid_uid']) ? trim(sanitize_text_field($_POST['manual_rfid_uid'])) : '';

        if ($card_number === '' || $rfid_uid === '') {
            set_transient('gym_manual_mapping_error_' . get_current_user_id(), 'Моля, попълнете и двете полета: номер на карта и Wiegand код.', 300);
            wp_safe_redirect(admin_url('admin.php?page=gym-access&tab=mapping'));
            exit;
        }

        if (!is_numeric($rfid_uid)) {
            set_transient('gym_manual_mapping_error_' . get_current_user_id(), 'Wiegand кодът трябва да бъде число (DEC формат).', 300);
            wp_safe_redirect(admin_url('admin.php?page=gym-access&tab=mapping'));
            exit;
        }

        $mapping_table = $wpdb->prefix . 'gym_card_mapping';

        // Проверяваме дали вече има запис с този номер на карта или Wiegand код
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $mapping_table WHERE card_number=%s OR rfid_uid=%s",
            $card_number,
            $rfid_uid
        ));

        if ($existing) {
            $result = $wpdb->update(
                $mapping_table,
                [
                    'card_number' => $card_number,
                    'rfid_uid'    => $rfid_uid,
                ],
                ['id' => $existing->id]
            );
            $message = 'Мапването е актуализирано успешно.';
        } else {
            $result = $wpdb->insert(
                $mapping_table,
                [
                    'card_number' => $card_number,
                    'rfid_uid'    => $rfid_uid,
                ]
            );
            $message = 'Мапването е добавено успешно.';
        }

        if ($result === false) {
            set_transient(
                'gym_manual_mapping_error_' . get_current_user_id(),
                'Грешка при запис в базата данни: ' . $wpdb->last_error,
                300
            );
        } else {
            set_transient(
                'gym_manual_mapping_success_' . get_current_user_id(),
                $message,
                300
            );
        }

        wp_safe_redirect(admin_url('admin.php?page=gym-access&tab=mapping'));
        exit;
    } elseif ($_POST['gym_action'] === 'save_settings') {
        // Запис на настройки на плъгина + OTA firmware
        $settings = get_option('gym_access_settings', []);
        if (!is_array($settings)) {
            $settings = [];
        }
        
        // Ограничение: едно посещение на ден
        $settings['one_visit_per_day'] = isset($_POST['one_visit_per_day']) ? 1 : 0;
        
        // OTA: версия (по избор) и флаг дали ъпдейтът е активен
        $ota_version = isset($_POST['ota_firmware_version']) ? sanitize_text_field($_POST['ota_firmware_version']) : '';
        if ($ota_version !== '') {
            $settings['ota_firmware_version'] = $ota_version;
        }
        
        $ota_mark_ready = isset($_POST['ota_mark_ready']) && $_POST['ota_mark_ready'] === '1';
        
        // Обработка на качен firmware файл (ако има)
        if (isset($_FILES['ota_firmware_file']) && !empty($_FILES['ota_firmware_file']['name'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            
            $file = $_FILES['ota_firmware_file'];
            
            // Разрешаваме само .bin файлове
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($file_ext !== 'bin') {
                set_transient('gym_settings_saved_' . get_current_user_id(), 'Грешка: позволени са само .bin файлове за firmware.', 300);
                wp_safe_redirect(admin_url('admin.php?page=gym-access&tab=settings'));
                exit;
            }
            
            $uploaded = wp_handle_upload($file, ['test_form' => false]);
            if (isset($uploaded['error'])) {
                set_transient('gym_settings_saved_' . get_current_user_id(), 'Грешка при качване на firmware: ' . $uploaded['error'], 300);
                wp_safe_redirect(admin_url('admin.php?page=gym-access&tab=settings'));
                exit;
            }
            
            // Записваме URL на качения firmware
            $settings['ota_firmware_url'] = isset($uploaded['url']) ? esc_url_raw($uploaded['url']) : '';
            // Ако няма въведена версия, ползваме името на файла
            if (empty($settings['ota_firmware_version'])) {
                $settings['ota_firmware_version'] = basename($file['name']);
            }
            
            // След качване по подразбиране ъпдейтът не е активен, докато не се маркира
            $settings['ota_required'] = 0;
        }
        
        // Ако има отметка "маркирай ъпдейта като готов", включваме флага
        if ($ota_mark_ready && !empty($settings['ota_firmware_url']) && !empty($settings['ota_firmware_version'])) {
            $settings['ota_required'] = 1;
        } elseif (!$ota_mark_ready) {
            // Ако не е маркирано, не променяме съществуващия флаг (освен ако не е нулиран от качване)
            if (!isset($settings['ota_required'])) {
                $settings['ota_required'] = 0;
            }
        }
        
        update_option('gym_access_settings', $settings);
        set_transient('gym_settings_saved_' . get_current_user_id(), 'Настройките са записани успешно!', 300);
        wp_safe_redirect(admin_url('admin.php?page=gym-access&tab=settings'));
        exit;
    } elseif ($_POST['gym_action'] === 'save_email_settings') {
        $settings = get_option('gym_access_settings', []);
        if (!is_array($settings)) {
            $settings = [];
        }

        $defaults = gym_access_email_setting_defaults();

        $settings['email_expiry_enabled'] = isset($_POST['email_expiry_enabled']) ? 1 : 0;
        $settings['email_visits_enabled'] = isset($_POST['email_visits_enabled']) ? 1 : 0;

        $days = isset($_POST['email_days_before_expiry']) ? (int) $_POST['email_days_before_expiry'] : 7;
        $settings['email_days_before_expiry'] = max(1, min(90, $days));

        $settings['email_subject_expiry'] = isset($_POST['email_subject_expiry'])
            ? sanitize_text_field(wp_unslash($_POST['email_subject_expiry']))
            : $defaults['email_subject_expiry'];
        $settings['email_body_expiry'] = isset($_POST['email_body_expiry'])
            ? sanitize_textarea_field(wp_unslash($_POST['email_body_expiry']))
            : $defaults['email_body_expiry'];

        $settings['email_subject_visits'] = isset($_POST['email_subject_visits'])
            ? sanitize_text_field(wp_unslash($_POST['email_subject_visits']))
            : $defaults['email_subject_visits'];
        $settings['email_body_visits'] = isset($_POST['email_body_visits'])
            ? sanitize_textarea_field(wp_unslash($_POST['email_body_visits']))
            : $defaults['email_body_visits'];

        update_option('gym_access_settings', $settings);
        set_transient('gym_email_settings_saved_' . get_current_user_id(), 'Имейл настройките са записани успешно!', 300);
        $email_return = isset($_POST['email_log_date_return']) ? sanitize_text_field(wp_unslash($_POST['email_log_date_return'])) : '';
        $redirect_email = admin_url('admin.php?page=gym-access&tab=email');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $email_return)) {
            $redirect_email = add_query_arg('email_log_date', $email_return, $redirect_email);
        }
        wp_safe_redirect($redirect_email);
        exit;
    }
});

function gym_access_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'gym_cards';
    
    // Настройки на плъгина
    $gym_settings = get_option('gym_access_settings', []);
    $gym_one_visit_per_day = !empty($gym_settings['one_visit_per_day']);
    $gym_ota_version = isset($gym_settings['ota_firmware_version']) ? $gym_settings['ota_firmware_version'] : '';
    $gym_ota_url     = isset($gym_settings['ota_firmware_url']) ? $gym_settings['ota_firmware_url'] : '';
    $gym_ota_required = !empty($gym_settings['ota_required']);

    $gym_email_defaults = gym_access_email_setting_defaults();
    $gym_email_expiry_enabled = !empty($gym_settings['email_expiry_enabled']);
    $gym_email_visits_enabled = !empty($gym_settings['email_visits_enabled']);
    $gym_email_days_before = isset($gym_settings['email_days_before_expiry']) ? (int) $gym_settings['email_days_before_expiry'] : (int) $gym_email_defaults['email_days_before_expiry'];
    $gym_email_days_before = max(1, min(90, $gym_email_days_before));
    $gym_email_subject_expiry = isset($gym_settings['email_subject_expiry']) ? $gym_settings['email_subject_expiry'] : $gym_email_defaults['email_subject_expiry'];
    $gym_email_body_expiry    = isset($gym_settings['email_body_expiry']) ? $gym_settings['email_body_expiry'] : $gym_email_defaults['email_body_expiry'];
    $gym_email_subject_visits = isset($gym_settings['email_subject_visits']) ? $gym_settings['email_subject_visits'] : $gym_email_defaults['email_subject_visits'];
    $gym_email_body_visits    = isset($gym_settings['email_body_visits']) ? $gym_settings['email_body_visits'] : $gym_email_defaults['email_body_visits'];
    
    // Определяме активния таб
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'list';
    if (!in_array($active_tab, ['list', 'add', 'test', 'mapping', 'settings', 'log', 'email'], true)) {
        $active_tab = 'list';
    }
    
    $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
    $edit_card = null;
    if ($edit_id > 0) {
        $edit_card = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $edit_id));
        // Ако има редактиране, показваме таба за добавяне
        if ($edit_card) {
            $active_tab = 'add';
        }
    }

    // Търсене по номер на карта или име на притежателя
    $search_card = isset($_GET['search_card']) ? sanitize_text_field($_GET['search_card']) : '';

    // Пагинация: показваме картите на страници по 80 броя
    $per_page = 80;
    $current_page = isset($_GET['card_page']) ? max(1, intval($_GET['card_page'])) : 1;
    $offset = ($current_page - 1) * $per_page;

    // Зареждаме всички карти (без филтър по дата), по избор филтрирани по търсене
    if ($search_card) {
        $like = '%' . $wpdb->esc_like($search_card) . '%';

        // Общо брой карти за тази заявка (за пагинацията)
        $total_cards = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table 
                 WHERE card_number LIKE %s 
                    OR holder_name LIKE %s",
                $like,
                $like
            )
        );

        // Конкретната страница от резултатите
        $cards = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table 
                 WHERE card_number LIKE %s 
                    OR holder_name LIKE %s
                 ORDER BY id DESC
                 LIMIT %d OFFSET %d",
                $like,
                $like,
                $per_page,
                $offset
            )
        );
    } else {
        // Общо брой карти (за всички страници)
        $total_cards = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");

        // Конкретната страница
        $cards = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table 
                 ORDER BY id DESC
                 LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        );
    }

    $total_pages = max(1, (int) ceil($total_cards / $per_page));
    
    // Проверяваме за тестови резултати
    $test_result = get_transient('gym_test_result_' . get_current_user_id());
    $test_saved = get_transient('gym_test_saved_' . get_current_user_id());
    $test_error = get_transient('gym_test_error_' . get_current_user_id());
    
    // Получаваме критерия за търсене по име
    $search_client_name = isset($_GET['search_client_name']) ? sanitize_text_field($_GET['search_client_name']) : '';
    
    // Взимаме всички поръчки за търсене (увеличаваме лимита за по-добро търсене)
    $order_args = [
        'limit' => 500,
        'orderby' => 'date',
        'order' => 'DESC',
        'return' => 'ids'
    ];
    
    $all_orders = wc_get_orders($order_args);
    
    // Филтриране по име на клиент
    $filtered_orders = [];
    if ($search_client_name) {
        $search_name = strtolower($search_client_name);
        foreach ($all_orders as $order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                $first_name = strtolower($order->get_billing_first_name());
                $last_name = strtolower($order->get_billing_last_name());
                $full_name = trim($first_name . ' ' . $last_name);
                
                if (stripos($full_name, $search_name) !== false || 
                    stripos($first_name, $search_name) !== false || 
                    stripos($last_name, $search_name) !== false) {
                    $filtered_orders[] = $order_id;
                }
            }
        }
    }

    ?>
    <div class="wrap gym-access-container">
        <h1>Фитнес карти</h1>
        
        <!-- Навигация с табове -->
        <h2 class="nav-tab-wrapper">
            <a href="?page=gym-access&tab=list" class="nav-tab <?php echo $active_tab === 'list' ? 'nav-tab-active' : ''; ?>">
                Списък с карти
            </a>
            <a href="?page=gym-access&tab=add<?php echo $edit_id > 0 ? '&edit=' . $edit_id : ''; ?>" class="nav-tab <?php echo $active_tab === 'add' ? 'nav-tab-active' : ''; ?>">
                <?php echo $edit_id > 0 ? 'Редактиране на карта' : 'Добавяне на нова карта'; ?>
            </a>
            <a href="?page=gym-access&tab=mapping" class="nav-tab <?php echo $active_tab === 'mapping' ? 'nav-tab-active' : ''; ?>">
                Мапване на карти (Wiegand код)
            </a>
            <a href="?page=gym-access&tab=test" class="nav-tab <?php echo $active_tab === 'test' ? 'nav-tab-active' : ''; ?>">
                Извличане на данни от поръчка
            </a>
            <a href="?page=gym-access&tab=log" class="nav-tab <?php echo $active_tab === 'log' ? 'nav-tab-active' : ''; ?>">
                Хронология
            </a>
            <a href="?page=gym-access&tab=settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                Настройки
            </a>
            <a href="?page=gym-access&tab=email" class="nav-tab <?php echo $active_tab === 'email' ? 'nav-tab-active' : ''; ?>">
                Email настройки
            </a>
        </h2>
        
        <?php if ($test_error): ?>
            <div class="notice notice-error"><p><?php echo esc_html($test_error); ?></p></div>
            <?php delete_transient('gym_test_error_' . get_current_user_id()); ?>
        <?php endif; ?>
        
        <?php if ($test_saved): ?>
            <div class="notice notice-success"><p>Данните са записани успешно в базата данни!</p></div>
            <?php delete_transient('gym_test_saved_' . get_current_user_id()); ?>
        <?php endif; ?>
        
        <?php if ($test_result): ?>
            <div class="notice notice-info" style="padding: 15px; margin: 20px 0;">
                <h3>Резултати от тестване на поръчка #<?php echo $test_result['order_id']; ?></h3>
                <table class="form-table" style="background: white; padding: 10px;">
                    <tr>
                        <th style="width: 200px;">Номер на карта:</th>
                        <td><strong><?php echo $test_result['data']['card_number'] ? esc_html($test_result['data']['card_number']) : '<span style="color:red;">Не е намерен</span>'; ?></strong></td>
                    </tr>
                    <tr>
                        <th>Име:</th>
                        <td><?php echo $test_result['data']['holder_name'] ? esc_html($test_result['data']['holder_name']) : '<span style="color:#999;">-</span>'; ?></td>
                    </tr>
                    <tr>
                        <th>Имейл:</th>
                        <td><?php echo $test_result['data']['email'] ? esc_html($test_result['data']['email']) : '<span style="color:#999;">-</span>'; ?></td>
                    </tr>
                    <tr>
                        <th>Телефон:</th>
                        <td><?php echo $test_result['data']['phone'] ? esc_html($test_result['data']['phone']) : '<span style="color:#999;">-</span>'; ?></td>
                    </tr>
                    <tr>
                        <th>Валидна от:</th>
                        <td><?php echo $test_result['data']['valid_from'] ? esc_html($test_result['data']['valid_from']) : '<span style="color:red;">Не е намерена</span>'; ?></td>
                    </tr>
                    <tr>
                        <th>Валидна до:</th>
                        <td><?php echo $test_result['data']['valid_until'] ? esc_html($test_result['data']['valid_until']) : '<span style="color:red;">Не е намерена</span>'; ?></td>
                    </tr>
                    <tr>
                        <th>Максимален брой посещения:</th>
                        <td>
                            <?php 
                            $max_visits = isset($test_result['data']['max_visits']) ? (int)$test_result['data']['max_visits'] : 0;
                            if ($max_visits > 0) {
                                echo '<strong>' . esc_html($max_visits) . '</strong>';
                            } else {
                                echo '<span style="color:#999;">Неограничен</span>';
                            }
                            ?>
                        </td>
                    </tr>
                </table>
                <p>
                    <a href="?page=gym-access&tab=test" class="button">Затвори</a>
                </p>
            </div>
            <?php delete_transient('gym_test_result_' . get_current_user_id()); ?>
        <?php endif; ?>
        
        <?php if ($active_tab === 'list'): ?>
            <!-- Таб: Списък с карти -->
            <div class="tab-content">
                <?php 
                $card_saved = get_transient('gym_card_saved_' . get_current_user_id());
                $card_deleted = get_transient('gym_card_deleted_' . get_current_user_id());
                if ($card_saved):
                ?>
                    <div class="notice notice-success is-dismissible"><p><?php echo esc_html($card_saved); ?></p></div>
                    <?php delete_transient('gym_card_saved_' . get_current_user_id()); ?>
                <?php endif; ?>
                <?php if ($card_deleted): ?>
                    <div class="notice notice-success is-dismissible"><p><?php echo esc_html($card_deleted); ?></p></div>
                    <?php delete_transient('gym_card_deleted_' . get_current_user_id()); ?>
                <?php endif; ?>
                <h2>Списък с карти</h2>
                
                <!-- Търсачка по номер на карта или име -->
                <div class="card" style="max-width: 600px; margin-bottom: 20px;">
                    <form method="get" action="" style="margin: 0; padding: 10px 0;">
                        <input type="hidden" name="page" value="gym-access">
                        <input type="hidden" name="tab" value="list">
                        <table class="form-table" style="margin: 0;">
                            <tr>
                                <th style="width: 160px;">
                                    <label for="search_card">Търсене на карта</label>
                                </th>
                                <td>
                                    <input
                                        type="text"
                                        id="search_card"
                                        name="search_card"
                                        value="<?php echo esc_attr($search_card); ?>"
                                        class="regular-text"
                                        placeholder="Номер на карта или име на притежател">
                                    <p class="description" style="margin-top: 4px;">
                                        Въведете част от номера на картата или името на притежателя.
                                    </p>
                                </td>
                            </tr>
                        </table>
                        <p style="margin-top: 8px;">
                            <input type="submit" class="button button-primary" value="Търси">
                            <?php if (!empty($search_card)): ?>
                                <a href="?page=gym-access&tab=list" class="button">Изчисти</a>
                            <?php endif; ?>
                        </p>
                    </form>
                </div>
                
                <?php if (isset($total_pages) && $total_pages > 1): ?>
                    <?php
                    // Базов URL за страниците (запазваме търсенето, но сменяме card_page)
                    $base_url = remove_query_arg('card_page');
                    if (!empty($search_card)) {
                        $base_url = add_query_arg('search_card', urlencode($search_card), $base_url);
                    }
                    $prev_page = max(1, $current_page - 1);
                    $next_page = min($total_pages, $current_page + 1);
                    ?>
                    <div class="tablenav-pages" style="margin-bottom: 10px; display:flex; justify-content:space-between; align-items:center;">
                        <span class="displaying-num">
                            <?php echo intval($total_cards); ?> карти, страница <?php echo intval($current_page); ?> от <?php echo intval($total_pages); ?>
                        </span>
                        <span class="pagination-links">
                            <?php if ($current_page > 1): ?>
                                <a class="button" href="<?php echo esc_url(add_query_arg('card_page', 1, $base_url)); ?>">&laquo; Първа</a>
                                <a class="button" href="<?php echo esc_url(add_query_arg('card_page', $prev_page, $base_url)); ?>">&lsaquo; Предишна</a>
                            <?php endif; ?>
                            <?php if ($current_page < $total_pages): ?>
                                <a class="button" href="<?php echo esc_url(add_query_arg('card_page', $next_page, $base_url)); ?>">Следваща &rsaquo;</a>
                                <a class="button" href="<?php echo esc_url(add_query_arg('card_page', $total_pages, $base_url)); ?>">Последна &raquo;</a>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php
                $gym_list_return_args = ['page' => 'gym-access', 'tab' => 'list'];
                if (!empty($search_card)) {
                    $gym_list_return_args['search_card'] = $search_card;
                }
                if (isset($current_page) && $current_page > 1) {
                    $gym_list_return_args['card_page'] = $current_page;
                }
                $gym_list_return_url = add_query_arg($gym_list_return_args, admin_url('admin.php'));
                ?>
                
                <div class="gym-cards-table-wrapper">
                    <table class="widefat fixed striped gym-cards-table">
                        <thead>
                            <tr>
                                <th>Карта</th>
                                <th>Wiegand код (DEC)</th>
                                <th>Име</th>
                                <th>Email</th>
                                <th>Телефон</th>
                                <th>Валидна от</th>
                                <th>Валидна до</th>
                                <th>Макс. посещения</th>
                                <th>Посещения</th>
                                <th>Имейл известия</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($cards)): ?>
                                <tr><td colspan="11">Няма налични карти.</td></tr>
                            <?php else: ?>
                                <?php foreach ($cards as $c): ?>
                                    <?php
                                    $recv_emails = !isset($c->receive_emails) || (int) $c->receive_emails === 1;
                                    ?>
                                    <tr>
                                        <td data-label="Карта"><?php echo esc_html($c->card_number); ?></td>
                                        <td data-label="Wiegand код (DEC)">
                                            <?php 
                                            $rfid_uid = isset($c->rfid_uid) ? $c->rfid_uid : '';
                                            if ($rfid_uid) {
                                                echo '<code style="background:#e8f5e9;padding:2px 6px;border-radius:3px;">' . esc_html($rfid_uid) . '</code>';
                                            } else {
                                                echo '<span style="color:#999;">—</span>';
                                            }
                                            ?>
                                        </td>
                                        <td data-label="Име"><?php echo esc_html($c->holder_name); ?></td>
                                        <td data-label="Email"><?php echo $c->email ? esc_html($c->email) : '<span style="color:#999;">—</span>'; ?></td>
                                        <td data-label="Телефон"><?php echo esc_html($c->phone); ?></td>
                                        <td data-label="Валидна от"><?php echo $c->valid_from ? esc_html(date('d.m.Y H:i', strtotime($c->valid_from))) : '-'; ?></td>
                                        <td data-label="Валидна до"><?php echo $c->valid_until ? esc_html(date('d.m.Y H:i', strtotime($c->valid_until))) : '-'; ?></td>
                                        <td data-label="Макс. посещения">
                                            <?php 
                                            $max_visits = isset($c->max_visits) ? (int)$c->max_visits : 0;
                                            if ($max_visits > 0) {
                                                echo esc_html($max_visits);
                                            } else {
                                                echo '<span style="color:#999;">Неограничен</span>';
                                            }
                                            ?>
                                        </td>
                                        <td data-label="Посещения">
                                            <strong><?php echo isset($c->visits_count) ? (int)$c->visits_count : 0; ?></strong>
                                            <?php 
                                            if ($max_visits > 0) {
                                                $remaining = $max_visits - (isset($c->visits_count) ? (int)$c->visits_count : 0);
                                                if ($remaining <= 0) {
                                                    echo ' <span style="color:#a00;">(Лимит достигнат)</span>';
                                                } else {
                                                    echo ' <span style="color:#46b450;">(' . $remaining . ' остават)</span>';
                                                }
                                            }
                                            ?>
                                        </td>
                                        <td data-label="Имейл известия" style="text-align:center;">
                                            <input type="hidden" form="gym-card-email-prefs" name="card_email_ids[]" value="<?php echo (int) $c->id; ?>">
                                            <label class="screen-reader-text" for="receive_email_<?php echo (int) $c->id; ?>">Имейл известия за карта <?php echo esc_attr($c->card_number); ?></label>
                                            <input type="checkbox" form="gym-card-email-prefs" id="receive_email_<?php echo (int) $c->id; ?>" name="receive_email[<?php echo (int) $c->id; ?>]" value="1" <?php checked($recv_emails); ?> title="Получава напомняния и известия за посещения">
                                        </td>
                                        <td data-label="Действия" class="gym-actions">
                                            <a href="?page=gym-access&tab=add&edit=<?php echo $c->id; ?>" class="button button-small">Редактирай</a>
                                            <form method="post" style="display:inline;" onsubmit="return confirm('Сигурни ли сте, че искате да изтриете тази карта?');">
                                                <?php wp_nonce_field('gym_access_action'); ?>
                                                <input type="hidden" name="gym_action" value="delete">
                                                <input type="hidden" name="card_id" value="<?php echo $c->id; ?>">
                                                <input type="submit" class="button button-small" value="Изтрий" style="color:#a00;">
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!empty($cards)): ?>
                    <form id="gym-card-email-prefs" method="post" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="gym-card-email-prefs-form" style="margin-top: 12px;">
                        <?php wp_nonce_field('gym_access_action'); ?>
                        <input type="hidden" name="gym_action" value="save_card_email_prefs">
                        <input type="hidden" name="gym_list_return_url" value="<?php echo esc_url($gym_list_return_url); ?>">
                        <p>
                            <button type="submit" class="button button-primary">Запази маркировките за имейли</button>
                        </p>
                        <p class="description" style="max-width: 720px;">
                            Отметката определя дали този абонат получава автоматични имейли (напомняне преди изтичане и известие за оставащи посещения), когато тези функции са включени в таба „Email настройки“. Няма имейл адрес – няма изпращане.
                        </p>
                    </form>
                <?php endif; ?>
            </div>
        <?php elseif ($active_tab === 'add'): ?>
            <!-- Таб: Добавяне/Редактиране на карта -->
            <div class="tab-content">
                <h2><?php echo $edit_id > 0 ? 'Редактиране на карта' : 'Добавяне на нова карта'; ?></h2>
                <form method="post" action="">
                    <?php wp_nonce_field('gym_access_action'); ?>
                    <input type="hidden" name="gym_action" value="save">
                    <input type="hidden" name="card_id" value="<?php echo $edit_id; ?>">
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="card_number">Номер на карта *</label></th>
                            <td>
                                <input type="number" id="card_number" name="card_number" value="<?php echo esc_attr($edit_card->card_number ?? ''); ?>" required class="regular-text">
                                <p class="description">Въведете номера на картата (число)</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="rfid_uid">Wiegand код (DEC)</label></th>
                            <td>
                                <input type="text" id="rfid_uid" name="rfid_uid" value="<?php echo esc_attr($edit_card->rfid_uid ?? ''); ?>" class="regular-text" placeholder="379456839" pattern="[0-9]+" maxlength="20">
                                <p class="description">Въведете Wiegand код (DEC формат) от ESP32, напр. 379456839. Оставете празно ако няма RFID карта.</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="holder_name">Име</label></th>
                            <td><input type="text" id="holder_name" name="holder_name" value="<?php echo esc_attr($edit_card->holder_name ?? ''); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="email">Имейл</label></th>
                            <td><input type="email" id="email" name="email" value="<?php echo esc_attr($edit_card->email ?? ''); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="phone">Телефон</label></th>
                            <td><input type="text" id="phone" name="phone" value="<?php echo esc_attr($edit_card->phone ?? ''); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="valid_from">Валидна от</label></th>
                            <td><input type="datetime-local" id="valid_from" name="valid_from" value="<?php echo $edit_card ? esc_attr(str_replace(' ', 'T', substr($edit_card->valid_from, 0, 16))) : ''; ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="valid_until">Валидна до</label></th>
                            <td><input type="datetime-local" id="valid_until" name="valid_until" value="<?php echo $edit_card ? esc_attr(str_replace(' ', 'T', substr($edit_card->valid_until, 0, 16))) : ''; ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="max_visits">Максимален брой посещения</label></th>
                            <td>
                                <input type="number" id="max_visits" name="max_visits" value="<?php echo isset($edit_card->max_visits) ? (int)$edit_card->max_visits : 0; ?>" min="0" class="regular-text">
                                <p class="description">0 = неограничен брой посещения</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="status">Статус</label></th>
                            <td>
                                <select id="status" name="status">
                                    <option value="active" <?php selected($edit_card->status ?? '', 'active'); ?>>Активна</option>
                                    <option value="inactive" <?php selected($edit_card->status ?? '', 'inactive'); ?>>Неактивна</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" class="button button-primary" value="<?php echo $edit_id > 0 ? 'Актуализирай' : 'Добави'; ?>">
                        <?php if ($edit_id > 0): ?>
                            <a href="?page=gym-access&tab=add" class="button">Отказ</a>
                        <?php endif; ?>
                    </p>
                </form>
            </div>
        <?php elseif ($active_tab === 'mapping'): ?>
            <!-- Таб: Мапване на карти (RFID UID) -->
            <div class="tab-content">
                <?php 
                $import_success = get_transient('gym_import_success_' . get_current_user_id());
                $import_error = get_transient('gym_import_error_' . get_current_user_id());
                $manual_success = get_transient('gym_manual_mapping_success_' . get_current_user_id());
                $manual_error = get_transient('gym_manual_mapping_error_' . get_current_user_id());
                if ($import_success):
                ?>
                    <div class="notice notice-success is-dismissible"><p><?php echo esc_html($import_success); ?></p></div>
                    <?php delete_transient('gym_import_success_' . get_current_user_id()); ?>
                <?php endif; ?>
                <?php if ($import_error): ?>
                    <div class="notice notice-error is-dismissible"><p><?php echo esc_html($import_error); ?></p></div>
                    <?php delete_transient('gym_import_error_' . get_current_user_id()); ?>
                <?php endif; ?>
                <?php 
                $mapping_deleted = get_transient('gym_mapping_deleted_' . get_current_user_id());
                if ($mapping_deleted):
                ?>
                    <div class="notice notice-success is-dismissible"><p><?php echo esc_html($mapping_deleted); ?></p></div>
                    <?php delete_transient('gym_mapping_deleted_' . get_current_user_id()); ?>
                <?php endif; ?>
                <?php if ($manual_success): ?>
                    <div class="notice notice-success is-dismissible"><p><?php echo esc_html($manual_success); ?></p></div>
                    <?php delete_transient('gym_manual_mapping_success_' . get_current_user_id()); ?>
                <?php endif; ?>
                <?php if ($manual_error): ?>
                    <div class="notice notice-error is-dismissible"><p><?php echo esc_html($manual_error); ?></p></div>
                    <?php delete_transient('gym_manual_mapping_error_' . get_current_user_id()); ?>
                <?php endif; ?>
                
                <h2>Мапване на карти (Wiegand код)</h2>
                
                <!-- Форма за ръчно добавяне на мапване -->
                <div class="card" style="max-width: 800px; margin-bottom: 30px;">
                    <h3>Ръчно добавяне на мапване</h3>
                    <form method="post" action="">
                        <?php wp_nonce_field('gym_access_action'); ?>
                        <input type="hidden" name="gym_action" value="add_mapping">
                        <table class="form-table">
                            <tr>
                                <th><label for="manual_card_number">Номер на карта</label></th>
                                <td>
                                    <input type="number" id="manual_card_number" name="manual_card_number" class="regular-text" required>
                                    <p class="description">Въведете номера на картата (число), както се използва в системата/поръчките.</p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="manual_rfid_uid">Wiegand код (DEC)</label></th>
                                <td>
                                    <input type="text" id="manual_rfid_uid" name="manual_rfid_uid" class="regular-text" required pattern="[0-9]+" maxlength="20">
                                    <p class="description">Въведете Wiegand код в десетичен (DEC) формат, напр. 379456839.</p>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <input type="submit" class="button button-primary" value="Запази мапването">
                        </p>
                    </form>
                </div>
                
                <!-- Форма за импортиране -->
                <div class="card" style="max-width: 800px; margin-bottom: 30px;">
                    <h3>Импортиране на мапване от XLS/CSV файл</h3>
                    <form method="post" action="" enctype="multipart/form-data">
                        <?php wp_nonce_field('gym_access_action'); ?>
                        <input type="hidden" name="gym_action" value="import_mapping">
                        <table class="form-table">
                            <tr>
                                <th><label for="mapping_file">Изберете файл:</label></th>
                                <td>
                                    <input type="file" id="mapping_file" name="mapping_file" accept=".csv,.xls,.xlsx,.txt" required>
                                    <p class="description">
                                        Файлът трябва да съдържа две колони:<br>
                                        <strong>Колона 1:</strong> Номер на карта (число)<br>
                                        <strong>Колона 2:</strong> Wiegand код (DEC формат, десетично число, напр. 379456839)<br><br>
                                        Формат: CSV или XLS/XLSX. Първият ред може да е header (card_number, wiegand_code).
                                    </p>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <input type="submit" class="button button-primary" value="Импортирай мапване">
                        </p>
                    </form>
                </div>
                
                <!-- Списък с мапвания -->
                <?php
                $mapping_table = $wpdb->prefix . 'gym_card_mapping';
                $search_mapping = isset($_GET['search_mapping']) ? sanitize_text_field($_GET['search_mapping']) : '';
                
                if ($search_mapping) {
                    $like_mapping = '%' . $wpdb->esc_like($search_mapping) . '%';
                    $mappings = $wpdb->get_results($wpdb->prepare(
                        "SELECT * FROM $mapping_table WHERE card_number LIKE %s ORDER BY card_number ASC LIMIT 500",
                        $like_mapping
                    ));
                } else {
                    $mappings = $wpdb->get_results("SELECT * FROM $mapping_table ORDER BY card_number ASC LIMIT 500");
                }
                ?>
                
                <!-- Търсачка по номер на карта -->
                <div class="card" style="max-width: 600px; margin-bottom: 20px;">
                    <form method="get" action="" style="margin: 0; padding: 10px 0;">
                        <input type="hidden" name="page" value="gym-access">
                        <input type="hidden" name="tab" value="mapping">
                        <table class="form-table" style="margin: 0;">
                            <tr>
                                <th style="width: 160px;">
                                    <label for="search_mapping">Търсене по номер на карта</label>
                                </th>
                                <td>
                                    <input type="text" id="search_mapping" name="search_mapping" value="<?php echo esc_attr($search_mapping); ?>" class="regular-text" placeholder="Номер на карта">
                                    <p class="description" style="margin-top: 4px;">Въведете част от номера на картата.</p>
                                </td>
                            </tr>
                        </table>
                        <p style="margin-top: 8px;">
                            <input type="submit" class="button button-primary" value="Търси">
                            <?php if (!empty($search_mapping)): ?>
                                <a href="?page=gym-access&tab=mapping" class="button">Изчисти</a>
                            <?php endif; ?>
                        </p>
                    </form>
                </div>
                
                <h3>Списък с мапвания (<?php echo count($mappings); ?>)</h3>
                <div class="gym-cards-table-wrapper">
                    <table class="widefat fixed striped gym-cards-table">
                        <thead>
                            <tr>
                                <th>Номер на карта</th>
                                <th>Wiegand код (DEC)</th>
                                <th>Създадено</th>
                                <th>Актуализирано</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mappings)): ?>
                                <tr><td colspan="5">Няма импортирани мапвания. Използвайте формата по-горе за импортиране.</td></tr>
                            <?php else: ?>
                                <?php foreach ($mappings as $m): ?>
                                    <tr>
                                        <td data-label="Номер на карта"><strong><?php echo esc_html($m->card_number); ?></strong></td>
                                        <td data-label="Wiegand код (DEC)"><code style="background:#e8f5e9;padding:2px 6px;border-radius:3px;"><?php echo esc_html($m->rfid_uid); ?></code></td>
                                        <td data-label="Създадено"><?php echo $m->created_at ? esc_html(date('d.m.Y H:i', strtotime($m->created_at))) : '-'; ?></td>
                                        <td data-label="Актуализирано"><?php echo $m->updated_at ? esc_html(date('d.m.Y H:i', strtotime($m->updated_at))) : '-'; ?></td>
                                        <td data-label="Действия">
                                            <form method="post" style="display:inline;" onsubmit="return confirm('Сигурни ли сте, че искате да изтриете това мапване?');">
                                                <?php wp_nonce_field('gym_access_action'); ?>
                                                <input type="hidden" name="gym_action" value="delete_mapping">
                                                <input type="hidden" name="mapping_id" value="<?php echo $m->id; ?>">
                                                <input type="submit" class="button button-small" value="Изтрий" style="color:#a00;">
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php elseif ($active_tab === 'log'): ?>
            <!-- Таб: Хронология -->
            <div class="tab-content">
                <?php
                $log_table = $wpdb->prefix . 'gym_access_log';
                
                // Подраздел: дневен, период (от–до) или месечен
                $log_view = isset($_GET['log_view']) ? sanitize_text_field($_GET['log_view']) : 'daily';
                if (!in_array($log_view, ['daily', 'monthly', 'range'], true)) {
                    $log_view = 'daily';
                }
                
                // Дата за филтриране (по подразбиране днес)
                $log_date = isset($_GET['log_date']) ? sanitize_text_field($_GET['log_date']) : current_time('Y-m-d');
                $log_month = isset($_GET['log_month']) ? sanitize_text_field($_GET['log_month']) : current_time('Y-m');
                $search_log = isset($_GET['search_log']) ? sanitize_text_field($_GET['search_log']) : '';

                $log_date_from_raw = isset($_GET['log_date_from']) ? sanitize_text_field(wp_unslash($_GET['log_date_from'])) : '';
                $log_date_to_raw   = isset($_GET['log_date_to']) ? sanitize_text_field(wp_unslash($_GET['log_date_to'])) : '';

                $gym_log_valid_ymd = static function ($d) {
                    if (!is_string($d) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $d, $m)) {
                        return false;
                    }
                    return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $d : false;
                };

                $today_ymd = current_time('Y-m-d');
                $default_from = date('Y-m-d', strtotime('-7 days', current_time('timestamp')));

                $log_date_from = $gym_log_valid_ymd($log_date_from_raw) ?: $default_from;
                $log_date_to   = $gym_log_valid_ymd($log_date_to_raw) ?: $today_ymd;
                if ($log_date_from > $log_date_to) {
                    $tmp = $log_date_from;
                    $log_date_from = $log_date_to;
                    $log_date_to = $tmp;
                }

                $range_nav_q = '&log_date_from=' . rawurlencode($log_date_from) . '&log_date_to=' . rawurlencode($log_date_to);
                ?>
                
                <h2>Хронология на достъпа</h2>
                
                <!-- Подраздели -->
                <?php $search_q = $search_log ? '&search_log=' . urlencode($search_log) : ''; ?>
                <div style="margin-bottom: 20px;">
                    <a href="?page=gym-access&tab=log&log_view=daily&log_date=<?php echo esc_attr($log_date); ?><?php echo $search_q; ?>" 
                       class="button <?php echo $log_view === 'daily' ? 'button-primary' : ''; ?>">
                        Дневен преглед
                    </a>
                    <a href="?page=gym-access&tab=log&log_view=range<?php echo $range_nav_q . $search_q; ?>" 
                       class="button <?php echo $log_view === 'range' ? 'button-primary' : ''; ?>">
                        Период (от–до)
                    </a>
                    <a href="?page=gym-access&tab=log&log_view=monthly&log_month=<?php echo esc_attr($log_month); ?><?php echo $search_q; ?>" 
                       class="button <?php echo $log_view === 'monthly' ? 'button-primary' : ''; ?>">
                        Месечен преглед
                    </a>
                </div>

                <?php
                $deny_reason_labels = [
                    'card_not_found' => 'Картата не е намерена',
                    'card_inactive' => 'Картата е неактивна',
                    'not_started' => 'Картата още не е валидна',
                    'expired' => 'Картата е изтекла',
                    'daily_limit_reached' => 'Дневен лимит достигнат',
                    'visits_limit_reached' => 'Лимит посещения достигнат',
                ];
                ?>
                
                <?php if ($log_view === 'daily'): ?>
                    <!-- Дневен преглед -->
                    <div class="card" style="max-width: 100%; margin-bottom: 20px; padding: 15px;">
                        <form method="get" action="" style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                            <input type="hidden" name="page" value="gym-access">
                            <input type="hidden" name="tab" value="log">
                            <input type="hidden" name="log_view" value="daily">
                            <label for="log_date"><strong>Избери дата:</strong></label>
                            <input type="date" id="log_date" name="log_date" value="<?php echo esc_attr($log_date); ?>" style="padding: 5px;">
                            <label for="search_log"><strong>Търсене:</strong></label>
                            <input type="text" id="search_log" name="search_log" value="<?php echo esc_attr($search_log); ?>" placeholder="Име или номер на карта" style="padding: 5px; min-width: 180px;">
                            <input type="submit" class="button button-primary" value="Покажи">
                            <a href="?page=gym-access&tab=log&log_view=daily&log_date=<?php echo esc_attr($log_date); ?>" class="button">Изчисти търсенето</a>
                            <a href="?page=gym-access&tab=log&log_view=daily&log_date=<?php echo current_time('Y-m-d'); ?>" class="button">Днес</a>
                        </form>
                    </div>
                    
                    <?php
                    // Извличаме записите за избраната дата
                    $date_start = $log_date . ' 00:00:00';
                    $date_end = $log_date . ' 23:59:59';
                    
                    if ($search_log) {
                        $like_log = '%' . $wpdb->esc_like($search_log) . '%';
                        $daily_logs = $wpdb->get_results($wpdb->prepare(
                            "SELECT * FROM $log_table 
                             WHERE checked_at BETWEEN %s AND %s 
                             AND (card_number LIKE %s OR holder_name LIKE %s) 
                             ORDER BY checked_at DESC",
                            $date_start,
                            $date_end,
                            $like_log,
                            $like_log
                        ));
                    } else {
                        $daily_logs = $wpdb->get_results($wpdb->prepare(
                            "SELECT * FROM $log_table WHERE checked_at BETWEEN %s AND %s ORDER BY checked_at DESC",
                            $date_start,
                            $date_end
                        ));
                    }
                    ?>
                    
                    <h3>Записи за <?php echo esc_html(date('d.m.Y', strtotime($log_date))); ?> (<?php echo count($daily_logs); ?> записа)</h3>
                    
                    <div class="gym-cards-table-wrapper">
                        <table class="widefat fixed striped gym-cards-table">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">Час</th>
                                    <th style="width: 100px;">Карта №</th>
                                    <th style="width: 150px;">Wiegand код</th>
                                    <th>Име</th>
                                    <th style="width: 80px;">Статус</th>
                                    <th style="width: 80px;">Чекиране</th>
                                    <th>Причина за отказ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($daily_logs)): ?>
                                    <tr><td colspan="7">Няма записи за тази дата.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($daily_logs as $log): 
                                        // Определяме цвета на реда
                                        if (!$log->access_granted) {
                                            $row_color = '#dc3232'; // Червено за отказ
                                            $row_bg = '#ffeaea';
                                        } elseif ($log->check_count_today == 1) {
                                            $row_color = '#46b450'; // Зелено за първо чекиране
                                            $row_bg = '#eaffea';
                                        } else {
                                            $row_color = '#ffb900'; // Жълто за повторно чекиране
                                            $row_bg = '#fff8e5';
                                        }
                                    ?>
                                        <tr style="background-color: <?php echo $row_bg; ?>;">
                                            <td data-label="Час" style="color: <?php echo $row_color; ?>; font-weight: bold;">
                                                <?php echo esc_html(date('H:i:s', strtotime($log->checked_at))); ?>
                                            </td>
                                            <td data-label="Карта №" style="color: <?php echo $row_color; ?>;">
                                                <strong><?php echo esc_html($log->card_number); ?></strong>
                                            </td>
                                            <td data-label="Wiegand код" style="color: <?php echo $row_color; ?>;">
                                                <?php if ($log->rfid_uid): ?>
                                                    <code style="background: rgba(0,0,0,0.05); padding: 2px 6px; border-radius: 3px;">
                                                        <?php echo esc_html($log->rfid_uid); ?>
                                                    </code>
                                                <?php else: ?>
                                                    <span style="color: #999;">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Име" style="color: <?php echo $row_color; ?>;">
                                                <?php echo $log->holder_name ? esc_html($log->holder_name) : '<span style="color:#999;">—</span>'; ?>
                                            </td>
                                            <td data-label="Статус" style="color: <?php echo $row_color; ?>; font-weight: bold;">
                                                <?php echo $log->access_granted ? '✓ Достъп' : '✗ Отказ'; ?>
                                            </td>
                                            <td data-label="Чекиране" style="color: <?php echo $row_color; ?>;">
                                                <?php 
                                                if ($log->access_granted) {
                                                    echo $log->check_count_today == 1 ? '1-во' : $log->check_count_today . '-ро';
                                                } else {
                                                    echo '—';
                                                }
                                                ?>
                                            </td>
                                            <td data-label="Причина" style="color: <?php echo $row_color; ?>;">
                                                <?php 
                                                if ($log->deny_reason) {
                                                    echo isset($deny_reason_labels[$log->deny_reason]) 
                                                        ? esc_html($deny_reason_labels[$log->deny_reason]) 
                                                        : esc_html($log->deny_reason);
                                                } else {
                                                    echo '—';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                <?php elseif ($log_view === 'range'): ?>
                    <!-- Преглед по период (от дата до дата, различни месеци) -->
                    <div class="card" style="max-width: 100%; margin-bottom: 20px; padding: 15px;">
                        <form method="get" action="" style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                            <input type="hidden" name="page" value="gym-access">
                            <input type="hidden" name="tab" value="log">
                            <input type="hidden" name="log_view" value="range">
                            <label for="log_date_from"><strong>От дата:</strong></label>
                            <input type="date" id="log_date_from" name="log_date_from" value="<?php echo esc_attr($log_date_from); ?>" style="padding: 5px;">
                            <label for="log_date_to"><strong>До дата:</strong></label>
                            <input type="date" id="log_date_to" name="log_date_to" value="<?php echo esc_attr($log_date_to); ?>" style="padding: 5px;">
                            <label for="search_log_range"><strong>Търсене:</strong></label>
                            <input type="text" id="search_log_range" name="search_log" value="<?php echo esc_attr($search_log); ?>" placeholder="Име или номер на карта" style="padding: 5px; min-width: 180px;">
                            <input type="submit" class="button button-primary" value="Покажи">
                            <a href="<?php echo esc_url( add_query_arg( [
                                'page' => 'gym-access',
                                'tab' => 'log',
                                'log_view' => 'range',
                                'log_date_from' => $log_date_from,
                                'log_date_to' => $log_date_to,
                            ], admin_url( 'admin.php' ) ) ); ?>" class="button">Изчисти търсенето</a>
                            <a href="<?php echo esc_url( add_query_arg( [
                                'page' => 'gym-access',
                                'tab' => 'log',
                                'log_view' => 'range',
                                'log_date_from' => $default_from,
                                'log_date_to' => $today_ymd,
                            ], admin_url( 'admin.php' ) ) ); ?>" class="button">Последни 7 дни</a>
                        </form>
                    </div>

                    <?php
                    $range_start = $log_date_from . ' 00:00:00';
                    $range_end = $log_date_to . ' 23:59:59';

                    if ($search_log) {
                        $like_log_range = '%' . $wpdb->esc_like($search_log) . '%';
                        $range_logs = $wpdb->get_results($wpdb->prepare(
                            "SELECT * FROM $log_table 
                             WHERE checked_at BETWEEN %s AND %s 
                             AND (card_number LIKE %s OR holder_name LIKE %s) 
                             ORDER BY checked_at DESC",
                            $range_start,
                            $range_end,
                            $like_log_range,
                            $like_log_range
                        ));
                    } else {
                        $range_logs = $wpdb->get_results($wpdb->prepare(
                            "SELECT * FROM $log_table WHERE checked_at BETWEEN %s AND %s ORDER BY checked_at DESC",
                            $range_start,
                            $range_end
                        ));
                    }
                    ?>

                    <h3>Записи от <?php echo esc_html(date('d.m.Y', strtotime($log_date_from))); ?> до <?php echo esc_html(date('d.m.Y', strtotime($log_date_to))); ?> (<?php echo count($range_logs); ?> записа)</h3>

                    <div class="gym-cards-table-wrapper">
                        <table class="widefat fixed striped gym-cards-table">
                            <thead>
                                <tr>
                                    <th style="width: 120px;">Дата и час</th>
                                    <th style="width: 100px;">Карта №</th>
                                    <th style="width: 150px;">Wiegand код</th>
                                    <th>Име</th>
                                    <th style="width: 80px;">Статус</th>
                                    <th style="width: 80px;">Чекиране</th>
                                    <th>Причина за отказ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($range_logs)): ?>
                                    <tr><td colspan="7">Няма записи за избрания период.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($range_logs as $log) :
                                        if (!$log->access_granted) {
                                            $row_color = '#dc3232';
                                            $row_bg = '#ffeaea';
                                        } elseif ($log->check_count_today == 1) {
                                            $row_color = '#46b450';
                                            $row_bg = '#eaffea';
                                        } else {
                                            $row_color = '#ffb900';
                                            $row_bg = '#fff8e5';
                                        }
                                        ?>
                                        <tr style="background-color: <?php echo esc_attr($row_bg); ?>;">
                                            <td data-label="Дата и час" style="color: <?php echo esc_attr($row_color); ?>; font-weight: bold;">
                                                <?php echo esc_html(date('d.m.Y H:i:s', strtotime($log->checked_at))); ?>
                                            </td>
                                            <td data-label="Карта №" style="color: <?php echo esc_attr($row_color); ?>;">
                                                <strong><?php echo esc_html($log->card_number); ?></strong>
                                            </td>
                                            <td data-label="Wiegand код" style="color: <?php echo esc_attr($row_color); ?>;">
                                                <?php if ($log->rfid_uid) : ?>
                                                    <code style="background: rgba(0,0,0,0.05); padding: 2px 6px; border-radius: 3px;">
                                                        <?php echo esc_html($log->rfid_uid); ?>
                                                    </code>
                                                <?php else : ?>
                                                    <span style="color: #999;">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Име" style="color: <?php echo esc_attr($row_color); ?>;">
                                                <?php echo $log->holder_name ? esc_html($log->holder_name) : '<span style="color:#999;">—</span>'; ?>
                                            </td>
                                            <td data-label="Статус" style="color: <?php echo esc_attr($row_color); ?>; font-weight: bold;">
                                                <?php echo $log->access_granted ? '✓ Достъп' : '✗ Отказ'; ?>
                                            </td>
                                            <td data-label="Чекиране" style="color: <?php echo esc_attr($row_color); ?>;">
                                                <?php
                                                if ($log->access_granted) {
                                                    echo $log->check_count_today == 1 ? '1-во' : (int) $log->check_count_today . '-ро';
                                                } else {
                                                    echo '—';
                                                }
                                                ?>
                                            </td>
                                            <td data-label="Причина" style="color: <?php echo esc_attr($row_color); ?>;">
                                                <?php
                                                if ($log->deny_reason) {
                                                    echo isset($deny_reason_labels[$log->deny_reason])
                                                        ? esc_html($deny_reason_labels[$log->deny_reason])
                                                        : esc_html($log->deny_reason);
                                                } else {
                                                    echo '—';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                <?php else: ?>
                    <!-- Месечен преглед -->
                    <div class="card" style="max-width: 100%; margin-bottom: 20px; padding: 15px;">
                        <form method="get" action="" style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                            <input type="hidden" name="page" value="gym-access">
                            <input type="hidden" name="tab" value="log">
                            <input type="hidden" name="log_view" value="monthly">
                            <label for="log_month"><strong>Избери месец:</strong></label>
                            <input type="month" id="log_month" name="log_month" value="<?php echo esc_attr($log_month); ?>" style="padding: 5px;">
                            <label for="search_log_monthly"><strong>Търсене:</strong></label>
                            <input type="text" id="search_log_monthly" name="search_log" value="<?php echo esc_attr($search_log); ?>" placeholder="Име или номер на карта" style="padding: 5px; min-width: 180px;">
                            <input type="submit" class="button button-primary" value="Покажи">
                            <a href="?page=gym-access&tab=log&log_view=monthly&log_month=<?php echo esc_attr($log_month); ?>" class="button">Изчисти търсенето</a>
                            <a href="?page=gym-access&tab=log&log_view=monthly&log_month=<?php echo current_time('Y-m'); ?>" class="button">Текущ месец</a>
                        </form>
                    </div>
                    
                    <?php
                    // Извличаме статистика за месеца - брой чекирания по карта
                    $month_start = $log_month . '-01 00:00:00';
                    $month_end = date('Y-m-t', strtotime($log_month . '-01')) . ' 23:59:59';
                    
                    if ($search_log) {
                        $like_log = '%' . $wpdb->esc_like($search_log) . '%';
                        $monthly_stats = $wpdb->get_results($wpdb->prepare(
                            "SELECT 
                                card_number, 
                                holder_name,
                                COUNT(*) as total_checks,
                                SUM(CASE WHEN access_granted = 1 THEN 1 ELSE 0 END) as successful_checks,
                                SUM(CASE WHEN access_granted = 0 THEN 1 ELSE 0 END) as denied_checks,
                                MAX(checked_at) as last_check
                            FROM $log_table 
                            WHERE checked_at BETWEEN %s AND %s 
                            AND (card_number LIKE %s OR holder_name LIKE %s)
                            GROUP BY card_number, holder_name
                            ORDER BY successful_checks DESC, total_checks DESC",
                            $month_start,
                            $month_end,
                            $like_log,
                            $like_log
                        ));
                        $total_monthly = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM $log_table 
                             WHERE checked_at BETWEEN %s AND %s 
                             AND (card_number LIKE %s OR holder_name LIKE %s)",
                            $month_start,
                            $month_end,
                            $like_log,
                            $like_log
                        ));
                    } else {
                        $monthly_stats = $wpdb->get_results($wpdb->prepare(
                            "SELECT 
                                card_number, 
                                holder_name,
                                COUNT(*) as total_checks,
                                SUM(CASE WHEN access_granted = 1 THEN 1 ELSE 0 END) as successful_checks,
                                SUM(CASE WHEN access_granted = 0 THEN 1 ELSE 0 END) as denied_checks,
                                MAX(checked_at) as last_check
                            FROM $log_table 
                            WHERE checked_at BETWEEN %s AND %s 
                            GROUP BY card_number, holder_name
                            ORDER BY successful_checks DESC, total_checks DESC",
                            $month_start,
                            $month_end
                        ));
                        $total_monthly = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM $log_table WHERE checked_at BETWEEN %s AND %s",
                            $month_start,
                            $month_end
                        ));
                    }
                    
                    $month_name = date_i18n('F Y', strtotime($log_month . '-01'));
                    ?>
                    
                    <h3>Статистика за <?php echo esc_html($month_name); ?></h3>
                    <p>Общо записи: <strong><?php echo (int)$total_monthly; ?></strong> | Уникални карти: <strong><?php echo count($monthly_stats); ?></strong></p>
                    
                    <div class="gym-cards-table-wrapper">
                        <table class="widefat fixed striped gym-cards-table">
                            <thead>
                                <tr>
                                    <th style="width: 100px;">Карта №</th>
                                    <th>Име</th>
                                    <th style="width: 120px;">Успешни входове</th>
                                    <th style="width: 100px;">Откази</th>
                                    <th style="width: 100px;">Общо</th>
                                    <th style="width: 150px;">Последно чекиране</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($monthly_stats)): ?>
                                    <tr><td colspan="6">Няма записи за този месец.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($monthly_stats as $stat): ?>
                                        <tr>
                                            <td data-label="Карта №"><strong><?php echo esc_html($stat->card_number); ?></strong></td>
                                            <td data-label="Име"><?php echo $stat->holder_name ? esc_html($stat->holder_name) : '<span style="color:#999;">—</span>'; ?></td>
                                            <td data-label="Успешни входове" style="color: #46b450; font-weight: bold;">
                                                <?php echo (int)$stat->successful_checks; ?>
                                            </td>
                                            <td data-label="Откази" style="color: <?php echo $stat->denied_checks > 0 ? '#dc3232' : '#999'; ?>;">
                                                <?php echo (int)$stat->denied_checks; ?>
                                            </td>
                                            <td data-label="Общо">
                                                <?php echo (int)$stat->total_checks; ?>
                                            </td>
                                            <td data-label="Последно чекиране">
                                                <?php echo esc_html(date('d.m.Y H:i', strtotime($stat->last_check))); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif ($active_tab === 'settings'): ?>
            <!-- Таб: Настройки -->
            <div class="tab-content">
                <?php 
                $settings_saved = get_transient('gym_settings_saved_' . get_current_user_id());
                if ($settings_saved):
                ?>
                    <div class="notice notice-success is-dismissible"><p><?php echo esc_html($settings_saved); ?></p></div>
                    <?php delete_transient('gym_settings_saved_' . get_current_user_id()); ?>
                <?php endif; ?>
                
                <h2>Настройки</h2>
                <div class="card" style="max-width: 800px;">
                    <form method="post" action="" enctype="multipart/form-data">
                        <?php wp_nonce_field('gym_access_action'); ?>
                        <input type="hidden" name="gym_action" value="save_settings">
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="one_visit_per_day">Ограничение: едно посещение на ден</label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" id="one_visit_per_day" name="one_visit_per_day" value="1" <?php checked($gym_one_visit_per_day); ?>>
                                        Разрешава само едно успешно влизане с картата в рамките на един календарен ден.
                                    </label>
                                    <p class="description">
                                        Ако е включено, при втори успешен опит за достъп в същия ден картата ще бъде отказана.
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="ota_firmware_file">OTA firmware (.bin) за ESP32</label>
                                </th>
                                <td>
                                    <input type="file" id="ota_firmware_file" name="ota_firmware_file" accept=".bin">
                                    <p class="description">
                                        Качете компилиран .bin файл за ESP32. Той няма да се инсталира автоматично, докато не маркирате ъпдейта като готов.
                                    </p>
                                    <?php if (!empty($gym_ota_url)): ?>
                                        <p style="margin-top: 8px;">
                                            <strong>Текущ качен firmware:</strong><br>
                                            Версия: <code><?php echo esc_html($gym_ota_version); ?></code><br>
                                            URL: <code><?php echo esc_html($gym_ota_url); ?></code><br>
                                            Статус: 
                                            <?php if ($gym_ota_required): ?>
                                                <span style="color:#46b450;font-weight:bold;">Маркиран за инсталация</span>
                                            <?php else: ?>
                                                <span style="color:#999;">Не е маркиран за инсталация</span>
                                            <?php endif; ?>
                                        </p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="ota_firmware_version">Версия на firmware-a</label>
                                </th>
                                <td>
                                    <input type="text" id="ota_firmware_version" name="ota_firmware_version" value="<?php echo esc_attr($gym_ota_version); ?>" class="regular-text" placeholder="напр. 2025.03.01-01">
                                    <p class="description">
                                        Използва се за сравнение от ESP32. Ако оставите празно, ще се използва името на файла при качване.
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">Пускане на ъпдейта</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="ota_mark_ready" value="1" <?php checked($gym_ota_required); ?>>
                                        Маркирай последния качен firmware като готов за инсталация (OTA).
                                    </label>
                                    <p class="description">
                                        Когато е отметнато, ESP32 ще види, че има нов firmware и при следващата проверка ще го изтегли и инсталира.
                                    </p>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <input type="submit" class="button button-primary" value="Запази настройките">
                        </p>
                    </form>
                </div>
            </div>
        <?php elseif ($active_tab === 'email'): ?>
            <div class="tab-content">
                <?php
                $email_saved = get_transient('gym_email_settings_saved_' . get_current_user_id());
                if ($email_saved) :
                    ?>
                    <div class="notice notice-success is-dismissible"><p><?php echo esc_html($email_saved); ?></p></div>
                    <?php
                    delete_transient('gym_email_settings_saved_' . get_current_user_id());
                endif;

                $email_log_date = isset($_GET['email_log_date']) ? sanitize_text_field(wp_unslash($_GET['email_log_date'])) : current_time('Y-m-d');
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $email_log_date)) {
                    $email_log_date = current_time('Y-m-d');
                }

                $email_log_rows    = [];
                $email_log_table   = $wpdb->prefix . 'gym_access_email_log';
                $email_log_table_ok = (bool) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $email_log_table));
                if ($email_log_table_ok) {
                    $d0 = $email_log_date . ' 00:00:00';
                    $d1 = $email_log_date . ' 23:59:59';
                    $email_log_rows = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT * FROM $email_log_table WHERE sent_at >= %s AND sent_at <= %s ORDER BY sent_at DESC LIMIT 500",
                            $d0,
                            $d1
                        )
                    );
                }

                $email_type_labels = [
                    'expiry_reminder'  => 'Напомняне за изтичане',
                    'visits_remaining' => 'Оставащи посещения',
                    'unknown'          => '—',
                ];
                ?>

                <h2>Email настройки</h2>
                <p class="description" style="max-width: 900px;">
                    Напомнянията за изтичане се изпращат автоматично веднъж на ден от WordPress (cron), когато до края на абонамента остават точно избраният брой календарни дни.
                    Имейлът за оставащи посещения се изпраща след всяко успешно чекиране, ако абонаментът има лимит на посещения и в картата е въведен валиден имейл.
                </p>

                <div class="card" style="max-width: 960px; margin-top: 15px;">
                    <form method="post" action="">
                        <?php wp_nonce_field('gym_access_action'); ?>
                        <input type="hidden" name="gym_action" value="save_email_settings">
                        <input type="hidden" name="email_log_date_return" value="<?php echo esc_attr($email_log_date); ?>">

                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row">Активиране</th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" name="email_expiry_enabled" value="1" <?php checked($gym_email_expiry_enabled); ?>>
                                            Изпращай напомнящи имейли преди изтичане на абонамента
                                        </label>
                                        <br><br>
                                        <label>
                                            <input type="checkbox" name="email_visits_enabled" value="1" <?php checked($gym_email_visits_enabled); ?>>
                                            Изпращай имейл с оставащи посещения след успешно чекиране (само при лимит на посещения)
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="email_days_before_expiry">Дни преди изтичане</label>
                                </th>
                                <td>
                                    <input type="number" id="email_days_before_expiry" name="email_days_before_expiry" value="<?php echo esc_attr((string) $gym_email_days_before); ?>" min="1" max="90" class="small-text">
                                    <p class="description">Имейлът се изпраща веднъж, когато до датата на изтичане остават точно толкова календарни дни (1–90).</p>
                                </td>
                            </tr>
                        </table>

                        <h3 style="margin-top: 24px;">Шаблон: напомняне за изтичане</h3>
                        <p class="description">Плейсхолдери: <code>{holder_name}</code>, <code>{card_number}</code>, <code>{valid_until}</code>, <code>{days_remaining}</code>, <code>{site_name}</code>, <code>{site_url}</code><br><em>Бележка: <code>{valid_until}</code> показва само дата на изтичане (без час).</em></p>
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><label for="email_subject_expiry">Тема</label></th>
                                <td>
                                    <input type="text" class="large-text" id="email_subject_expiry" name="email_subject_expiry" value="<?php echo esc_attr($gym_email_subject_expiry); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="email_body_expiry">Текст на имейла</label></th>
                                <td>
                                    <textarea class="large-text" rows="8" id="email_body_expiry" name="email_body_expiry"><?php echo esc_textarea($gym_email_body_expiry); ?></textarea>
                                    <p class="description">Примерен текст (може да се редактира изцяло):</p>
                                    <pre class="description" style="white-space: pre-wrap; max-width: 100%; background: #f6f7f7; padding: 10px; border: 1px solid #c3c4c7;"><?php echo esc_html($gym_email_defaults['email_body_expiry']); ?></pre>
                                </td>
                            </tr>
                        </table>

                        <h3 style="margin-top: 24px;">Шаблон: оставащи посещения след чекиране</h3>
                        <p class="description">Плейсхолдери: <code>{holder_name}</code>, <code>{card_number}</code>, <code>{max_visits}</code>, <code>{visits_used}</code>, <code>{visits_remaining}</code>, <code>{site_name}</code>, <code>{site_url}</code></p>
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><label for="email_subject_visits">Тема</label></th>
                                <td>
                                    <input type="text" class="large-text" id="email_subject_visits" name="email_subject_visits" value="<?php echo esc_attr($gym_email_subject_visits); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="email_body_visits">Текст на имейла</label></th>
                                <td>
                                    <textarea class="large-text" rows="8" id="email_body_visits" name="email_body_visits"><?php echo esc_textarea($gym_email_body_visits); ?></textarea>
                                    <p class="description">Примерен текст (може да се редактира изцяло):</p>
                                    <pre class="description" style="white-space: pre-wrap; max-width: 100%; background: #f6f7f7; padding: 10px; border: 1px solid #c3c4c7;"><?php echo esc_html($gym_email_defaults['email_body_visits']); ?></pre>
                                </td>
                            </tr>
                        </table>

                        <p class="submit">
                            <input type="submit" class="button button-primary" value="Запази имейл настройките">
                        </p>
                    </form>
                </div>

                <div class="card" style="max-width: 960px; margin-top: 24px;">
                    <h3 style="margin-top: 0;">Лог на изпратени имейли</h3>
                    <?php if (! $email_log_table_ok) : ?>
                        <div class="notice notice-warning inline" style="margin: 0 0 12px;">
                            <p>Таблицата за лог на имейли още не е създадена. Деактивирайте и отново активирайте плъгина „Gym Access Manager“, за да се създаде.</p>
                        </div>
                    <?php endif; ?>
                    <p class="description">По подразбиране се показва днешната дата. Изберете друга дата, за да прегледате минали изпращания.</p>
                    <form method="get" action="" style="margin: 12px 0 16px; display: flex; align-items: center; flex-wrap: wrap; gap: 10px;">
                        <input type="hidden" name="page" value="gym-access">
                        <input type="hidden" name="tab" value="email">
                        <label for="email_log_date"><strong>Дата:</strong></label>
                        <input type="date" id="email_log_date" name="email_log_date" value="<?php echo esc_attr($email_log_date); ?>">
                        <input type="submit" class="button button-primary" value="Покажи">
                        <a href="<?php echo esc_url(add_query_arg(['page' => 'gym-access', 'tab' => 'email', 'email_log_date' => current_time('Y-m-d')], admin_url('admin.php'))); ?>" class="button">Днес</a>
                    </form>

                    <div class="gym-cards-table-wrapper">
                        <table class="widefat striped gym-cards-table">
                            <thead>
                                <tr>
                                    <th style="width: 150px;">Час</th>
                                    <th style="width: 180px;">Тип</th>
                                    <th>Получател</th>
                                    <th>Тема</th>
                                    <th style="width: 110px;">Карта №</th>
                                    <th style="width: 90px;">Статус</th>
                                    <th>Бележка</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (! $email_log_table_ok) : ?>
                                    <tr>
                                        <td colspan="7">Логът не е наличен, докато таблицата в базата не бъде създадена (вижте съобщението по-горе).</td>
                                    </tr>
                                <?php elseif (empty($email_log_rows)) : ?>
                                    <tr>
                                        <td colspan="7">Няма записи за <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($email_log_date . ' 12:00:00'))); ?>.</td>
                                    </tr>
                                <?php else : ?>
                                    <?php foreach ($email_log_rows as $elog) : ?>
                                        <?php
                                        $tlabel = isset($email_type_labels[ $elog->email_type ]) ? $email_type_labels[ $elog->email_type ] : $elog->email_type;
                                        $ok     = ($elog->status === 'sent');
                                        ?>
                                        <tr>
                                            <td data-label="Час"><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($elog->sent_at))); ?></td>
                                            <td data-label="Тип"><?php echo esc_html($tlabel); ?></td>
                                            <td data-label="Получател"><?php echo esc_html($elog->recipient_email); ?></td>
                                            <td data-label="Тема"><?php echo esc_html($elog->subject); ?></td>
                                            <td data-label="Карта №"><?php echo $elog->card_number ? esc_html($elog->card_number) : '<span style="color:#999;">—</span>'; ?></td>
                                            <td data-label="Статус">
                                                <?php if ($ok) : ?>
                                                    <span style="color: #46b450; font-weight: 600;">Успех</span>
                                                <?php else : ?>
                                                    <span style="color: #d63638; font-weight: 600;">Неуспех</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Бележка"><?php echo (! $ok && ! empty($elog->error_message)) ? esc_html($elog->error_message) : '<span style="color:#999;">—</span>'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (count($email_log_rows) >= 500) : ?>
                        <p class="description" style="margin-top: 10px;">Показани са най-много 500 записа за деня.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif ($active_tab === 'test'): ?>
            <!-- Таб: Извличане на данни от поръчка -->
            <div class="tab-content gym-test-tab">
                <div class="card" style="margin-bottom: 30px;">
                    <!-- Форма за търсене -->
                    <h3 style="margin-top: 0;">Търсене на поръчка по име на клиент</h3>
                    <form method="get" action="" class="gym-search-form" style="margin-bottom: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd;">
                        <input type="hidden" name="page" value="gym-access">
                        <input type="hidden" name="tab" value="test">
                        <table class="form-table" style="margin: 0;">
                            <tr>
                                <th style="width: 150px;"><label for="search_client_name">Име на клиент:</label></th>
                                <td>
                                    <input type="text" id="search_client_name" name="search_client_name" value="<?php echo esc_attr($search_client_name); ?>" class="regular-text" placeholder="Въведете име, фамилия или пълно име на клиент">
                                    <p class="description" style="margin: 5px 0 0 0;">Търсене по име, фамилия или пълно име</p>
                                </td>
                            </tr>
                        </table>
                        <p style="margin: 15px 0 0 0;">
                            <input type="submit" class="button button-primary" value="Търси">
                            <?php if ($search_client_name): ?>
                                <a href="?page=gym-access&tab=test" class="button">Изчисти търсене</a>
                            <?php endif; ?>
                        </p>
                    </form>
                    
                    <!-- Списък с намерени поръчки -->
                    <?php if ($search_client_name): ?>
                        <?php if (!empty($filtered_orders)): ?>
                            <h3>Намерени поръчки (<?php echo count($filtered_orders); ?>):</h3>
                            <div class="gym-orders-table-wrapper">
                                <table class="widefat fixed striped gym-orders-table" style="margin-top: 10px;">
                                    <thead>
                                        <tr>
                                            <th style="width: 100px;">Поръчка #</th>
                                            <th style="width: 150px;">Дата</th>
                                            <th style="width: 200px;">Клиент</th>
                                            <th style="width: 120px;">Сума</th>
                                            <th style="width: 120px;">Статус</th>
                                            <th style="width: 200px;">Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($filtered_orders as $order_id): 
                                            $order = wc_get_order($order_id);
                                            if ($order):
                                                $order_date = $order->get_date_created()->date_i18n('d.m.Y H:i');
                                                $order_total = $order->get_total();
                                                $order_status = wc_get_order_status_name($order->get_status());
                                                $customer_name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
                                                $customer_email = $order->get_billing_email();
                                        ?>
                                            <tr>
                                                <td data-label="Поръчка #"><strong>#<?php echo $order_id; ?></strong></td>
                                                <td data-label="Дата"><?php echo esc_html($order_date); ?></td>
                                                <td data-label="Клиент">
                                                    <?php echo esc_html($customer_name); ?>
                                                    <?php if ($customer_email): ?>
                                                        <br><small style="color: #666;"><?php echo esc_html($customer_email); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td data-label="Сума"><?php echo wc_price($order_total); ?></td>
                                                <td data-label="Статус"><?php echo esc_html($order_status); ?></td>
                                                <td data-label="Действия" class="gym-actions">
                                                    <form method="post" action="" class="gym-test-form">
                                                        <?php wp_nonce_field('gym_access_action'); ?>
                                                        <input type="hidden" name="gym_action" value="test_order">
                                                        <input type="hidden" name="test_order_id" value="<?php echo $order_id; ?>">
                                                        <input type="hidden" name="search_client_name" value="<?php echo esc_attr($search_client_name); ?>">
                                                        <div style="margin-bottom: 5px;">
                                                            <label style="display: block; margin-bottom: 5px;">
                                                                <input type="checkbox" name="save_to_db" value="1" style="margin-right: 5px;">
                                                                <small>Запиши в БД</small>
                                                            </label>
                                                        </div>
                                                        <input type="submit" class="button button-small" value="Тествай">
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endif; endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="notice notice-info" style="margin-top: 20px;">
                                <p>Не са намерени поръчки за клиент с име "<?php echo esc_html($search_client_name); ?>"</p>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="notice notice-info" style="margin-top: 20px;">
                            <p>Въведете име на клиент в полето за търсене, за да видите всички поръчки за този клиент.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
