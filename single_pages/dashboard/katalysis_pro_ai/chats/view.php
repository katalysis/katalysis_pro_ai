<?php
defined('C5_EXECUTE') or die("Access Denied.");

/** @var \KatalysisProAi\Entity\Chat $chat */
/** @var string $pageTitle */

// This template is for individual chat view - chat should never be null
if (!$chat) {
    echo '<div class="alert alert-danger">' . t('Chat not found.') . '</div>';
    return;
}
?>

<div class="ccm-dashboard-header-buttons">
    <button id="send-chat-email" class="btn btn-primary me-2" data-chat-id="<?php echo $chat->getId(); ?>">
        <i class="fa fa-envelope"></i> <?php echo t('Send Email'); ?>
    </button>
    <a href="<?php echo $this->action(''); ?>" class="btn btn-secondary">
        <i class="fa fa-arrow-left"></i> <?php echo t('Back to Chats'); ?>
    </a>
</div>

<div class="ccm-dashboard-content-full">
    <div class="container-fluid">
        <div class="row justify-content-between">
            <div class="col-md-5">
                <h4><?php echo t('Basic Information'); ?></h4>
                <table class="table table-striped">
                    <tbody>
                        <tr>
                            <th style="width:40%;"><?php echo t('ID'); ?></th>
                            <td><?php echo $chat->getId(); ?></td>
                        </tr>
                        <tr>
                            <th style="width:40%;"><?php echo t('Session ID'); ?></th>
                            <td><?php echo $chat->getSessionId() ?: t('Not set'); ?></td>
                        </tr>
                        <tr>
                            <th style="width:40%;"><?php echo t('Started'); ?></th>
                            <td><?php echo $chat->getStarted() ? $chat->getStarted()->format('Y-m-d H:i:s') : t('Not set'); ?>
                            </td>
                        </tr>
                        <tr>
                            <th style="width:40%;"><?php echo t('Created Date'); ?></th>
                            <td><?php echo $chat->getCreatedDate() ? $chat->getCreatedDate()->format('Y-m-d H:i:s') : t('Not set'); ?>
                            </td>
                        </tr>
                        <tr>
                            <th style="width:40%;"><?php echo t('Created By'); ?></th>
                            <td><?php echo $chat->getCreatedBy(); ?></td>
                        </tr>
                        <tr>
                            <th style="width:40%;"><?php echo t('Location'); ?></th>
                            <td><?php echo $chat->getLocation() ?: t('Not set'); ?></td>
                        </tr>
                        <tr>
                            <th style="width:40%;"><?php echo t('LLM'); ?></th>
                            <td><?php echo $chat->getLlm() ?: t('Not set'); ?></td>
                        </tr>
                        <tr>
                            <th style="width:40%;"><?php echo t('User Message Count'); ?></th>
                            <td>
                                <span class="badge bg-primary"><?php echo $chat->getUserMessageCount() ?: '0'; ?></span>
                                <small class="text-muted ms-2"><?php echo t('engagement metric'); ?></small>
                            </td>
                        </tr>
                        <tr>
                            <th style="width:40%;"><?php echo t('Welcome Message'); ?></th>
                            <td>
                                <?php if ($chat->getWelcomeMessage() && trim($chat->getWelcomeMessage()) !== ''): ?>
                                    <div class="welcome-message-display bg-light p-2 rounded border">
                                        <i class="fa fa-comments text-primary me-1"></i>
                                        <?php echo htmlspecialchars($chat->getWelcomeMessage()); ?>
                                    </div>
                                <?php else: ?>
                                    <em class="text-muted"><?php echo t('No welcome message recorded'); ?></em>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <h4><?php echo t('Page Information'); ?></h4>
                <table class="table table-striped">
                    <tbody>
                        <tr>
                            <th style="width:40%;"><?php echo t('Page Title'); ?></th>
                            <td><?php echo $chat->getLaunchPageTitle() ?: t('Not set'); ?></td>
                        </tr>
                        <tr>
                            <th style="width:40%;"><?php echo t('Page URL'); ?></th>
                            <td style="word-wrap: break-word;word-break: break-all;">
                                <?php if ($chat->getLaunchPageUrl()): ?>
                                    <a href="<?php echo htmlspecialchars($chat->getLaunchPageUrl()); ?>" target="_blank">
                                        <?php echo htmlspecialchars($chat->getLaunchPageUrl()); ?>
                                    </a>
                                <?php else: ?>
                                    <?php echo t('Not set'); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th style="width:40%;"><?php echo t('Page Type'); ?></th>
                            <td><?php echo $chat->getLaunchPageType() ?: t('Not set'); ?></td>
                        </tr>
                    </tbody>
                </table>
                <h4><?php echo t('User Information'); ?></h4>
                <table class="table table-striped">
                    <tbody>
                        <tr>
                            <th style="width:40%;"><?php echo t('Name'); ?></th>
                            <td><?php echo $chat->getName() ?: t('Not set'); ?></td>
                        </tr>
                        <tr>
                            <th style="width:40%;"><?php echo t('Email'); ?></th>
                            <td>
                                <?php if ($chat->getEmail()): ?>
                                    <a href="mailto:<?php echo htmlspecialchars($chat->getEmail()); ?>">
                                        <?php echo htmlspecialchars($chat->getEmail()); ?>
                                    </a>
                                <?php else: ?>
                                    <?php echo t('Not set'); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th style="width:40%;"><?php echo t('Phone'); ?></th>
                            <td><?php echo $chat->getPhone() ?: t('Not set'); ?></td>
                        </tr>
                    </tbody>
                </table>
                <h4><?php echo t('UTM Tracking'); ?></h4>
                <table class="table table-striped">
                    <tbody>
                        <tr>
                            <th style="width:40%;"><?php echo t('UTM ID'); ?></th>
                            <td><?php echo $chat->getUtmId() ?: t('Not set'); ?></td>
                        </tr>
                        <tr>
                            <th style="width:40%;"><?php echo t('UTM Source'); ?></th>
                            <td><?php echo $chat->getUtmSource() ?: t('Not set'); ?></td>
                        </tr>
                        <tr>
                            <th style="width:40%;"><?php echo t('UTM Medium'); ?></th>
                            <td><?php echo $chat->getUtmMedium() ?: t('Not set'); ?></td>
                        </tr>
                        <tr>
                            <th style="width:40%;"><?php echo t('UTM Campaign'); ?></th>
                            <td><?php echo $chat->getUtmCampaign() ?: t('Not set'); ?></td>
                        </tr>
                        <tr>
                            <th style="width:40%;"><?php echo t('UTM Term'); ?></th>
                            <td><?php echo $chat->getUtmTerm() ?: t('Not set'); ?></td>
                        </tr>
                        <tr>
                            <th style="width:40%;"><?php echo t('UTM Content'); ?></th>
                            <td><?php echo $chat->getUtmContent() ?: t('Not set'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="col-md-6">
                <div class="row mt-4">
                    <!-- Complete Chat History -->
                    <div class="col-12">
                        <?php if ($chat->getCompleteChatHistory()): ?>
                            <?php
                            $chatHistory = json_decode($chat->getCompleteChatHistory(), true);
                            if (is_array($chatHistory) && !empty($chatHistory)): ?>
                                
                                <?php // Display welcome message first if it exists ?>
                                <?php if ($chat->getWelcomeMessage() && trim($chat->getWelcomeMessage()) !== ''): ?>
                                    <div class="ai-response welcome-message-chat-display mb-3">
                                        <div class="message-content">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fa fa-robot me-2 text-primary"></i>
                                                <strong><?php echo t('AI Assistant'); ?></strong>
                                                <small class="ms-auto me-2">
                                                    <em class="text-muted"><?php echo t('Welcome Message'); ?></em>
                                                </small>
                                            </div>
                                            <div class="welcome-message-text bg-primary bg-opacity-10 p-3 rounded border-start border-primary border-4">
                                                <?php echo nl2br(htmlspecialchars($chat->getWelcomeMessage())); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php foreach ($chatHistory as $message): ?>
                                    <div class="<?php echo $message['sender'] === 'user' ? 'user-message' : 'ai-response'; ?>">
                                        <div class="message-content">
                                            <i class="fa <?php echo $message['sender'] === 'user' ? 'fa-user' : 'fa-robot'; ?> me-1"></i>
                                            <strong><?php echo $message['sender'] === 'user' ? t('You') : t('AI Assistant'); ?></strong>

                                            <small class="ms-auto me-2">
                                                <?php echo isset($message['timestamp']) ? date('H:i:s', $message['timestamp'] / 1000) : ''; ?>
                                            </small>

                                            <?php if (isset($message['content']) && is_string($message['content'])): ?>
                                                <?php if ($message['sender'] === 'ai'): ?>
                                                    <?php echo nl2br($message['content']); ?>
                                                <?php else: ?>
                                                    <?php 
                                                    // Check if this is a form submission message
                                                    if (strpos($message['content'], 'Form submitted:') === 0 && strpos($message['content'], '<div class="form-submission-summary">') !== false) {
                                                        // For form submissions, allow safe HTML but strip potentially dangerous tags
                                                        $allowedTags = '<div><ul><li><strong>';
                                                        echo nl2br(strip_tags($message['content'], $allowedTags));
                                                    } else {
                                                        // For regular user messages, escape HTML
                                                        echo nl2br(htmlspecialchars($message['content']));
                                                    }
                                                    ?>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <em class="text-muted"><?php echo t('Message content not available'); ?></em>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                            <?php else: ?>
                                <?php // If no chat history but there's a welcome message, show just the welcome message ?>
                                <?php if ($chat->getWelcomeMessage() && trim($chat->getWelcomeMessage()) !== ''): ?>
                                    <div class="ai-response welcome-message-chat-display mb-3">
                                        <div class="message-content">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fa fa-robot me-2 text-primary"></i>
                                                <strong><?php echo t('AI Assistant'); ?></strong>
                                                <small class="ms-auto me-2">
                                                    <em class="text-muted"><?php echo t('Welcome Message'); ?></em>
                                                </small>
                                            </div>
                                            <div class="welcome-message-text bg-primary bg-opacity-10 p-3 rounded border-start border-primary border-4">
                                                <?php echo nl2br(htmlspecialchars($chat->getWelcomeMessage())); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <em class="text-muted mt-2 d-block"><?php echo t('No further conversation yet'); ?></em>
                                <?php else: ?>
                                    <em class="text-muted"><?php echo t('No chat history available'); ?></em>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                        <?php else: ?>
                            <?php // If no complete chat history but there's a welcome message, show just the welcome message ?>
                            <?php if ($chat->getWelcomeMessage() && trim($chat->getWelcomeMessage()) !== ''): ?>
                                <div class="ai-response welcome-message-chat-display mb-3">
                                    <div class="message-content">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fa fa-robot me-2 text-primary"></i>
                                            <strong><?php echo t('AI Assistant'); ?></strong>
                                            <small class="ms-auto me-2">
                                                <em class="text-muted"><?php echo t('Welcome Message'); ?></em>
                                            </small>
                                        </div>
                                        <div class="welcome-message-text bg-primary bg-opacity-10 p-3 rounded border-start border-primary border-4">
                                            <?php echo nl2br(htmlspecialchars($chat->getWelcomeMessage())); ?>
                                        </div>
                                    </div>
                                </div>
                                <em class="text-muted mt-2 d-block"><?php echo t('No further conversation yet'); ?></em>
                            <?php else: ?>
                                <em class="text-muted"><?php echo t('Chat history not yet implemented'); ?></em>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Form submission styling for dashboard chat view */
.form-submission-summary {
    margin-top: 8px;
}

.form-submission-summary ul {
    margin: 0;
    padding-left: 20px;
}

.form-submission-summary li {
    margin-bottom: 4px;
}

</style>

<script>
$(document).ready(function() {
    $('#send-chat-email').on('click', function() {
        var chatId = $(this).data('chat-id');
        var $button = $(this);
        
        // Disable button and show loading state
        $button.prop('disabled', true);
        $button.html('<i class="fa fa-spinner fa-spin"></i> <?php echo t('Sending...'); ?>');
        
        $.ajax({
            url: '<?php echo \Concrete\Core\Support\Facade\Url::to('/dashboard/katalysis_pro_ai/chat_bot_settings/send_chat_email'); ?>',
            type: 'POST',
            data: {
                chat_id: chatId,
                ccm_token: '<?php echo \Concrete\Core\Support\Facade\Application::getFacadeApplication()->make('token')->generate('send_chat_email'); ?>'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Show success message
                    ConcreteAlert.notify({
                        'message': response.message || '<?php echo t('Email sent successfully'); ?>',
                        'title': '<?php echo t('Success'); ?>',
                        'type': 'success'
                    });
                } else {
                    // Show error message
                    ConcreteAlert.notify({
                        'message': response.message || '<?php echo t('Failed to send email'); ?>',
                        'title': '<?php echo t('Error'); ?>',
                        'type': 'error'
                    });
                }
            },
            error: function() {
                // Show error message
                ConcreteAlert.notify({
                    'message': '<?php echo t('An error occurred while sending the email'); ?>',
                    'title': '<?php echo t('Error'); ?>',
                    'type': 'error'
                });
            },
            complete: function() {
                // Re-enable button and restore text
                $button.prop('disabled', false);
                $button.html('<i class="fa fa-envelope"></i> <?php echo t('Send Email'); ?>');
            }
        });
    });
});
</script>
