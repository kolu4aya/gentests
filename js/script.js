/**
 * Test Generator — Frontend Scripts
 */

(function () {
    'use strict';

    const form = document.getElementById('testForm');
    const generateBtn = document.getElementById('generateBtn');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const previewContent = document.getElementById('previewContent');
    const emptyState = document.getElementById('emptyState');
    const previewActions = document.getElementById('previewActions');
    const toast = document.getElementById('toast');

    let currentTestData = null;
    let toastTimer = null;

    // Moodle state
    let moodleUrl = '';
    let moodleToken = '';

    // ---------- Form Submit ----------
    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const topic = document.getElementById('topic').value.trim();
        const subject = document.getElementById('subject').value;
        const age = document.getElementById('age').value;
        const wishes = document.getElementById('wishes').value.trim();

        if (!topic || topic.length < 2) {
            showToast('Пожалуйста, введите тему теста (минимум 2 символа)');
            return;
        }
        if (!subject) {
            showToast('Пожалуйста, выберите предмет');
            return;
        }
        if (!age || age < 3 || age > 25) {
            showToast('Возраст должен быть от 3 до 25 лет');
            return;
        }

        setLoading(true);
        hideError();

        const formData = new FormData();
        formData.append('topic', topic);
        formData.append('subject', subject);
        formData.append('age', age);
        formData.append('wishes', wishes);

        try {
            const response = await fetch('generate_test.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.error || 'Ошибка сервера');
            }

            if (!result.success || !result.test) {
                throw new Error(result.error || 'Не удалось сгенерировать тест');
            }

            currentTestData = result.test;
            renderTest(result.test);

        } catch (err) {
            showToast(err.message || 'Произошла ошибка при генерации теста');
            console.error('Generation error:', err);
        } finally {
            setLoading(false);
        }
    });

    // ---------- Set Loading State ----------
    function setLoading(loading) {
        if (loading) {
            generateBtn.disabled = true;
            generateBtn.querySelector('.btn__text').textContent = 'Генерация...';
            loadingIndicator.style.display = 'block';
        } else {
            generateBtn.disabled = false;
            generateBtn.querySelector('.btn__text').textContent = 'Сгенерировать';
            loadingIndicator.style.display = 'none';
        }
    }

    // ---------- Render Test ----------
    function renderTest(test) {
        if (!test || !test.questions || test.questions.length === 0) {
            showToast('Тест не содержит вопросов. Попробуйте сгенерировать ещё раз.');
            return;
        }

        const typeLabels = {
            multiple_choice: 'Выбор ответа',
            true_false: 'Верно / Неверно',
            fill_blank: 'Вставка пропуска',
            matching: 'Соответствие',
            ordering: 'Последовательность',
            short_answer: 'Краткий ответ',
            essay: 'Развёрнутый ответ'
        };

        const typeIcons = {
            multiple_choice: 'A)',
            true_false: '✓/✗',
            fill_blank: '___',
            matching: '⟷',
            ordering: '①②③',
            short_answer: '✎',
            essay: '📝'
        };

        let html = `
            <h2>${escapeHtml(test.title || 'Тест')}</h2>
            <div class="test-meta">
                <span>${test.questions.length} вопросов</span>
                <span>${test.total_points} баллов</span>
            </div>
        `;

        if (test.description) {
            html += `<p class="test-description">${escapeHtml(test.description)}</p>`;
        }

        test.questions.forEach(function (q, index) {
            const typeLabel = typeLabels[q.type] || q.type;
            const typeIcon = typeIcons[q.type] || '?';

            if (q.type === 'true_false' && (!q.options || q.options.length === 0)) {
                q.options = ['Верно', 'Неверно'];
            }

            html += `<div class="question-card">`;
            html += `<div class="q-header">`;
            html += `<span class="q-number">${index + 1}. ${typeIcon} ${typeLabel}</span>`;
            html += `<span class="q-badge">${pluralize(q.points, 'балл', 'балла', 'баллов')}</span>`;
            html += `</div>`;

            if (q.type === 'multiple_choice') {
                html += `<div class="q-text">${q.question.replace(/\n/g, '<br>')}</div>`;
                if (q.options && q.options.length > 0) {
                    html += `<ul class="q-options">`;
                    q.options.forEach(function (opt) {
                        html += `<li>${escapeHtml(opt)}</li>`;
                    });
                    html += `</ul>`;
                }
            } else if (q.type === 'true_false') {
                html += `<div class="q-text">${q.question.replace(/\n/g, '<br>')}</div>`;
                if (q.options && q.options.length > 0) {
                    html += `<ul class="q-options" style="display:flex;gap:24px;">`;
                    q.options.forEach(function (opt) {
                        html += `<li style="padding-left:0;">${escapeHtml(opt)}</li>`;
                    });
                    html += `</ul>`;
                }
            } else if (q.type === 'fill_blank') {
                html += `<div class="q-text">${q.question.replace(/\n/g, '<br>')}</div>`;
            } else if (q.type === 'matching') {
                html += `<div style="padding:12px;background:#edf2f7;border-radius:6px;font-size:14px;line-height:1.7;">`;
                html += `${q.question.replace(/\n/g, '<br>')}`;
                html += `</div>`;
                html += `<div style="margin-top:8px;border-bottom:1px solid #e2e8f0;height:40px;"></div>`;
                html += `<div style="border-bottom:1px solid #e2e8f0;height:40px;"></div>`;
                html += `<div style="border-bottom:1px solid #e2e8f0;height:40px;"></div>`;
            } else if (q.type === 'ordering') {
                html += `<div style="padding:12px;background:#edf2f7;border-radius:6px;font-size:14px;line-height:1.7;">`;
                html += `${q.question.replace(/\n/g, '<br>')}`;
                html += `</div>`;
                html += `<div style="margin-top:8px;border-bottom:1px solid #e2e8f0;height:40px;"></div>`;
                html += `<div style="border-bottom:1px solid #e2e8f0;height:40px;"></div>`;
                html += `<div style="border-bottom:1px solid #e2e8f0;height:40px;"></div>`;
                html += `<div style="border-bottom:1px solid #e2e8f0;height:40px;"></div>`;
            } else if (q.type === 'short_answer') {
                html += `<div class="q-text">${q.question.replace(/\n/g, '<br>')}</div>`;
                html += `<div style="margin-top:8px;"><div style="border-bottom:1px solid #e2e8f0;height:40px;"></div>`;
                html += `<div style="border-bottom:1px solid #e2e8f0;height:40px;"></div></div>`;
            } else if (q.type === 'essay') {
                html += `<div class="q-text">${q.question.replace(/\n/g, '<br>')}</div>`;
                html += `<div style="margin-top:8px;">`;
                html += `<div style="border-bottom:1px solid #e2e8f0;height:60px;margin-bottom:4px;"></div>`;
                html += `<div style="border-bottom:1px solid #e2e8f0;height:60px;margin-bottom:4px;"></div>`;
                html += `<div style="border-bottom:1px solid #e2e8f0;height:60px;margin-bottom:4px;"></div>`;
                html += `<div style="border-bottom:1px solid #e2e8f0;height:60px;"></div></div>`;
            }

            const answerId = 'answer-' + q.id;
            html += `<div class="q-answer-toggle" onclick="window.toggleAnswer('${answerId}')">`;
            html += `&#128065; Показать ответ`;
            html += `</div>`;
            html += `<div class="q-answer-hidden" id="${answerId}">`;
            html += `<strong>Ответ:</strong> ${(q.answer || '—').replace(/\n/g, '<br>')}`;
            html += `</div>`;

            html += `</div>`;
        });

        html += `<div class="total-row">`;
        html += `<span>Всего</span>`;
        html += `<span>${test.questions.length} вопросов · ${test.total_points} баллов</span>`;
        html += `</div>`;

        previewContent.innerHTML = html;
        emptyState.style.display = 'none';
        previewContent.style.display = 'block';
        previewActions.style.display = 'flex';

        if (window.innerWidth <= 900) {
            document.querySelector('.preview-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    // ---------- Toggle Answer ----------
    window.toggleAnswer = function (id) {
        const el = document.getElementById(id);
        if (!el) return;
        const toggle = el.previousElementSibling;
        if (el.classList.contains('visible')) {
            el.classList.remove('visible');
            if (toggle) toggle.innerHTML = '&#128065; Показать ответ';
        } else {
            el.classList.add('visible');
            if (toggle) toggle.innerHTML = '&#128064; Скрыть ответ';
        }
    };

    // ---------- Print ----------
    window.printTest = function () {
        if (!currentTestData) {
            showToast('Нет данных для печати');
            return;
        }
        window.open('print_test.php', '_blank', 'width=900,height=700');
    };

    // ---------- Download Moodle XML ----------
    window.downloadMoodleXml = function () {
        if (!currentTestData) {
            showToast('Нет данных для экспорта');
            return;
        }
        window.location.href = 'moodle_export.php';
    };

    // ---------- Regenerate ----------
    window.regenerate = function () {
        form.dispatchEvent(new Event('submit'));
    };

    // ============================================================
    // MOODLE INTEGRATION
    // ============================================================

    window.openMoodleModal = function () {
        if (!currentTestData) {
            showToast('Сначала сгенерируйте тест');
            return;
        }
        document.getElementById('moodleModal').style.display = 'flex';
        document.getElementById('moodleStep1').style.display = 'block';
        document.getElementById('moodleStep2').style.display = 'none';
        document.getElementById('moodleStep3').style.display = 'none';
        document.getElementById('moodleValidationStatus').innerHTML = '';
        document.getElementById('moodleImportStatus').innerHTML = '';
        document.getElementById('summaryTestName').textContent = currentTestData.title || 'Тест';
        document.getElementById('summaryCount').textContent = currentTestData.questions.length;
    };

    window.closeMoodleModal = function () {
        document.getElementById('moodleModal').style.display = 'none';
    };

    // Close on overlay click
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('modal-overlay')) {
            closeMoodleModal();
        }
    });

    // Close on Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeMoodleModal();
        }
    });

    // ---------- Step 1: Validate ----------
    window.validateMoodle = async function () {
        moodleUrl = document.getElementById('moodleUrl').value.trim();
        moodleToken = document.getElementById('moodleToken').value.trim();

        if (!moodleUrl) {
            showMoodleStatus('Укажите URL Moodle', 'error');
            return;
        }
        if (!moodleToken) {
            showMoodleStatus('Введите токен', 'error');
            return;
        }

        const btn = document.getElementById('moodleValidateBtn');
        btn.disabled = true;
        btn.textContent = 'Подключение...';
        showMoodleStatus('Проверяем подключение...', 'info');

        try {
            const fd = new FormData();
            fd.append('action', 'validate');
            fd.append('moodle_url', moodleUrl);
            fd.append('token', moodleToken);

            const resp = await fetch('moodle_api.php', { method: 'POST', body: fd });
            const data = await resp.json();

            if (!resp.ok || !data.success) {
                throw new Error(data.error || 'Ошибка подключения');
            }

            showMoodleStatus(
                'Подключено! Пользователь: ' + escapeHtml(data.user.fullname) + ' · ' + escapeHtml(data.user.sitename),
                'success'
            );

            // Load courses
            await loadMoodleCourses();

            // Go to step 2
            document.getElementById('moodleStep1').style.display = 'none';
            document.getElementById('moodleStep2').style.display = 'block';

        } catch (err) {
            showMoodleStatus(err.message, 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Подключиться';
        }
    };

    // ---------- Load courses ----------
    async function loadMoodleCourses() {
        const sel = document.getElementById('moodleCourse');
        sel.innerHTML = '<option value="">Загрузка...</option>';
        sel.disabled = true;

        try {
            const fd = new FormData();
            fd.append('action', 'get_courses');
            fd.append('moodle_url', moodleUrl);
            fd.append('token', moodleToken);

            const resp = await fetch('moodle_api.php', { method: 'POST', body: fd });
            const data = await resp.json();

            if (!resp.ok || !data.success) {
                throw new Error(data.error || 'Не удалось загрузить курсы');
            }

            sel.innerHTML = '<option value="">— Выберите курс —</option>';
            data.courses.forEach(function (c) {
                var opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.fullname + ' (' + c.shortname + ')';
                sel.appendChild(opt);
            });
            sel.disabled = false;
        } catch (err) {
            sel.innerHTML = '<option value="">Ошибка загрузки</option>';
            showMoodleStatus(err.message, 'error');
        }
    }

    // ---------- Course changed -> load sections ----------
    window.moodleCourseChanged = async function () {
        var courseId = document.getElementById('moodleCourse').value;
        var secSel = document.getElementById('moodleSection');
        var btn = document.getElementById('moodleStep2Btn');

        secSel.innerHTML = '<option value="">Загрузка...</option>';
        btn.disabled = true;

        if (!courseId) {
            secSel.innerHTML = '<option value="">Выберите сначала курс</option>';
            return;
        }

        try {
            var fd = new FormData();
            fd.append('action', 'get_sections');
            fd.append('moodle_url', moodleUrl);
            fd.append('token', moodleToken);
            fd.append('course_id', courseId);

            var resp = await fetch('moodle_api.php', { method: 'POST', body: fd });
            var data = await resp.json();

            if (!resp.ok || !data.success) {
                throw new Error(data.error || 'Ошибка загрузки разделов');
            }

            secSel.innerHTML = '<option value="">— Не добавлять в раздел —</option>';
            data.sections.forEach(function (s) {
                var opt = document.createElement('option');
                opt.value = s.section;
                opt.textContent = s.name;
                secSel.appendChild(opt);
            });
            secSel.disabled = false;
            btn.disabled = false;
        } catch (err) {
            secSel.innerHTML = '<option value="">Ошибка загрузки</option>';
            showMoodleStatus(err.message, 'error');
        }
    };

    // ---------- Step 2 -> 3 ----------
    window.moodleGoToStep3 = function () {
        var courseSel = document.getElementById('moodleCourse');
        var sectionSel = document.getElementById('moodleSection');
        var courseName = courseSel.options[courseSel.selectedIndex]?.text || '';
        var sectionName = sectionSel.options[sectionSel.selectedIndex]?.text || '';

        if (!courseSel.value) {
            showToast('Выберите курс');
            return;
        }

        document.getElementById('summaryCourse').textContent = courseName;
        document.getElementById('summarySection').textContent = sectionName;
        document.getElementById('moodleStep2').style.display = 'none';
        document.getElementById('moodleStep3').style.display = 'block';
    };

    // ---------- Step 3: Import ----------
    window.importToMoodle = async function () {
        var btn = document.getElementById('moodleImportBtn');
        btn.disabled = true;
        btn.innerHTML = '&#128260; Импортируем...';
        showImportStatus('Импорт вопросов в Moodle...', 'info');

        try {
            var fd = new FormData();
            fd.append('action', 'import_questions');
            fd.append('moodle_url', moodleUrl);
            fd.append('token', moodleToken);
            fd.append('course_id', document.getElementById('moodleCourse').value);
            fd.append('section_id', document.getElementById('moodleSection').value);

            var resp = await fetch('moodle_api.php', { method: 'POST', body: fd });
            var data = await resp.json();

            if (!resp.ok || !data.success) {
                throw new Error(data.error || 'Ошибка импорта');
            }

            var msg = 'Импортировано ' + data.imported + ' из ' + data.total + ' вопросов';
            if (data.quiz_url) {
                msg += '<br><br>Тест-активность создана: <a href="' + escapeHtml(data.quiz_url) + '" target="_blank" style="color:#4299e1;">Перейти к тесту в Moodle</a>';
            }
            if (data.quiz_error) {
                msg += '<br><br><em>' + escapeHtml(data.quiz_error) + '</em>';
            }
            showImportStatus(msg, 'success');
            btn.textContent = '&#10003; Готово!';

            // If errors, show them
            if (data.errors && data.errors.length > 0) {
                var errList = '<br><br><strong>Ошибки:</strong><ul>';
                data.errors.forEach(function (e) {
                    errList += '<li>' + escapeHtml(e) + '</li>';
                });
                errList += '</ul>';
                showImportStatus(msg + errList, 'warning');
            }
        } catch (err) {
            showImportStatus(err.message, 'error');
            btn.disabled = false;
            btn.innerHTML = '&#10133; Добавить тест в Moodle';
        }
    };

    // ---------- Moodle UI helpers ----------
    function showMoodleStatus(msg, type) {
        var el = document.getElementById('moodleValidationStatus');
        el.innerHTML = msg;
        el.className = 'moodle-status moodle-status--' + (type || 'info');
    }

    function showImportStatus(msg, type) {
        var el = document.getElementById('moodleImportStatus');
        el.innerHTML = msg;
        el.className = 'moodle-status moodle-status--' + (type || 'info');
    }

    // ---------- Toast ----------
    function showToast(message, type) {
        if (toastTimer) {
            clearTimeout(toastTimer);
            toast.classList.remove('visible', 'toast--success');
        }

        toast.textContent = message;
        if (type === 'success') {
            toast.classList.add('toast--success');
        } else {
            toast.classList.remove('toast--success');
        }
        toast.classList.add('visible');

        toastTimer = setTimeout(function () {
            toast.classList.remove('visible');
            toastTimer = null;
        }, 5000);
    }

    function hideError() {
        toast.classList.remove('visible');
    }

    // ---------- Utility ----------
    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function pluralize(n, one, few, many) {
        n = Math.abs(n) % 100;
        var n1 = n % 10;
        if (n > 10 && n < 20) return n + ' ' + many;
        if (n1 > 1 && n1 < 5) return n + ' ' + few;
        if (n1 === 1) return n + ' ' + one;
        return n + ' ' + many;
    }

    // ---------- Keyboard shortcut ----------
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            form.dispatchEvent(new Event('submit'));
        }
    });

})();
