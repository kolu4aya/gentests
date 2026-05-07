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
                    <button class="btn btn--secondary" onclick="downloadMoodleXml()">
                        <span class="btn__icon">&#11015;</span>
                        <span class="btn__text">Moodle XML</span>
                    </button>
                    <button class="btn btn--success" onclick="openMoodleModal()">
                        <span class="btn__icon">&#10133;</span>
                        <span class="btn__text">В Moodle</span>
                    </button>
                    <button class="btn btn--outline" onclick="regenerate()">
                        <span class="btn__icon">&#128260;</span>
                        <span class="btn__text">Заново</span>
                    </button>
                </div>
            </section>
        </div>
    </main>

    <!-- Moodle Modal -->
    <div class="modal-overlay" id="moodleModal" style="display:none;">
        <div class="modal">
            <div class="modal__header">
                <h2>Добавить тест в Moodle</h2>
                <button class="modal__close" onclick="closeMoodleModal()">&times;</button>
            </div>

            <div class="modal__body">
                <!-- Step 1: Connection -->
                <div class="moodle-step" id="moodleStep1">
                    <div class="moodle-step__indicator">Шаг 1 из 3</div>
                    <h3>Подключение к Moodle</h3>
                    <p class="moodle-note">
                        Для работы через API администратор Moodle должен включить 
                        <strong>Веб-службы</strong> и <strong>REST-протокол</strong> 
                        (Администрирование → Дополнительные возможности → Веб-службы).<br>
                        Выпустите токен в своём профиле Moodle или получите у администратора.
                    </p>

                    <div class="form__group">
                        <label class="form__label" for="moodleUrl">URL вашего Moodle</label>
                        <input type="url" id="moodleUrl" class="form__input" 
                               placeholder="https://moodle.example.ru" value="">
                    </div>
                    <div class="form__group">
                        <label class="form__label" for="moodleToken">Токен веб-службы</label>
                        <input type="text" id="moodleToken" class="form__input" 
                               placeholder="вставьте токен">
                    </div>
                    <div id="moodleValidationStatus" class="moodle-status"></div>
                    <button class="btn btn--primary" onclick="validateMoodle()" id="moodleValidateBtn">
                        Подключиться
                    </button>
                </div>

                <!-- Step 2: Course -->
                <div class="moodle-step" id="moodleStep2" style="display:none;">
                    <div class="moodle-step__indicator">Шаг 2 из 3</div>
                    <h3>Выбор курса</h3>
                    <div class="form__group">
                        <label class="form__label" for="moodleCourse">Курс</label>
                        <select id="moodleCourse" class="form__select" onchange="moodleCourseChanged()">
                            <option value="">Загрузка курсов...</option>
                        </select>
                    </div>
                    <div class="form__group">
                        <label class="form__label" for="moodleSection">Раздел</label>
                        <select id="moodleSection" class="form__select">
                            <option value="">Выберите сначала курс</option>
                        </select>
                    </div>
                    <button class="btn btn--primary" onclick="moodleGoToStep3()" id="moodleStep2Btn" disabled>
                        Далее
                    </button>
                </div>

                <!-- Step 3: Confirm -->
                <div class="moodle-step" id="moodleStep3" style="display:none;">
                    <div class="moodle-step__indicator">Шаг 3 из 3</div>
                    <h3>Подтверждение</h3>
                    <div class="moodle-summary" id="moodleSummary">
                        <p><strong>Тест:</strong> <span id="summaryTestName"></span></p>
                        <p><strong>Курс:</strong> <span id="summaryCourse"></span></p>
                        <p><strong>Раздел:</strong> <span id="summarySection"></span></p>
                        <p><strong>Вопросов:</strong> <span id="summaryCount"></span></p>
                    </div>
                    <p class="moodle-note">
                        Вопросы будут созданы в банке вопросов Moodle в категории «Тест: название».
                        После импорта они будут доступны для добавления в любой тест курса.
                        <br><br>
                        <em>Также будет предпринята попытка создать тест-активность в выбранном разделе.</em>
                    </p>
                    <div id="moodleImportStatus" class="moodle-status"></div>
                    <button class="btn btn--success" onclick="importToMoodle()" id="moodleImportBtn">
                        &#10133; Добавить тест в Moodle
                    </button>
                    <span style="margin:0 8px;color:#a0aec0;">или</span>
                    <a href="moodle_export.php" class="btn btn--outline" style="text-decoration:none;display:inline-flex;">
                        &#11015; Скачать XML для ручного импорта
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="toast" id="toast"></div>

    <script src="js/script.js"></script>
</body>
</html>
