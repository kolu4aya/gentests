<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="header">
        <div class="container header__inner">
            <h1 class="header__title"><?= APP_NAME ?></h1>
            <p class="header__subtitle">Создавайте увлекательные тесты с помощью ИИ</p>
        </div>
    </header>

    <main class="main container">
        <div class="layout">
            <!-- Left: Form -->
            <section class="form-section">
                <form id="testForm" class="form">
                    <div class="form__group">
                        <label class="form__label" for="topic">Тема теста</label>
                        <input type="text" id="topic" name="topic" class="form__input" 
                               placeholder="Например: Дроби, Фотосинтез, Великая Отечественная война" required>
                    </div>

                    <div class="form__group">
                        <label class="form__label" for="subject">Предмет</label>
                        <select id="subject" name="subject" class="form__select" required>
                            <option value="">Выберите предмет</option>
                            <option value="Математика">Математика</option>
                            <option value="Русский язык">Русский язык</option>
                            <option value="Литература">Литература</option>
                            <option value="Английский язык">Английский язык</option>
                            <option value="История">История</option>
                            <option value="Обществознание">Обществознание</option>
                            <option value="География">География</option>
                            <option value="Биология">Биология</option>
                            <option value="Физика">Физика</option>
                            <option value="Химия">Химия</option>
                            <option value="Информатика">Информатика</option>
                            <option value="Окружающий мир">Окружающий мир</option>
                            <option value="Другое">Другое</option>
                        </select>
                    </div>

                    <div class="form__group">
                        <label class="form__label" for="age">Возраст детей</label>
                        <input type="number" id="age" name="age" class="form__input" 
                               min="3" max="25" value="10" required>
                        <span class="form__hint">От 3 до 25 лет</span>
                    </div>

                    <div class="form__group">
                        <label class="form__label" for="wishes">Пожелания к тесту</label>
                        <textarea id="wishes" name="wishes" class="form__textarea" 
                                  placeholder="Например: 10 вопросов, включить задания на соответствие, добавить творческое задание, уровень сложности — средний..."
                                  rows="4"></textarea>
                    </div>

                    <button type="submit" class="btn btn--primary" id="generateBtn">
                        <span class="btn__icon">&#9889;</span>
                        <span class="btn__text">Сгенерировать</span>
                    </button>
                </form>

                <div class="form__loading" id="loadingIndicator" style="display:none;">
                    <div class="spinner"></div>
                    <p>Генерируем тест... Это может занять до 30 секунд</p>
                </div>
            </section>

            <!-- Right: Preview -->
            <section class="preview-section">
                <div class="preview" id="preview">
                    <div class="preview__empty" id="emptyState">
                        <div class="preview__empty-icon">&#128221;</div>
                        <h2>Тест ещё не создан</h2>
                        <p>Заполните форму слева и нажмите «Сгенерировать», чтобы получить тест</p>
                    </div>
                    <div class="preview__content" id="previewContent" style="display:none;"></div>
                </div>

                <div class="preview__actions" id="previewActions" style="display:none;">
                    <button class="btn btn--secondary" onclick="printTest()">
                        <span class="btn__icon">&#128424;</span>
                        <span class="btn__text">Печатать</span>
                    </button>
                    <button class="btn btn--outline" onclick="regenerate()">
                        <span class="btn__icon">&#128260;</span>
                        <span class="btn__text">Сгенерировать заново</span>
                    </button>
                </div>
            </section>
        </div>
    </main>

    <div class="toast" id="toast"></div>

    <script src="js/script.js"></script>
</body>
</html>
