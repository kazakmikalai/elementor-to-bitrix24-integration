<?php
/**
 * Интеграция форм Elementor Pro с Битрикс24 CRM
 * * Данный код перехватывает отправку формы, обрабатывает данные
 * и создает новый Лид в Битрикс24 через REST API.
 */

add_action('elementor_pro/forms/new_record', function($record, $handler) {
    
    // Получаем отформатированные данные полей (по их ID в Elementor)
    $fields = $record->get_formatted_data();
    
    $raw_name = trim($fields['Name'] ?? '');
    $phone    = $fields['Phone'] ?? '';
    
    // Очистка номера телефона: оставляем только цифры и знак плюс
    $clean_phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Логика формирования заголовка лида, если имя не заполнено
    if ( empty($raw_name) ) {
        $display_name = 'Новый клиент';
        $title = 'Заявка: ' . ($clean_phone ? $clean_phone : 'Без номера');
    } else {
        $display_name = $raw_name;
        $title = 'Заявка: ' . $raw_name;
    }

    // ВАЖНО: Замените URL на ваш собственный входящий вебхук из Битрикс24
    $url = 'https://your-domain.bitrix24.ru/rest/ID/TOKEN/crm.lead.add.json';
    
    // Формируем массив данных согласно документации Bitrix24 REST API
    $data = [
        'FIELDS' => [
            'TITLE'     => $title . ' (Сайт)',
            'NAME'      => $display_name,
            'PHONE'     => [
                [
                    'VALUE'      => $clean_phone,
                    'VALUE_TYPE' => 'WORK'
                ]
            ],
            'SOURCE_ID' => 'WEB', // Источник лида
            'COMMENTS'  => 'Автоматическая заявка из формы Elementor.',
        ],
        'params' => [
            'REGISTER_SONET_EVENT' => 'Y' // Отправка уведомления ответственному
        ]
    ];

    // Отправка POST-запроса через внутренние функции WordPress
    wp_remote_post($url, [
        'method'      => 'POST',
        'sslverify'   => false, 
        'timeout'     => 15,
        'blocking'    => false, // Не задерживаем отправку формы для пользователя
        'headers'     => [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ],
        'body'        => http_build_query($data), 
    ]);
    
}, 10, 2);
