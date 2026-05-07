<?php
/**
 * API endpoint for generating tests via ProxyAPI
 */

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не поддерживается']);
    exit;
}

// Validate required fields
$topic = trim($_POST['topic'] ?? '');
$age = intval($_POST['age'] ?? 0);
$subject = trim($_POST['subject'] ?? '');
$wishes = trim($_POST['wishes'] ?? '');

if (empty($topic)) {
    http_response_code(400);
    echo json_encode(['error' => 'Укажите тему теста']);
    exit;
}
if (empty($subject)) {
    http_response_code(400);
    echo json_encode(['error' => 'Выберите предмет']);
    exit;
}
if ($age < 3 || $age > 25) {
    http_response_code(400);
    echo json_encode(['error' => 'Возраст должен быть от 3 до 25 лет']);
    exit;
}

// Build the prompt based on age
$prompt = buildPrompt($topic, $age, $subject, $wishes);

// Call ProxyAPI
$result = callProxyAPI($prompt);

if (isset($result['error'])) {
    http_response_code(500);
    echo json_encode(['error' => $result['error']]);
    exit;
}

// Parse and validate the response
$testData = parseResponse($result['content']);

if (isset($testData['error'])) {
    http_response_code(500);
    echo json_encode(['error' => $testData['error']]);
    exit;
}

// Store in session for print page
$_SESSION['test_data'] = $testData;
$_SESSION['test_params'] = [
    'topic' => $topic,
    'age' => $age,
    'subject' => $subject,
    'wishes' => $wishes
];

echo json_encode(['success' => true, 'test' => $testData]);

// ============================================================
// FUNCTIONS
// ============================================================

