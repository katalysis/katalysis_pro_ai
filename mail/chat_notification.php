<?php
defined('C5_EXECUTE') or die("Access Denied.");

use Concrete\Core\Package\Package;

// Available variables passed from controller:
// $chat - The chat entity
// $chatContent - Formatted array of messages  
// $chatUrl - URL to view chat in dashboard

$subject = t('Chat Notification - Chat #%s', $chat->getId());

/**
 * Clean chat content for email display
 */
function cleanChatContentForEmail($content) {
    if (empty($content)) return '';
    
    // Handle form submissions specially
    if (strpos($content, 'Form submitted:') === 0) {
        // Extract form data and format nicely
        $content = preg_replace('/<div class="form-submission-summary">.*?<\/div>/s', '', $content);
        $content = str_replace('Form submitted:', 'Form submitted with the following information:', $content);
        return $content;
    }
    
    // Remove chatbot-specific HTML elements and classes
    $content = preg_replace('/<div class="simple-form-container"[^>]*>.*?<\/div>/s', '', $content);
    $content = preg_replace('/<div class="more-info-links">.*?<\/div>/s', '', $content);
    $content = preg_replace('/<div class="form-submission-summary">.*?<\/div>/s', '', $content);
    
    // Convert chatbot links to simple text
    $content = preg_replace('/<a href="([^"]*)"[^>]*class="chatbot-text-link"[^>]*>([^<]*)<\/a>/', '$2 ($1)', $content);
    $content = preg_replace('/<a href="([^"]*)"[^>]*class="link-button"[^>]*><i[^>]*><\/i>\s*([^<]*)<\/a>/', '• $2: $1', $content);
    
    // Remove any remaining HTML tags except basic formatting
    $allowedTags = '<br><strong><em><p>';
    $content = strip_tags($content, $allowedTags);
    
    // Clean up extra whitespace
    $content = preg_replace('/\s+/', ' ', $content);
    $content = trim($content);
    
    return $content;
}

// Build the email content
$email = '<h1 style="background: #e7eff7; padding: 20px; margin-bottom: 20px;">Chat Notification - Chat #' . $chat->getId() . '</h1>';

// Chat basic information
$email .= '<div style="margin-bottom: 20px;">
    <h3>Chat Information</h3>
    <p><strong>Chat ID:</strong> ' . $chat->getId() . '</p>
    <p><strong>Started:</strong> ' . ($chat->getStarted() ? $chat->getStarted()->format('Y-m-d H:i:s') : 'Not set') . '</p>
    <p><strong>Location:</strong> ' . ($chat->getLocation() ?: 'Not set') . '</p>
    <p><strong>User:</strong> ' . ($chat->getName() ?: 'Anonymous') . '</p>';
    
if ($chat->getEmail()) {
    $email .= '<p><strong>Email:</strong> ' . $chat->getEmail() . '</p>';
}

$email .= '<p><strong>Page:</strong> ' . ($chat->getLaunchPageTitle() ?: 'Not set') . '</p>
    <p><strong>View Chat:</strong> <a href="' . $chatUrl . '">Click here to view full chat</a></p>
</div>';

// Chat conversation
if (!empty($chatContent)) {
    $email .= '<div style="margin-bottom: 20px;">
        <h3>Conversation History</h3>';
    
    foreach ($chatContent as $message) {
        // Clean the message content for email display
        $cleanContent = cleanChatContentForEmail($message['content']);
        
        if ($message['type'] === 'welcome') {
            // Welcome message styling (similar to AI message but highlighted)
            $email .= '<div style="margin-bottom: 15px; padding: 12px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #007bff;">
                <div style="color: #007bff; font-weight: bold; margin-bottom: 8px;">
                    <img src="' . Package::getByHandle('katalysis_pro_ai')->getRelativePath() . '/assistant-icon.png" alt="AI" style="width: 24px; height: 24px; margin-right: 5px; vertical-align: middle;"> AI Assistant - Welcome Message
                </div>
                <div style="color: #333; line-height: 1.4;">' . nl2br($cleanContent) . '</div>
            </div>';
        } else if ($message['sender'] === 'user') {
            // User message: white text on blue background
            $timestamp = $message['timestamp'] ? ' - ' . $message['timestamp'] : '';
            $email .= '<div style="margin-bottom: 15px; padding: 12px; background: #007bff; color: white; border-radius: 8px;">
                <div style="font-weight: bold; margin-bottom: 8px;">
                    <img src="' . Package::getByHandle('katalysis_pro_ai')->getRelativePath() . '/user-icon.png" alt="User" style="width: 24px; height: 24px; margin-right: 5px; vertical-align: middle;"> You' . $timestamp . '
                </div>
                <div style="line-height: 1.4;">' . nl2br($cleanContent) . '</div>
            </div>';
        } else {
            // AI message: black text on light grey background
            $timestamp = $message['timestamp'] ? ' - ' . $message['timestamp'] : '';
            $email .= '<div style="margin-bottom: 15px; padding: 12px; background: #f8f9fa; color: #333; border-radius: 8px;">
                <div style="font-weight: bold; margin-bottom: 8px; color: #6c757d;">
                    <img src="' . Package::getByHandle('katalysis_pro_ai')->getRelativePath() . '/assistant-icon.png" alt="AI" style="width: 24px; height: 24px; margin-right: 5px; vertical-align: middle;"> AI Assistant' . $timestamp . '
                </div>
                <div style="line-height: 1.4;">' . nl2br($cleanContent) . '</div>
            </div>';
        }
    }
    
    $email .= '</div>';
}

//--------------------------------------------------------------------------

$bodyHTML = $email;
$body = strip_tags($bodyHTML);

/* Add header and footer */
if (file_exists(__DIR__ . '/email_config.php')) {
    include 'email_config.php';	
    $email = $header;
    $email .= $bodyHTML;
    $email .= $footer;
    $bodyHTML = $email;
    $body = strip_tags($bodyHTML);
} else {
    // If no email_config.php, use the content as is
    $bodyHTML = $email;
}
?>