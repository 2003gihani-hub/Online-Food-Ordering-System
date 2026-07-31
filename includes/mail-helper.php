<?php
// Prevent direct access to this helper file
if (count(get_included_files()) === 1) {
    exit("Direct access not permitted.");
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once dirname(__DIR__) . '/config/mail.php';

/**
 * Send an email notifying the customer of an order status update.
 *
 * @param string $customerName Name of the customer
 * @param string $customerEmail Email address of the customer
 * @param string $orderId Formatted Order ID
 * @param string $orderStatus New status of the order
 * @return bool True if sent successfully, False otherwise
 */
function sendOrderStatusEmail($customerName, $customerEmail, $orderId, $orderStatus) {
    $mail = new PHPMailer(true);

    try {
        // SMTP Server configuration
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;

        // Bypass SSL certificate validation if enabled (for local XAMPP environments)
        if (defined('SMTP_ALLOW_SELF_SIGNED') && SMTP_ALLOW_SELF_SIGNED) {
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
        }

        // Set Charset
        $mail->CharSet = 'UTF-8';

        // Sender and Recipient
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($customerEmail, $customerName);

        // Content
        $mail->isHTML(false); // Using plain text as specified
        $mail->Subject = 'FoodExpress - Order Status Update';

        // Email Body Template
        $body = "Hello " . $customerName . ",\n\n";
        $body .= "Your order status has been updated.\n\n";
        $body .= "Order ID: " . $orderId . "\n\n";
        $body .= "New Status: " . $orderStatus . "\n\n";
        $body .= "Thank you for choosing FoodExpress.\n\n";
        $body .= "Best Regards,\n";
        $body .= "FoodExpress Team";

        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log the error in PHP error logs without stopping application execution
        error_log("FoodExpress PHPMailer Error for Order ID " . $orderId . ": " . $mail->ErrorInfo);
        return false;
    } catch (\Throwable $t) {
        // Catch any other potential exceptions/errors to keep system resilient
        error_log("FoodExpress Mail Helper Error for Order ID " . $orderId . ": " . $t->getMessage());
        return false;
    }
}
