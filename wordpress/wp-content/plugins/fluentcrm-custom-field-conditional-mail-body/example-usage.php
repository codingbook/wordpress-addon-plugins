<?php
/**
 * Example Usage of FluentCRM Custom Field Conditional Content
 * 
 * This file demonstrates various ways to use the custom field conditional functionality
 * in FluentCRM email templates and automation workflows.
 */

// Example 1: Basic conditional content in email template
$emailTemplateExample = '
<h1>Welcome {{contact.first_name}}!</h1>

<!-- Show different content based on membership level -->
[fc_custom_field_conditional field="membership_level" operator="equals" value="premium" 
show_if_true="<h2>Premium Member Benefits</h2><p>You have access to exclusive content and features!</p>" 
show_if_false="<h2>Upgrade to Premium</h2><p>Get access to exclusive content and features.</p>"]

<!-- Show different images based on purchase count -->
[fc_custom_field_conditional field="total_purchases" operator="greater_than" value="10" 
show_if_true="<img src=\"https://yoursite.com/vip-badge.jpg\" alt=\"VIP Customer\" style=\"width: 100px; height: 100px;\" />" 
show_if_false="<img src=\"https://yoursite.com/standard-badge.jpg\" alt=\"Standard Customer\" style=\"width: 100px; height: 100px;\" />]

<!-- Conditional buttons based on subscription status -->
[fc_custom_field_conditional field="subscription_status" operator="equals" value="active" 
show_if_true="<a href=\"https://yoursite.com/account\" style=\"background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px;\">Manage Account</a>" 
show_if_false="<a href=\"https://yoursite.com/subscribe\" style=\"background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px;\">Subscribe Now</a>"]
';

// Example 2: Complex conditional logic with multiple fields
$complexExample = '
<!-- Check if user is a premium member AND has completed onboarding -->
[fc_custom_field_conditional field="membership_level" operator="equals" value="premium" 
show_if_true="
    [fc_custom_field_conditional field=\"onboarding_completed\" operator=\"equals\" value=\"yes\" 
    show_if_true=\"<h2>Welcome to Premium!</h2><p>Your account is fully set up and ready to go.</p>\" 
    show_if_false=\"<h2>Complete Your Setup</h2><p>Please complete your onboarding to access premium features.</p>\"]
" 
show_if_false="<h2>Upgrade Required</h2><p>Upgrade to premium to access advanced features.</p>"]

<!-- Show location-specific content -->
[fc_custom_field_conditional field="country" operator="contains" value="US" 
show_if_true="<p>Free shipping available for US customers!</p>" 
show_if_false="<p>International shipping available.</p>"]
';

// Example 3: Using PHP helper functions in custom code
function exampleCustomCode($subscriber) {
    // Get custom field value
    $membershipLevel = fluentcrm_get_custom_field_value('membership_level', $subscriber);
    $purchaseCount = fluentcrm_get_custom_field_value('total_purchases', $subscriber);
    
    // Check conditions
    $isPremium = fluentcrm_check_custom_field_condition('membership_level', 'equals', 'premium', $subscriber);
    $isVIP = fluentcrm_check_custom_field_condition('total_purchases', 'greater_than', '10', $subscriber);
    
    // Build dynamic content
    $content = '<h1>Welcome ' . $subscriber->first_name . '!</h1>';
    
    if ($isPremium && $isVIP) {
        $content .= '<h2>VIP Premium Member</h2>';
        $content .= '<p>You are our most valued customer!</p>';
        $content .= '<img src="https://yoursite.com/vip-premium-badge.jpg" alt="VIP Premium" />';
    } elseif ($isPremium) {
        $content .= '<h2>Premium Member</h2>';
        $content .= '<p>Thank you for being a premium member!</p>';
        $content .= '<img src="https://yoursite.com/premium-badge.jpg" alt="Premium" />';
    } else {
        $content .= '<h2>Welcome!</h2>';
        $content .= '<p>Upgrade to premium for exclusive benefits.</p>';
        $content .= '<a href="https://yoursite.com/upgrade">Upgrade Now</a>';
    }
    
    return $content;
}

// Example 4: Integration with FluentCRM automation workflows
function exampleAutomationIntegration($subscriber) {
    // This could be used in a custom automation action
    $courseProgress = fluentcrm_get_custom_field_value('course_progress', $subscriber);
    $lastActivity = fluentcrm_get_custom_field_value('last_activity_date', $subscriber);
    
    // Send different emails based on progress
    if (fluentcrm_check_custom_field_condition('course_progress', 'greater_than', '80', $subscriber)) {
        // Send completion email
        return [
            'subject' => 'Congratulations! You\'re almost done!',
            'body' => 'You\'ve completed ' . $courseProgress . '% of your course. Keep going!'
        ];
    } elseif (fluentcrm_check_custom_field_condition('course_progress', 'greater_than', '50', $subscriber)) {
        // Send encouragement email
        return [
            'subject' => 'Great Progress!',
            'body' => 'You\'re more than halfway through! Don\'t give up now.'
        ];
    } else {
        // Send motivation email
        return [
            'subject' => 'Start Your Learning Journey',
            'body' => 'Begin your course today and unlock new skills!'
        ];
    }
}

// Example 5: E-commerce integration
function exampleEcommerceIntegration($subscriber) {
    $orderCount = fluentcrm_get_custom_field_value('order_count', $subscriber);
    $totalSpent = fluentcrm_get_custom_field_value('total_spent', $subscriber);
    $lastOrderDate = fluentcrm_get_custom_field_value('last_order_date', $subscriber);
    
    // Determine customer tier
    if (fluentcrm_check_custom_field_condition('total_spent', 'greater_than', '1000', $subscriber)) {
        $tier = 'platinum';
    } elseif (fluentcrm_check_custom_field_condition('total_spent', 'greater_than', '500', $subscriber)) {
        $tier = 'gold';
    } elseif (fluentcrm_check_custom_field_condition('total_spent', 'greater_than', '100', $subscriber)) {
        $tier = 'silver';
    } else {
        $tier = 'bronze';
    }
    
    // Build personalized content
    $content = '<h2>Hello ' . $subscriber->first_name . '!</h2>';
    $content .= '<p>You are a ' . ucfirst($tier) . ' customer with ' . $orderCount . ' orders.</p>';
    
    // Add tier-specific benefits
    switch ($tier) {
        case 'platinum':
            $content .= '<p>You get exclusive early access to new products!</p>';
            break;
        case 'gold':
            $content .= '<p>You get free shipping on all orders!</p>';
            break;
        case 'silver':
            $content .= '<p>You get 10% off your next purchase!</p>';
            break;
        default:
            $content .= '<p>Make your first purchase to unlock benefits!</p>';
    }
    
    return $content;
}

// Example 6: LMS (Learning Management System) integration
function exampleLMSIntegration($subscriber) {
    $enrolledCourses = fluentcrm_get_custom_field_value('enrolled_courses', $subscriber);
    $completedCourses = fluentcrm_get_custom_field_value('completed_courses', $subscriber);
    $certificates = fluentcrm_get_custom_field_value('certificates_earned', $subscriber);
    
    $content = '<h2>Your Learning Dashboard</h2>';
    
    // Show enrollment status
    if (fluentcrm_check_custom_field_condition('enrolled_courses', 'not_empty', '', $subscriber)) {
        $content .= '<p>You are enrolled in ' . $enrolledCourses . ' courses.</p>';
        
        if (fluentcrm_check_custom_field_condition('completed_courses', 'greater_than', '0', $subscriber)) {
            $content .= '<p>You have completed ' . $completedCourses . ' courses.</p>';
            
            if (fluentcrm_check_custom_field_condition('certificates_earned', 'greater_than', '0', $subscriber)) {
                $content .= '<p>Congratulations! You have earned ' . $certificates . ' certificates!</p>';
                $content .= '<a href="https://yoursite.com/certificates">View Certificates</a>';
            }
        } else {
            $content .= '<p>Start your first course today!</p>';
            $content .= '<a href="https://yoursite.com/courses">Browse Courses</a>';
        }
    } else {
        $content .= '<p>You haven\'t enrolled in any courses yet.</p>';
        $content .= '<a href="https://yoursite.com/courses">Explore Courses</a>';
    }
    
    return $content;
}

// Example 7: Email template with multiple conditionals
$emailTemplateWithMultipleConditionals = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Personalized Email</title>
</head>
<body>
    <div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;">
        <h1>Hello {{contact.first_name}}!</h1>
        
        <!-- Personalized greeting based on time of day -->
        [fc_custom_field_conditional field="timezone" operator="contains" value="US" 
        show_if_true="<p>Good morning from the US!</p>" 
        show_if_false="<p>Hello from around the world!</p>"]
        
        <!-- Show different content based on user type -->
        [fc_custom_field_conditional field="user_type" operator="equals" value="student" 
        show_if_true="
            <h2>Student Resources</h2>
            <p>Check out our latest study materials and resources.</p>
            <a href=\"https://yoursite.com/student-resources\" style=\"background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px;\">View Resources</a>
        " 
        show_if_false="
            <h2>Professional Development</h2>
            <p>Enhance your career with our professional courses.</p>
            <a href=\"https://yoursite.com/professional-courses\" style=\"background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px;\">Browse Courses</a>
        "]
        
        <!-- Show loyalty program status -->
        [fc_custom_field_conditional field="loyalty_points" operator="greater_than" value="1000" 
        show_if_true="
            <div style=\"background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;\">
                <h3>VIP Status Unlocked!</h3>
                <p>You have {{contact.custom.loyalty_points}} loyalty points.</p>
                <p>Redeem them for exclusive rewards!</p>
                <a href=\"https://yoursite.com/rewards\" style=\"background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;\">View Rewards</a>
            </div>
        " 
        show_if_false="
            <div style=\"background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;\">
                <h3>Earn Loyalty Points</h3>
                <p>You currently have {{contact.custom.loyalty_points}} loyalty points.</p>
                <p>Earn more by completing courses and activities!</p>
            </div>
        "]
        
        <!-- Footer with conditional unsubscribe link -->
        <hr style="margin: 30px 0;">
        <p style="font-size: 12px; color: #666;">
            [fc_custom_field_conditional field="email_frequency" operator="equals" value="daily" 
            show_if_true="You receive daily updates. <a href=\"{{crm.manage_subscription_url}}\">Manage preferences</a>" 
            show_if_false="<a href=\"{{crm.unsubscribe_url}}\">Unsubscribe</a>"]
        </p>
    </div>
</body>
</html>
';

// Example 8: Testing and debugging
function debugCustomFields($subscriber) {
    echo '<h3>Debug Information</h3>';
    echo '<p><strong>Contact ID:</strong> ' . $subscriber->id . '</p>';
    echo '<p><strong>Email:</strong> ' . $subscriber->email . '</p>';
    echo '<p><strong>Custom Fields:</strong></p>';
    echo '<pre>' . print_r($subscriber->custom_fields(), true) . '</pre>';
    
    // Test specific conditions
    $testFields = ['membership_level', 'total_purchases', 'subscription_status'];
    foreach ($testFields as $field) {
        $value = fluentcrm_get_custom_field_value($field, $subscriber);
        echo '<p><strong>' . $field . ':</strong> ' . ($value ?: 'empty') . '</p>';
    }
}

// Example 9: Hook into FluentCRM email processing
add_filter('fluent_crm/parse_campaign_email_text', function($content, $subscriber) {
    // Add custom processing here if needed
    return $content;
}, 10, 2);

// Example 10: Create a custom automation action
function customAutomationAction($subscriber, $sequence, $funnelSubscriberId, $funnelMetric) {
    // This could be a custom automation action that uses conditional logic
    $membershipLevel = fluentcrm_get_custom_field_value('membership_level', $subscriber);
    
    if (fluentcrm_check_custom_field_condition('membership_level', 'equals', 'premium', $subscriber)) {
        // Send premium member email
        $emailData = [
            'subject' => 'Premium Member Update',
            'body' => 'Here\'s your exclusive premium content...'
        ];
    } else {
        // Send standard member email
        $emailData = [
            'subject' => 'Upgrade to Premium',
            'body' => 'Upgrade now to access exclusive content...'
        ];
    }
    
    // Send the email using FluentCRM's mailer
    if (class_exists('\FluentCrm\App\Services\Mailer\Handler')) {
        \FluentCrm\App\Services\Mailer\Handler::send($emailData, $subscriber);
    }
}