function buildPrompt(string $topic, int $age, string $subject, string $wishes): array
{
    // Age-specific settings
    if ($age <= 7) {
        $ageGroup = 'дошкольники';
        $langStyle = 'Используй простой, игровой язык. Короткие предложения, яркие образы. 
                      Вопросы должны быть понятны ребёнку 5-7 лет. Добавляй эмодзи и восклицания.';
        $questionTypes = 'Вопросы на соответствие картинок, простой выбор из 2-3 вариантов, 
                         вопросы "да/нет", вопросы с пропущенным словом (вставь слово).';
        $difficulty = 'Очень лёгкий. Все вопросы должны быть интуитивно понятны.';
        $pointRange = 'Каждый вопрос по 1 баллу.';
        $numQuestions = '5-7 вопросов';
    } elseif ($age <= 10) {
        $ageGroup = 'младшие школьники';
        $langStyle = 'Понятный, дружелюбный язык. Умеренно короткие предложения. 
                      Можно использовать небольшие пояснения к вопросам.';
        $questionTypes = 'Множественный выбор (4 варианта), вопросы "верно/неверно", 
                         задания на соответствие, вставка пропущенного слова, короткий ответ.';
        $difficulty = 'Лёгкий. Базовые знания по теме.';
        $pointRange = 'Вопросы по 1-2 балла.';
        $numQuestions = '8-10 вопросов';
    } elseif ($age <= 14) {
        $ageGroup = 'средние классы';
        $langStyle = 'Умеренно академический, но доступный язык. Чёткие формулировки.';
        $questionTypes = 'Множественный выбор (4 варианта), верно/неверно с объяснением, 
                         задания на соответствие, заполнение пропусков, развёрнутый ответ, 
                         задания на установление последовательности.';
        $difficulty = 'Средний. Вопросы на понимание и применение знаний.';
        $pointRange = 'Вопросы по 1-3 балла.';
        $numQuestions = '10-12 вопросов';
    } elseif ($age <= 17) {
        $ageGroup = 'старшие классы';
        $langStyle = 'Академический язык. Чёткие, ёмкие формулировки.';
        $questionTypes = 'Множественный выбор (4-5 вариантов), верно/неверно с аргументацией, 
                         задания на соответствие, заполнение пропусков, развёрнутый ответ, 
                         задания на анализ, творческие задания, эссе.';
        $difficulty = 'Повышенный. Вопросы на анализ, синтез и оценку.';
        $pointRange = 'Вопросы по 2-5 баллов.';
        $numQuestions = '10-15 вопросов';
    } else {
        $ageGroup = 'студенты / взрослые';
        $langStyle = 'Академический или профессиональный язык в зависимости от предмета.';
        $questionTypes = 'Множественный выбор, верно/неверно с обоснованием, задания на 
                         соответствие, анализ ситуаций, развёрнутый ответ, эссе, 
                         практические задания, задания на критическое мышление.';
        $difficulty = 'Высокий. Вопросы на анализ, синтез, оценку и применение.';
        $pointRange = 'Вопросы по 3-10 баллов.';
        $numQuestions = '10-15 вопросов';
    }

    $systemMessage = "Ты — профессиональный педагог и методист по созданию тестов. "
        . "Твоя задача — составить качественный, интересный и возрастосообразный тест.";

    $userMessage = "Составь тест по следующим параметрам:

**Предмет:** {$subject}
**Тема:** {$topic}
**Возрастная группа:** {$ageGroup} ({$age} лет)
**Пожелания:** " . ($wishes ?: 'Нет особых пожеланий') . "

**Требования к стилю:** {$langStyle}
**Типы вопросов:** {$questionTypes}
**Сложность:** {$difficulty}
**Оценка:** {$pointRange}
**Количество вопросов:** {$numQuestions}

ВАЖНО: Верни ТОЛЬКО валидный JSON без каких-либо пояснений до или после. 
Никакой разметки, никаких ```json, никаких комментариев.

Схема JSON:
{
  \"title\": \"Название теста\",
  \"description\": \"Краткое описание теста (1-2 предложения)\",
  \"questions\": [
    {
      \"id\": 1,
      \"type\": \"multiple_choice\" | \"true_false\" | \"fill_blank\" | \"matching\" | \"ordering\" | \"short_answer\" | \"essay\",
      \"question\": \"Текст вопроса (можно использовать HTML: &lt;b&gt;, &lt;i&gt;, &lt;ul&gt;, &lt;li&gt; для форматирования)\",
      \"options\": [\"Вариант А\", \"Вариант Б\", \"Вариант В\", \"Вариант Г\"],
      \"answer\": \"Правильный ответ или пояснение\",
      \"points\": 2
    }
  ],
  \"total_points\": 30
}

Правила для типов вопросов:
- **multiple_choice**: вопрос с вариантами ответов. Поле options обязательно (3-5 вариантов).
- **true_false**: вопрос с ответом да/нет. Поле options обязательно: [\"Верно\", \"Неверно\"]. answer: \"Верно\" или \"Неверно\".
- **fill_blank**: вопрос с пропуском. В тексте вопроса используй _____ для пропуска.
- **matching**: задание на соответствие. В question опиши элементы для сопоставления. Используй &lt;br&gt; для переноса строк.
- **ordering**: задание на установление правильной последовательности. В question перечисли элементы в произвольном порядке. Используй &lt;br&gt; для переноса строк.
- **short_answer**: вопрос с кратким ответом (1-2 предложения).
- **essay**: творческое задание с развёрнутым ответом.

Сделай тест действительно интересным и увлекательным! Добавь вопросы, 
которые заставляют думать, а не просто вспоминать.";

    return [
        ['role' => 'system', 'content' => $systemMessage],
        ['role' => 'user', 'content' => $userMessage]
    ];
}

function callProxyAPI(array $messages): array
{
    $payload = json_encode([
        'model' => MODEL,
        'messages' => $messages,
        'max_completion_tokens' => MAX_TOKENS,
        'temperature' => TEMPERATURE
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => API_URL,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . PROXYAPI_KEY,
            'Accept: application/json'
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['error' => 'Ошибка соединения с API: ' . $error];
    }

    $data = json_decode($response, true);

    if (!$data) {
        return ['error' => 'Не удалось обработать ответ API'];
    }

    if ($httpCode !== 200) {
        $errMsg = $data['error']['message'] ?? 'Неизвестная ошибка API (код: ' . $httpCode . ')';
        if (DEBUG_MODE) {
            $errMsg .= ' | Ответ: ' . substr($response, 0, 500);
        }
        return ['error' => $errMsg];
    }

    $content = $data['choices'][0]['message']['content'] ?? '';
    if (empty($content)) {
        return ['error' => 'API вернул пустой ответ'];
    }

    return ['content' => $content];
}

function parseResponse(string $content): array
{
    // Remove markdown code blocks if present
    $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
    $content = preg_replace('/\s*```$/', '', $content);
    $content = trim($content);

    $data = json_decode($content, true);

    if (!$data) {
        // Try to extract JSON from the response
        preg_match('/\{.*\}/s', $content, $matches);
        if (!empty($matches[0])) {
            $data = json_decode($matches[0], true);
        }
    }

    if (!$data || !isset($data['questions']) || !is_array($data['questions'])) {
        if (DEBUG_MODE) {
            return ['error' => 'Неверный формат ответа от ИИ. Сырой ответ: ' . substr($content, 0, 500)];
        }
        return ['error' => 'Не удалось распарсить тест из ответа ИИ. Попробуйте сгенерировать ещё раз.'];
    }

    // Validate and sanitize questions
    $validTypes = ['multiple_choice', 'true_false', 'fill_blank', 'matching', 'ordering', 'short_answer', 'essay'];
    $validQuestions = [];
    $seenIds = [];

    foreach ($data['questions'] as $q) {
        if (!isset($q['id'])) continue;

        $id = (int)$q['id'];
        if (isset($seenIds[$id])) continue;
        $seenIds[$id] = true;

        $type = in_array($q['type'] ?? '', $validTypes) ? $q['type'] : 'short_answer';

        $validQuestions[] = [
            'id' => $id,
            'type' => $type,
            'question' => $q['question'] ?? '',
            'options' => $q['options'] ?? [],
            'answer' => $q['answer'] ?? '',
            'points' => max(1, (int)($q['points'] ?? 1))
        ];
    }

    return [
        'title' => $data['title'] ?? 'Тест',
        'description' => $data['description'] ?? '',
        'questions' => $validQuestions,
        'total_points' => array_sum(array_column($validQuestions, 'points'))
    ];
}
