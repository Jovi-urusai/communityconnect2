<?php
require_once __DIR__ . '/../../config/resident_auth.php';

if (!resident_is_logged_in()) {
    return;
}
?>
<div class="chatbot-widget">
    <button type="button" class="chatbot-toggle" id="chatbotToggle" aria-label="Open chat assistant">
        <?php icon('message-circle'); ?>
    </button>

    <div class="chatbot-window hidden" id="chatbotWindow">
        <div class="chatbot-header">
            <span><?php icon('bot'); ?> CommunityConnect Assistant</span>
            <button type="button" class="chatbot-close" id="chatbotClose" aria-label="Close chat">&times;</button>
        </div>
        <div class="chatbot-messages" id="chatbotMessages">
            <div class="chatbot-msg chatbot-msg-bot">
                Hi! I'm the CommunityConnect assistant for Sto. Niño Barangay Hall. How can I help you today?
            </div>
        </div>
        <div class="chatbot-typing hidden" id="chatbotTyping">
            <span></span><span></span><span></span>
        </div>
        <form class="chatbot-input-row" id="chatbotForm">
            <input type="text" id="chatbotInput" placeholder="Type a message..." maxlength="1000" autocomplete="off" required>
            <button type="submit" class="chatbot-send" aria-label="Send message"><?php icon('send'); ?></button>
        </form>
    </div>
</div>
<script src="assets/js/chatbot.js"></script>
