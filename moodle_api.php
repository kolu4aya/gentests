<?php
/**
 * Moodle REST API backend — proxy calls to a Moodle instance
 */

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// Polyfill for PHP < 8.0
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return $needle === '' || strpos($haystack, $needle) === 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не поддерживается']);
    exit;
}

$action = $_POST['action'] ?? '';
$moodleUrl = rtrim(trim($_POST['moodle_url'] ?? ''), '/');
$token = trim($_POST['token'] ?? '');

if (empty($moodleUrl) || empty($token)) {
    http_response_code(400);
    echo json_encode(['error' => 'Укажите URL Moodle и токен']);
    exit;
}

// Normalise Moodle URL
if (!str_starts_with($moodleUrl, 'http')) {
    $moodleUrl = 'https://' . $moodleUrl;
}

$restUrl = $moodleUrl . '/webservice/rest/server.php';

try {
    switch ($action) {

        // ---------- Validate token + get user info ----------
        case 'validate':
            $data = moodleCall($restUrl, $token, 'core_webservice_get_site_info', []);
            echo json_encode([
                'success' => true,
                'user' => [
                    'fullname' => $data['fullname'] ?? '',
                    'username' => $data['username'] ?? '',
                    'userid'   => $data['userid'] ?? 0,
                    'siteurl'  => $data['siteurl'] ?? '',
                    'sitename' => $data['sitename'] ?? '',
                ]
            ]);
            break;

        // ---------- List courses ----------
        case 'get_courses':
            // 1. Get current user info
            $siteInfo = moodleCall($restUrl, $token, 'core_webservice_get_site_info', []);
            $userId = (int)($siteInfo['userid'] ?? 0);

            // 2. Get courses the user is enrolled in
            $courses = [];
            try {
                $courses = moodleCall($restUrl, $token, 'core_enrol_get_users_courses', [
                    'userid' => $userId,
                ]);
            } catch (Exception $e) {
                // Fallback: try core_course_get_courses
                $allCourses = moodleCall($restUrl, $token, 'core_course_get_courses', []);
                foreach ($allCourses as $c) {
                    $courses[] = [
                        'id' => (int)$c['id'],
                        'shortname' => $c['shortname'] ?? '',
                        'fullname' => $c['fullname'] ?? '',
                    ];
                }
                echo json_encode(['success' => true, 'courses' => $courses]);
                break;
            }

            $result = [];
            foreach ($courses as $c) {
                $result[] = [
                    'id'        => (int)$c['id'],
                    'shortname' => $c['shortname'] ?? '',
                    'fullname'  => $c['fullname'] ?? '',
                ];
            }

            echo json_encode(['success' => true, 'courses' => $result]);
            break;

        // ---------- Get sections for a course ----------
        case 'get_sections':
            $courseId = (int)($_POST['course_id'] ?? 0);
            if (!$courseId) {
                throw new Exception('Выберите курс');
            }

            $data = moodleCall($restUrl, $token, 'core_course_get_course_sections_by_course', [
                'courseid' => $courseId,
            ]);

            $sections = [];
            foreach ($data as $s) {
                $sections[] = [
                    'id'   => (int)$s['id'],
                    'name' => $s['name'] ?: "Раздел {$s['section']}",
                    'section' => (int)$s['section'],
                ];
            }

            echo json_encode(['success' => true, 'sections' => $sections]);
            break;

        // ---------- Import questions into Moodle ----------
        case 'import_questions':
            $courseId  = (int)($_POST['course_id'] ?? 0);
            $sectionId = (int)($_POST['section_id'] ?? 0);

            if (!$courseId) {
                throw new Exception('Выберите курс');
            }

            $testData = $_SESSION['test_data'] ?? null;
            if (!$testData || empty($testData['questions'])) {
                throw new Exception('Нет данных теста. Сначала сгенерируйте тест.');
            }

            $title = $testData['title'] ?? 'Тест';
            $questions = $testData['questions'];

            // 1. Get existing question categories for this course
            $cats = moodleCall($restUrl, $token, 'core_question_get_categories', [
                'courseid' => $courseId,
            ]);

            // 2. Find or create a category for our test
            $categoryId = null;
            $catName = 'Тест: ' . $title;
            foreach ($cats as $cat) {
                if ($cat['name'] === $catName) {
                    $categoryId = (int)$cat['id'];
                    break;
                }
            }

            if (!$categoryId && !empty($cats)) {
                // Use first existing category as parent
                $parentCat = $cats[0];
                $parentId = (int)$parentCat['id'];
                $parentCtxId = (int)$parentCat['contextid'];

                try {
                    $newCat = moodleCall($restUrl, $token, 'core_question_create_category', [
                        'name' => $catName,
                        'info' => 'Импортировано из Генератора тестов',
                        'parent' => $parentId,
                        'contextid' => $parentCtxId,
                    ]);
                    $categoryId = (int)($newCat['category']['id'] ?? 0);
                } catch (Exception $e) {
                    throw new Exception('Не удалось создать категорию вопросов: ' . $e->getMessage());
                }
            }

            if (!$categoryId) {
                throw new Exception('Не найдена категория вопросов для курса. Убедитесь, что в курсе есть хотя бы одна категория.');
            }

            // 3. Create each question
            $imported = 0;
            $errors = [];
            $typeMap = [
                'multiple_choice' => 'multichoice',
                'true_false'      => 'truefalse',
                'fill_blank'      => 'shortanswer',
                'matching'        => 'matching',
                'ordering'        => 'multichoice',
                'short_answer'    => 'shortanswer',
                'essay'           => 'essay',
            ];

            foreach ($questions as $q) {
                $moodleType = $typeMap[$q['type']] ?? 'shortanswer';
                $questionText = $q['question'] ?? '';
                $answerText   = $q['answer'] ?? '';
                $points       = max(1, (int)($q['points'] ?? 1));
                $options      = $q['options'] ?? [];

                $questionData = buildQuestionData($moodleType, $q, $questionText, $answerText, $points, $options);

                try {
                    moodleCall($restUrl, $token, 'core_question_create_question', [
                        'categoryid' => $categoryId,
                        'type'       => $moodleType,
                        'questiondata' => $questionData,
                    ]);
                    $imported++;
                } catch (Exception $e) {
                    $errors[] = "Вопрос {$q['id']}: " . $e->getMessage();
                }
            }

            $result = [
                'success' => true,
                'imported' => $imported,
                'total'    => count($questions),
                'category_id' => $categoryId,
                'course_id'   => $courseId,
                'errors'   => $errors,
            ];

            // 4. Optionally create a quiz
            if ($sectionId && $imported > 0) {
                try {
                    $quizData = [
                        'coursemodule' => 0,
                        'modulename'   => 'quiz',
                        'section'      => $sectionId,
                        'visible'      => 1,
                        'name'         => $title,
                        'intro'        => $testData['description'] ?? '',
                        'introformat'  => 1,
                        'timeopen'     => 0,
                        'timeclose'    => 0,
                        'grademethod'  => 1,
                        'attempts'     => 0,
                        'sumgrades'    => $testData['total_points'] ?? $imported,
                    ];

                    // Try to create quiz, but don't fail if it doesn't work
                    try {
                        $quizResult = moodleCall($restUrl, $token, 'core_course_create_activities', [
                            'activities' => [$quizData]
                        ]);
                        if (!empty($quizResult)) {
                            $result['quiz_cmid'] = $quizResult[0]['cmid'] ?? null;
                            $result['quiz_url'] = $moodleUrl . '/mod/quiz/view.php?id=' . ($quizResult[0]['cmid'] ?? '');
                        }
                    } catch (Exception $e) {
                        // Quiz creation failed — not critical
                        $result['quiz_error'] = 'Вопросы импортированы, но создать тест не удалось: ' . $e->getMessage();
                    }
                } catch (Exception $e) {
                    $result['quiz_error'] = 'Вопросы импортированы, но создать тест не удалось: ' . $e->getMessage();
                }
            }

            echo json_encode($result);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Неизвестное действие: ' . $action]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

// ============================================================
// FUNCTIONS
// ============================================================

function moodleCall(string $restUrl, string $token, string $function, array $params): array
{
    $params['wstoken']           = $token;
    $params['wsfunction']        = $function;
    $params['moodlewsrestformat'] = 'json';

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $restUrl,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($params, '', '&'),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception('Ошибка подключения к Moodle: ' . $error);
    }

    $data = json_decode($response, true);
    if (!$data) {
        throw new Exception('Неверный ответ от Moodle сервера');
    }

    // Moodle returns error in the response body
    if (isset($data['exception'])) {
        $msg = $data['message'] ?? 'Ошибка Moodle API';
        if (isset($data['errorcode'])) {
            $msg = "[{$data['errorcode']}] $msg";
        }
        throw new Exception($msg);
    }

    return $data;
}

function buildQuestionData(string $moodleType, array $q, string $text, string $answer, int $points, array $options): array
{
    $data = [
        'name' => ['text' => 'Вопрос ' . ($q['id'] ?? 1)],
        'questiontext' => ['text' => $text, 'format' => 1],
        'defaultgrade' => $points,
        'generalfeedback' => ['text' => '', 'format' => 1],
    ];

    switch ($moodleType) {
        case 'multichoice':
            $data['single'] = 1;
            $data['shuffleanswers'] = 1;
            $data['answernumbering'] = 'abc';
            $data['answer'] = [];

            if ($q['type'] === 'ordering') {
                // Ordering as multichoice
                $data['answer'][] = [
                    'text'     => $answer ?: 'Правильный порядок',
                    'format'   => 1,
                    'fraction' => 100,
                ];
                $data['answer'][] = [
                    'text'     => 'Неверный порядок',
                    'format'   => 1,
                    'fraction' => 0,
                ];
            } else {
                $correctLower = mb_strtolower(trim(strip_tags($answer)));
                foreach ($options as $opt) {
                    $optLower = mb_strtolower(trim(strip_tags($opt)));
                    $isCorrect = ($optLower === $correctLower) || ($correctLower !== '' && str_contains($correctLower, $optLower));
                    $data['answer'][] = [
                        'text'     => $opt,
                        'format'   => 1,
                        'fraction' => $isCorrect ? 100 : 0,
                    ];
                }
            }
            break;

        case 'truefalse':
            $isTrue = (stripos($answer, 'верно') !== false || stripos($answer, 'true') !== false);
            $data['answer'] = [
                ['text' => $isTrue ? 'true' : 'false', 'format' => 1, 'fraction' => 100],
                ['text' => $isTrue ? 'false' : 'true', 'format' => 1, 'fraction' => 0],
            ];
            $data['feedbacktrue'] = ['text' => $answer, 'format' => 1];
            $data['feedbackfalse'] = ['text' => $answer, 'format' => 1];
            break;

        case 'shortanswer':
            $data['usecase'] = 0;
            $data['answer'] = [
                ['text' => $answer, 'format' => 1, 'fraction' => 100]
            ];
            break;

        case 'matching':
            $data['shuffleanswers'] = 1;
            $data['subquestions'] = [];
            // Simple matching: create pairs from the question text
            $data['subquestions'][] = [
                'questiontext' => ['text' => 'Элемент', 'format' => 1],
                'answertext'   => $answer,
            ];
            break;

        case 'essay':
            $data['responseformat'] = 'editor';
            $data['responserequired'] = 1;
            $data['responsefieldlines'] = 15;
            $data['attachments'] = 0;
            $data['graderinfo'] = ['text' => $answer, 'format' => 1];
            break;
    }

    return $data;
}
