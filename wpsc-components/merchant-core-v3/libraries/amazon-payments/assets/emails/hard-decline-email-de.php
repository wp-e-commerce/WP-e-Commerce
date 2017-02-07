<?php
/**
 * The template part for the email message for declined transactions in Amazon Payments
 * This message is for the "hard" decline, in German.
 *
 * Hard declines indicate the payment method may not be retried.
 *
 * Override this template by copying it to theme-folder/wp-e-commerce/emails/hard-decline-email-de.php
 *
 * @author   WP eCommerce
 * @package  WP-e-Commerce/Templates/Emails
 * @version  3.9.0
 */
?>

Sehr geehrter Kunde,

Leider wurde die Zahlung zu Ihrer Bestellung in unserem Onlineshop <?php echo get_option( 'blogname' ); ?>.

von Amazon Payments zurückgewiesen. Bitte kontaktieren Sie uns.

Mit freundlichen Grüßen

<?php echo get_option( 'blogname' ); ?>
