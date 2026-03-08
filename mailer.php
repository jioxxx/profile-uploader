<?php
/**
 * ProfileGen Mailer
 * SMTP-based mailer using PHP sockets (no external dependencies)
 */

// SMTP Configuration - Update these in db.php or set directly here
function get_smtp_config() {
    return [
        'host' => defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com',
        'port' => defined('SMTP_PORT') ? SMTP_PORT : 587,
        'username' => defined('SMTP_USERNAME') ? SMTP_USERNAME : '',
        'password' => defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '',
        'from_email' => defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'noreply@profilegen.local',
        'from_name' => defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'ProfileGen',
    ];
}

/**
 * Connect to SMTP server and send email
 */
function smtp_send($to, $subject, $body, $config) {
    $host = $config['host'];
    $port = $config['port'];
    $username = $config['username'];
    $password = $config['password'];
    $from_email = $config['from_email'];
    $from_name = $config['from_name'];
    
    $timeout = 30;
    $fp = @fsockopen(
        ($port == 465 ? "ssl://" : "") . $host,
        $port,
        $errno,
        $errstr,
        $timeout
    );
    
    if (!$fp) {
        error_log("SMTP Connection failed: $errstr ($errno)");
        return false;
    }
    
    // Read welcome message
    $response = fgets($fp, 515);
    
    // EHLO
    fputs($fp, "EHLO " . gethostname() . "\r\n");
    $response = "";
    while ($line = fgets($fp, 515)) {
        $response .= $line;
        if (substr($line, 3, 1) == " ") break;
    }
    
    // STARTTLS if needed
    if ($port == 587) {
        fputs($fp, "STARTTLS\r\n");
        $response = fgets($fp, 515);
        
        // Upgrade to TLS
        stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        
        // Re-EHLO after TLS
        fputs($fp, "EHLO " . gethostname() . "\r\n");
        $response = "";
        while ($line = fgets($fp, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == " ") break;
        }
    }
    
    // AUTH LOGIN
    fputs($fp, "AUTH LOGIN\r\n");
    $response = fgets($fp, 515);
    
    fputs($fp, base64_encode($username) . "\r\n");
    $response = fgets($fp, 515);
    
    fputs($fp, base64_encode($password) . "\r\n");
    $response = fgets($fp, 515);
    
    if (substr($response, 0, 3) != "235") {
        error_log("SMTP Auth failed: " . $response);
        fclose($fp);
        return false;
    }
    
    // MAIL FROM
    fputs($fp, "MAIL FROM:<{$from_email}>\r\n");
    $response = fgets($fp, 515);
    
    // RCPT TO
    fputs($fp, "RCPT TO:<{$to}>\r\n");
    $response = fgets($fp, 515);
    
    // DATA
    fputs($fp, "DATA\r\n");
    $response = fgets($fp, 515);
    
    // Email headers and body
    $message = "From: {$from_name} <{$from_email}>\r\n";
    $message .= "To: {$to}\r\n";
    $message .= "Subject: {$subject}\r\n";
    $message .= "MIME-Version: 1.0\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message .= "Date: " . date("r") . "\r\n";
    $message .= "\r\n";
    $message .= $body;
    $message .= "\r\n.\r\n";
    
    fputs($fp, $message);
    $response = fgets($fp, 515);
    
    // QUIT
    fputs($fp, "QUIT\r\n");
    fclose($fp);
    
    return (substr($response, 0, 3) == "250");
}

/**
 * Send an email notification
 */
function send_email($to, $subject, $body) {
    $config = get_smtp_config();
    
    // If no SMTP credentials configured, log and return false
    if (empty($config['username']) || empty($config['password'])) {
        error_log("SMTP not configured. Email to {$to} not sent.");
        return false;
    }
    
    return smtp_send($to, $subject, $body, $config);
}

/**
 * Send profile creation notification to the user
 */
function notify_profile_created($profile) {
    $to = $profile['email'];
    $subject = "Your Profile on ProfileGen Has Been Created!";
    
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
        <h1 style="color: #fff; margin: 0; font-size: 24px;">Profile Created Successfully!</h1>
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
        
        <p>You can view your profile here:</p>
        <p>
            <a href="https://profilegen.local/profile.php?u={$profile['username']}" 
               style="display: inline-block; background: #2d6a4f; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">
               View Your Profile
            </a>
        </p>
        
        <hr style="border: none; border-top: 1px solid #e8e4da; margin: 30px 0;">
        
        <p style="color: #888; font-size: 14px; margin: 0;">
            If you did not create this profile, please contact us immediately.
        </p>
        
        <p style="color: #888; font-size: 14px; margin: 10px 0 0 0;">
            The ProfileGen Team
        </p>
    </div>
</body>
</html>
HTML;
    
    return send_email($to, $subject, $body);
}

/**
 * Send profile visit notification to the profile owner
 */
function notify_profile_visited($profile, $visitor_ip = 'Unknown', $visitor_url = '') {
    $to = $profile['email'];
    $subject = "Someone Just Visited Your Profile on ProfileGen!";
    
    $visit_time = date('F j, Y \a\t g:i A');
    $profile_url = "https://profilegen.local/profile.php?u={$profile['username']}";
    
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
        <h1 style="color: #fff; margin: 0; font-size: 24px;">Profile Visit Alert</h1>
    </div>
    
    <div style="background: #faf8f3; padding: 30px; border: 1px solid #e8e4da; border-top: none; border-radius: 0 0 10px 10px;">
        <p>Hi <strong>{$profile['name']}</strong>,</p>
        
        <p>Someone just visited your profile on <strong>ProfileGen</strong>.</p>
        
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
        
        <p>Want to see who's viewing your profile? Keep creating great content to attract more visitors!</p>
        
        <p>
            <a href="{$profile_url}" 
               style="display: inline-block; background: #2d6a4f; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">
               View Your Profile
            </a>
        </p>
        
        <hr style="border: none; border-top: 1px solid #e8e4da; margin: 30px 0;">
        
        <p style="color: #888; font-size: 14px; margin: 0;">
            <em>Note: You'll receive an email notification each time someone visits your profile.</em>
        </p>
        
        <p style="color: #888; font-size: 14px; margin: 10px 0 0 0;">
            The ProfileGen Team
        </p>
    </div>
</body>
</html>
HTML;
    
    return send_email($to, $subject, $body);
}
