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

    // ---------- Print Test ----------
    window.printTest = function () {
        if (!currentTestData) {
            showToast('Нет данных для печати');
            return;
        }
        window.open('print_test.php', '_blank', 'width=900,height=700');
    };

    // ---------- Regenerate ----------
    window.regenerate = function () {
        form.dispatchEvent(new Event('submit'));
    };

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
