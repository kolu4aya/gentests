<?php
/**
 * Moodle XML export — generates a Moodle-compatible XML file for quiz import
 */

require_once 'config.php';

$testData = $_SESSION['test_data'] ?? null;
$params = $_SESSION['test_params'] ?? null;

if (!$testData || !isset($testData['questions'])) {
    http_response_code(404);
    echo 'Нет данных теста. Сначала сгенерируйте тест.';
    exit;
}

$title = htmlspecialchars($testData['title'] ?? 'Тест', ENT_QUOTES, 'UTF-8');
$questions = $testData['questions'];
$categoryName = $title;

header('Content-Type: application/xml; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $categoryName . '.xml"');

// Moodle XML type mapping
$typeMap = [
    'multiple_choice' => 'multichoice',
    'true_false'      => 'truefalse',
    'fill_blank'      => 'shortanswer',
    'matching'        => 'matching',
    'ordering'        => 'multichoice',
    'short_answer'    => 'shortanswer',
    'essay'           => 'essay',
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<quiz>
    <question type="category">
        <category>
            <text>$course$/<?= h($categoryName) ?></text>
        </category>
        <info format="html">
            <text><![CDATA[Тест: <?= h($testData['description'] ?? '') ?>]]></text>
        </info>
    </question>

<?php foreach ($questions as $q):
    $type = $typeMap[$q['type']] ?? 'shortanswer';
    $id = h($q['id']);
    $text = $q['question'];
    $defaultGrade = (int)($q['points'] ?? 1);
    $answerText = h($q['answer'] ?? '');
    $options = $q['options'] ?? [];

    if ($type === 'multichoice' && $q['type'] === 'ordering'):
        // Ordering → multichoice where the correct answer is the sequence
        // We'll create a "select correct order" question
?>
    <question type="multichoice">
        <name><text>Вопрос <?= $id ?></text></name>
        <questiontext format="html">
            <text><![CDATA[<?= $text ?>]]></text>
        </questiontext>
        <defaultgrade><?= $defaultGrade ?></defaultgrade>
        <generalfeedback format="html"><text></text></generalfeedback>
        <single>true</single>
        <shuffleanswers>false</shuffleanswers>
        <answer fraction="100" format="html">
            <text><![CDATA[<?= h($answerText) ?>]]></text>
        </answer>
        <answer fraction="0" format="html">
            <text><![CDATA[Неверный порядок]]></text>
        </answer>
    </question>

<?php elseif ($type === 'multichoice'): ?>
    <question type="multichoice">
        <name><text>Вопрос <?= $id ?></text></name>
        <questiontext format="html">
            <text><![CDATA[<?= $text ?>]]></text>
        </questiontext>
        <defaultgrade><?= $defaultGrade ?></defaultgrade>
        <generalfeedback format="html"><text></text></generalfeedback>
        <single>true</single>
        <shuffleanswers>true</shuffleanswers>
<?php
        $totalOpts = count($options);
        $correctOpt = $answerText;
        foreach ($options as $opt):
            $isCorrect = (stripos($correctOpt, $opt) !== false || stripos($opt, $correctOpt) !== false);
            // If first option, give it 100% if it matches, otherwise 0
            $fraction = ($isCorrect && $options[0] === $opt) ? 100 : 0;
            // If only one option, make it 100
            if ($totalOpts === 1) $fraction = 100;
?>
        <answer fraction="<?= $fraction ?>" format="html">
            <text><![CDATA[<?= h($opt) ?>]]></text>
        </answer>
<?php endforeach; ?>
    </question>

<?php elseif ($type === 'truefalse'): ?>
    <question type="truefalse">
        <name><text>Вопрос <?= $id ?></text></name>
        <questiontext format="html">
            <text><![CDATA[<?= $text ?>]]></text>
        </questiontext>
        <defaultgrade><?= $defaultGrade ?></defaultgrade>
        <generalfeedback format="html"><text></text></generalfeedback>
<?php
        $isTrue = (stripos($answerText, 'верно') !== false || stripos($answerText, 'true') !== false);
?>
        <answer fraction="100" format="html">
            <text><?= $isTrue ? 'true' : 'false' ?></text>
        </answer>
        <answer fraction="0" format="html">
            <text><?= $isTrue ? 'false' : 'true' ?></text>
        </answer>
        <feedback>
            <text><![CDATA[Правильный ответ: <?= h($answerText) ?>]]></text>
        </feedback>
    </question>

<?php elseif ($type === 'shortanswer'): ?>
    <question type="shortanswer">
        <name><text>Вопрос <?= $id ?></text></name>
        <questiontext format="html">
            <text><![CDATA[<?= $text ?>]]></text>
        </questiontext>
        <defaultgrade><?= $defaultGrade ?></defaultgrade>
        <generalfeedback format="html"><text></text></generalfeedback>
        <usecase>0</usecase>
        <answer fraction="100" format="html">
            <text><?= h($answerText) ?></text>
        </answer>
    </question>

<?php elseif ($type === 'matching'): ?>
    <question type="matching">
        <name><text>Вопрос <?= $id ?></text></name>
        <questiontext format="html">
            <text><![CDATA[<?= $text ?>]]></text>
        </questiontext>
        <defaultgrade><?= $defaultGrade ?></defaultgrade>
        <generalfeedback format="html"><text></text></generalfeedback>
        <shuffleanswers>true</shuffleanswers>
        <subquestion format="html">
            <text><![CDATA[Элемент 1]]></text>
            <answertext><![CDATA[<?= h($answerText) ?>]]></answertext>
        </subquestion>
    </question>

<?php elseif ($type === 'essay'): ?>
    <question type="essay">
        <name><text>Вопрос <?= $id ?></text></name>
        <questiontext format="html">
            <text><![CDATA[<?= $text ?>]]></text>
        </questiontext>
        <defaultgrade><?= $defaultGrade ?></defaultgrade>
        <generalfeedback format="html"><text></text></generalfeedback>
        <responseformat>editor</responseformat>
        <responserequired>1</responserequired>
        <responsefieldlines>15</responsefieldlines>
        <attachments>0</attachments>
        <graderinfo format="html">
            <text><![CDATA[<?= h($answerText) ?>]]></text>
        </graderinfo>
    </question>

<?php endif; ?>
<?php endforeach; ?>

</quiz>
<?php

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
