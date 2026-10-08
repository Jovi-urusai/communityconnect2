(function () {
    'use strict';

    var toggle = document.getElementById('chatbotToggle');
    var closeBtn = document.getElementById('chatbotClose');
    var win = document.getElementById('chatbotWindow');
    var messages = document.getElementById('chatbotMessages');
    var typing = document.getElementById('chatbotTyping');
    var form = document.getElementById('chatbotForm');
    var input = document.getElementById('chatbotInput');

    if (!toggle || !win || !form || !input) return;

    function openWindow() {
        win.classList.remove('hidden');
        input.focus();
    }

    function closeWindow() {
        win.classList.add('hidden');
    }

    toggle.addEventListener('click', function () {
        win.classList.contains('hidden') ? openWindow() : closeWindow();
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', closeWindow);
    }

    function appendMessage(text, who) {
        var bubble = document.createElement('div');
        bubble.className = 'chatbot-msg chatbot-msg-' + who;
        bubble.textContent = text;
        messages.appendChild(bubble);
        messages.scrollTop = messages.scrollHeight;
    }

    function setBusy(isBusy) {
        input.disabled = isBusy;
        form.querySelector('button[type="submit"]').disabled = isBusy;
        typing.classList.toggle('hidden', !isBusy);
        if (isBusy) {
            messages.scrollTop = messages.scrollHeight;
        }
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var text = input.value.trim();
        if (!text) return;

        appendMessage(text, 'user');
        input.value = '';
        setBusy(true);

        fetch('chatbot_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: text }),
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                appendMessage(data.reply || "Sorry, something went wrong. Please try again.", 'bot');
            })
            .catch(function () {
                appendMessage("Sorry, I couldn't reach the assistant. Please check your connection and try again.", 'bot');
            })
            .finally(function () {
                setBusy(false);
            });
    });
})();
