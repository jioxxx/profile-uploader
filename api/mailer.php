<?php
/**
 * ProfileGen Mailer for Vercel
 * Uses a simple API-based approach for sending emails
 */

// Email configuration - set these in Vercel Dashboard > Environment Variables
define('MAIL_API_URL', getenv('MAIL_API_URL') ?: '');  // e.g., Resend, SendGrid API URL
define('MAIL_API_KEY', getenv('MAIL_API_KEY') ?: '');   // API key for email service
define('FROM_EMAIL', getenv('FROM_EMAIL') ?: 'noreply@profilegen.vercel.app');
define('FROM_NAME', getenv('FROM_NAME') ?: 'ProfileGen');

/**
 * Send an email notification
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @return bool True if email was sent successfully
 */
function send_email($to, $subject, $body)
{
    // If no API configured, log to stderr (visible in Vercel logs)
    if (empty(MAIL_API_URL)) {
        error_log("Email would be sent to: $to");
        error_log("Subject: $subject");
        // In development, just return true and log
        return true;
    }

    // Use Resend API (recommended for Vercel)
    if (strpos(MAIL_API_URL, 'resend.com') !== false) {
        return send_via_resend($to, $subject, $body);
    }

    // Generic API call
    return send_via_api($to, $subject, $body);
}

/**
 * Send email via Resend API
 */
function send_via_resend($to, $subject, $body)
{
    $data = [
        'from' => FROM_NAME . ' <' . FROM_EMAIL . '>',
        'to' => $to,
        'subject' => $subject,
        'html' => $body
    ];

    $ch = curl_init(MAIL_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . MAIL_API_KEY,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode >= 200 && $httpCode < 300;
}

/**
 * Send email via generic API
 */
function send_via_api($to, $subject, $body)
{
    $data = [
        'to' => $to,
        'subject' => $subject,
        'body' => $body,
        'from' => FROM_NAME . ' <' . FROM_EMAIL . '>'
    ];

    $ch = curl_init(MAIL_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . MAIL_API_KEY,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return !empty($response);
}

/**
 * Send profile creation notification to the user
 * 
 * @param array $profile Profile data array
 */
function notify_profile_created($profile)
{
    $to = $profile['email'];
    $subject = "✅ Your Profile on ProfileGen Has Been Created!";

    $body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Created</title>
</head>
<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #2d6a4f 0%, #40916c 100%); padding: 30px; border-radius: 10px 10px 0 0;">
        <h1 style="color: #fff; margin: 0; font-size: 24px;">🎉 Profile Created Successfully!</h1>
    </div>
    
    <div style="background: #faf8f3; padding: 30px; border: 1px solid #e8e4da; border-top: none; border-radius: 0 0 10px 10px;">
        <p>Hi <strong>{$profile['name']}</strong>,</p>
        
        <p>Great news! Your profile has been successfully created on <strong>ProfileGen</strong>.</p>
        
        <div style="background: #fff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2d6a4f;">
            <h3 style="margin-top: 0; color: #2d6a4f;">Your Profile Details</h3>
            <p><strong>Username:</strong> @{$profile['username']}</p>
            <p><strong>Name:</strong> {$profile['name']}</p>
            <p><strong>Email:</strong> {$profile['email']}</p>
        </div>
        
        <p>You can view your profile by visiting our website.</p>
        
        <hr style="border: none; border-top: 1px solid #e8e4da; margin: 30px 0;">
        
        <p style="color: #888; font-size: 14px; margin: 0;">
            If you did not create this profile, please contact us immediately.
        </p>
        
        <p style="color: #888; font-size: 14px; margin: 10px 0 0 0;">
            — The ProfileGen Team
        </p>
    </div>
</body>
</html>
HTML;

    return send_email($to, $subject, $body);
}

/**
 * Send profile verification notification
 * 
 * @param array $profile Profile data array
 * @param string $token Verification token
 */
function notify_profile_verification($profile, $token)
{
    $to = $profile['email'];
    $subject = "Action Required: Verify Your ProfileGen Identity";

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    if (getenv('VERCEL') === '1') {
        $protocol = 'https';
    }

    $verify_link = "$protocol://$host/verify.php?token=$token";

    $body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Identity</title>
</head>
<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #d4a017 0%, #a37c15 100%); padding: 30px; border-radius: 10px 10px 0 0;">
        <h1 style="color: #fff; margin: 0; font-size: 24px;">Action Required: Verify Identity</h1>
    </div>
    
    <div style="background: #faf8f3; padding: 30px; border: 1px solid #e8e4da; border-top: none; border-radius: 0 0 10px 10px;">
        <p>Hi <strong>{$profile['name']}</strong>,</p>
        
        <p>You recently registered a profile on <strong>ProfileGen</strong>. Please tap the button below to verify your identity.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{$verify_link}" style="display: inline-block; background: #2d6a4f; color: #fff; text-decoration: none; padding: 12px 25px; border-radius: 7px; font-weight: bold;">Verify My Profile</a>
        </div>
        
        <hr style="border: none; border-top: 1px solid #e8e4da; margin: 30px 0;">
        
        <p style="color: #888; font-size: 14px; margin: 0;">If you didn't create a profile, you can safely ignore this email.</p>
    </div>
</body>
</html>
HTML;

    return send_email($to, $subject, $body);
}

/**
 * Send profile visit notification to the profile owner
 * 
 * @param array $profile Profile data array
 * @param string $visitor_ip Visitor's IP address (optional)
 * @param string $visitor_url URL they came from (optional)
 */
function notify_profile_visited($profile, $visitor_ip = 'Unknown', $visitor_url = '')
{
    $to = $profile['email'];
    $subject = "👁 Someone Just Visited Your Profile on ProfileGen!";

    $visit_time = date('F j, Y \a\t g:i A');

    $body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Visit Notification</title>
</head>
<body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #2d6a4f 0%, #40916c 100%); padding: 30px; border-radius: 10px 10px 0 0;">
        <h1 style="color: #fff; margin: 0; font-size: 24px;">👁 Profile Visit Alert</h1>
    </div>
    
    <div style="background: #faf8f3; padding: 30px; border: 1px solid #e8e4da; border-top: none; border-radius: 0 0 10px 10px;">
        <p>Hi <strong>{$profile['name']}</strong>,</p>
        
        <p>Good news! Someone just visited your profile on <strong>ProfileGen</strong>.</p>
        
        <div style="background: #fff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #d4a017;">
            <h3 style="margin-top: 0; color: #2d6a4f;">Visit Details</h3>
            <p><strong>Time:</strong> {$visit_time}</p>
            <p><strong>Your Profile:</strong> @{$profile['username']}</p>
HTML;

    if ($visitor_ip !== 'Unknown' && $visitor_ip !== '') {
        $body .= "            <p><strong>Visitor IP:</strong> {$visitor_ip}</p>\n";
    }

    if ($visitor_url !== '') {
        $body .= "            <p><strong>Referrer:</strong> {$visitor_url}</p>\n";
    }

    $body .= <<<HTML
        </div>
        
        <hr style="border: none; border-top: 1px solid #e8e4da; margin: 30px 0;">
        
        <p style="color: #888; font-size: 14px; margin: 0;">
            <em>Note: You'll receive an email notification each time someone visits your profile.</em>
        </p>
        
        <p style="color: #888; font-size: 14px; margin: 10px 0 0 0;">
            — The ProfileGen Team
        </p>
    </div>
</body>
</html>
HTML;

    return send_email($to, $subject, $body);
}
