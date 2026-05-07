<?php
/**
 * Print-friendly view of the test with PDF generation support
 */

require_once 'config.php';

$testData = $_SESSION['test_data'] ?? null;
$params = $_SESSION['test_params'] ?? null;

if (!$testData) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8"><title>Ошибка</title>';
    echo '<style>body{font-family:sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;background:#f5f5f5;}
          .card{background:#fff;padding:40px;border-radius:12px;box-shadow:0 2px 20px rgba(0,0,0,0.1);text-align:center;}
          h2{color:#e53e3e;margin:0 0 12px;}p{color:#666;margin:0 0 24px;}
          a{display:inline-block;padding:10px 24px;background:#4299e1;color:#fff;text-decoration:none;border-radius:8px;}</style>';
    echo '</head><body><div class="card"><h2>Нет данных для печати</h2>';
    echo '<p>Сначала сгенерируйте тест на главной странице.</p>';
    echo '<a href="index.php">Вернуться к генератору</a></div></body></html>';
    exit;
}

$questions = $testData['questions'];
$totalQuestions = count($questions);
$totalPoints = $testData['total_points'];
$title = htmlspecialchars($testData['title'], ENT_QUOTES, 'UTF-8');
$description = htmlspecialchars($testData['description'], ENT_QUOTES, 'UTF-8');

function declOfNum(int $num, array $titles): string {
    $cases = [2, 0, 1, 1, 1, 2];
    return $titles[($num % 100 > 4 && $num % 100 < 20) ? 2 : $cases[min($num % 10, 5)]];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?> — Тест для печати</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: #fff;
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 40px;
            color: #1a202c;
        }
        .print-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
        }
        .print-header h1 {
            font-size: 24px;
            margin: 0 0 8px;
            color: #2d3748;
        }
        .print-header p {
            margin: 4px 0;
            color: #718096;
            font-size: 14px;
        }
        .print-description {
            text-align: center;
            color: #4a5568;
            margin-bottom: 28px;
            font-style: italic;
        }
        .student-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 24px;
            padding-bottom: 12px;
            border-bottom: 1px dashed #e2e8f0;
        }
        .student-info .field {
            font-size: 14px;
            color: #4a5568;
        }
        .student-info .field span {
            display: inline-block;
            min-width: 180px;
            border-bottom: 1px solid #a0aec0;
            margin-left: 8px;
        }
        .print-question {
            page-break-inside: avoid;
            margin-bottom: 20px;
            padding: 16px;
            background: #f7fafc;
            border-radius: 8px;
            border-left: 4px solid #4299e1;
        }
        .print-question .q-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2d3748;
        }
        .print-question .q-number {
            font-size: 15px;
        }
        .print-question .q-type {
            font-size: 11px;
            background: #ebf8ff;
            color: #2b6cb0;
            padding: 2px 8px;
            border-radius: 4px;
        }
        .print-question .q-text {
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 8px;
        }
        .print-question .q-options {
            list-style: none;
            padding: 0;
            margin: 8px 0;
        }
        .print-question .q-options li {
            padding: 4px 0 4px 24px;
            font-size: 14px;
            position: relative;
        }
        .print-question .q-options li .circle {
            position: absolute;
            left: 0;
            top: 7px;
            width: 14px;
            height: 14px;
            border: 2px solid #718096;
            border-radius: 50%;
            display: inline-block;
        }
        .print-question .q-answer-lines {
            margin-top: 10px;
        }
        .print-question .q-answer-lines .line {
            border-bottom: 1px solid #cbd5e0;
            height: 40px;
            margin-bottom: 4px;
        }
        .print-question .q-answer-lines .line.line--tall {
            height: 60px;
        }
        .print-question .q-points {
            font-size: 12px;
            color: #a0aec0;
            text-align: right;
            margin-top: 6px;
        }
        .print-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
            color: #a0aec0;
            font-size: 12px;
        }
        .answer-key {
            page-break-before: always;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 3px double #4299e1;
        }
        .answer-key h2 {
            text-align: center;
            color: #2d3748;
            margin-bottom: 20px;
        }
        .answer-key .ak-item {
            display: flex;
            gap: 12px;
            padding: 6px 12px;
            font-size: 14px;
            border-bottom: 1px solid #edf2f7;
        }
        .answer-key .ak-item:nth-child(even) {
            background: #f7fafc;
        }
        .answer-key .ak-num {
            font-weight: 600;
            color: #4299e1;
            min-width: 30px;
        }
        .answer-key .ak-answer {
            color: #38a169;
            font-weight: 500;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
            .print-meta { display: none !important; }
            @page { margin: 1.5cm; }
        }
        .no-print {
            text-align: center;
            margin-bottom: 20px;
        }
        .no-print button {
            padding: 12px 32px;
            font-size: 16px;
            background: #4299e1;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        .no-print button:hover { background: #3182ce; }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">&#128424; Печатать / Сохранить PDF</button>
        &nbsp;
        <button onclick="window.close()" style="background:#718096;">&#10005; Закрыть</button>
    </div>

    <div class="print-header print-meta">
        <h1><?= $title ?></h1>
        <?php if ($description): ?>
            <p class="print-description"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>

    <div class="student-info">
        <div class="field">ФИО ученика: <span>&nbsp;</span></div>
        <div class="field">Дата: <span>&nbsp;</span></div>
    </div>

    <?php foreach ($questions as $q):
        $typeLabels = [
            'multiple_choice' => 'Выбор ответа',
            'true_false' => 'Верно / Неверно',
            'fill_blank' => 'Вставка пропуска',
            'matching' => 'Соответствие',
            'ordering' => 'Последовательность',
            'short_answer' => 'Краткий ответ',
            'essay' => 'Развёрнутый ответ'
        ];
        $typeLabel = $typeLabels[$q['type']] ?? $q['type'];

        // Default options for true_false if AI didn't provide them
        if ($q['type'] === 'true_false' && empty($q['options'])) {
            $q['options'] = ['Верно', 'Неверно'];
        }
    ?>
        <div class="print-question">
            <div class="q-header">
                <span class="q-number">Вопрос <?= $q['id'] ?></span>
                <span class="q-type"><?= $typeLabel ?> · <?= $q['points'] ?> <?= declOfNum($q['points'], ['балл', 'балла', 'баллов']) ?></span>
            </div>
            <?php if ($q['type'] === 'multiple_choice'): ?>
                <div class="q-text"><?= $q['question'] ?></div>
                <?php if (!empty($q['options'])): ?>
                <ul class="q-options">
                    <?php foreach ($q['options'] as $opt): ?>
                        <li><span class="circle"></span><?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>

            <?php elseif ($q['type'] === 'true_false'): ?>
                <div class="q-text"><?= $q['question'] ?></div>
                <?php if (!empty($q['options'])): ?>
                <ul class="q-options" style="display:flex;gap:32px;">
                    <?php foreach ($q['options'] as $opt): ?>
                        <li style="padding-left:20px;"><span class="circle"></span><?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>

            <?php elseif ($q['type'] === 'fill_blank'): ?>
                <div class="q-text"><?= $q['question'] ?></div>
                <div style="margin-top:6px;">
                    <span style="border-bottom:2px solid #a0aec0;min-width:200px;display:inline-block;">&nbsp;</span>
                </div>

            <?php elseif ($q['type'] === 'matching'): ?>
                <div style="padding:12px;background:#edf2f7;border-radius:6px;font-size:14px;line-height:1.7;">
                    <?= $q['question'] ?>
                </div>
                <div class="q-answer-lines">
                    <div class="line"></div>
                    <div class="line"></div>
                    <div class="line"></div>
                </div>

            <?php elseif ($q['type'] === 'ordering'): ?>
                <div style="padding:12px;background:#edf2f7;border-radius:6px;font-size:14px;line-height:1.7;">
                    <?= $q['question'] ?>
                </div>
                <div class="q-answer-lines">
                    <div class="line"></div>
                    <div class="line"></div>
                    <div class="line"></div>
                    <div class="line"></div>
                </div>

            <?php elseif ($q['type'] === 'short_answer'): ?>
                <div class="q-text"><?= $q['question'] ?></div>
                <div class="q-answer-lines">
                    <div class="line"></div>
                    <div class="line"></div>
                </div>

            <?php elseif ($q['type'] === 'essay'): ?>
                <div class="q-text"><?= $q['question'] ?></div>
                <div class="q-answer-lines">
                    <div class="line line--tall"></div>
                    <div class="line line--tall"></div>
                    <div class="line line--tall"></div>
                    <div class="line line--tall"></div>
                </div>
            <?php endif; ?>

            <div class="q-points"><?= $q['points'] ?> <?= declOfNum($q['points'], ['балл', 'балла', 'баллов']) ?></div>
        </div>
    <?php endforeach; ?>

    <div class="answer-key">
        <h2>Ключ к тесту</h2>
        <?php foreach ($questions as $q): ?>
            <div class="ak-item">
                <span class="ak-num"><?= $q['id'] ?>.</span>
                <span class="ak-answer"><?= nl2br(htmlspecialchars($q['answer'] ?? '', ENT_QUOTES, 'UTF-8')) ?></span>
                <span style="color:#a0aec0;margin-left:auto;font-size:12px;"><?= $q['points'] ?> <?= declOfNum($q['points'], ['балл', 'балла', 'баллов']) ?></span>
            </div>
        <?php endforeach; ?>
        <div style="text-align:center;margin-top:16px;font-weight:600;color:#2d3748;">
            Итого: <?= $totalPoints ?> <?= declOfNum($totalPoints, ['балл', 'балла', 'баллов']) ?>
        </div>
    </div>

    <div class="print-footer">
        Сгенерировано с помощью <?= APP_NAME ?> · <?= date('d.m.Y') ?>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
        window.onafterprint = function() {
            if (confirm('Закрыть страницу печати?')) {
                window.close();
            }
        };
    </script>
</body>
</html>
