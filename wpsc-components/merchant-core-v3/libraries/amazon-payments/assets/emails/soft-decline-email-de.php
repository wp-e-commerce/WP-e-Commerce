<?php
/**
 * The template part for the email message for declined transactions in Amazon Payments
 * This message is for the "soft" decline, in German.
 *
 * Soft declines indicate the payment method may be retried.
 *
 * Override this template by copying it to theme-folder/wp-e-commerce/emails/soft-decline-email-de.php
 *
 * @author   WP eCommerce
 * @package  WP-e-Commerce/Templates/Emails
 * @version  3.9.0
 */
?>

Sehr geehrter Kunde,

Vielen Dank für Ihre Bestellung bei <?php echo get_option( 'blogname' ); ?>.

Leider wurde Ihre Bezahlung von Amazon Payments abgelehnt.

Sie können unter <?php echo esc_url( $url ); ?> die Zahlungsinformationen für Ihre Bestellung aktualisieren, indem Sie eine andere Zahlungsweise auswählen oder eine neue Zahlungsweise angeben. Mit der neuen Zahlungsweise wird dann ein erneuter Zahlungsversuch vorgenommen, und Sie erhalten eine Bestätigungsemail.

Mit freundlichen Grüßen

<?php echo get_option( 'blogname' ); ?>
