<?php
/**
 * Configuration file for Test Generator
 * 
 * ProxyAPI — универсальное решение для доступа к API ведущих сервисов ИИ
 * Сайт: https://proxyapi.ru
 * Документация: https://docs.proxyapi.ru
 */

// === ProxyAPI Settings ===
// Получите ключ на https://proxyapi.ru после регистрации
define('PROXYAPI_KEY', 'sk-dBH1U8QHDATFwJJXm1PFeDXDf6009tNL');

// API endpoint для OpenAI-совместимых моделей
define('API_URL', 'https://api.proxyapi.ru/openai/v1/chat/completions');

// Модель для генерации
define('MODEL', 'gpt-5.4-mini');

// Параметры генерации
define('MAX_TOKENS', 4096);
define('TEMPERATURE', 0.8);

// === Application Settings ===
define('APP_NAME', 'Генератор тестов');
define('DEBUG_MODE', false);

// === Session settings ===
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();
