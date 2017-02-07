<?php
/**
 * The template part for the email message for declined transactions in Amazon Payments
 * This message is for the "hard" decline, in Italian.
 *
 * Hard declines indicate the payment method may not be retried.
 *
 * Override this template by copying it to theme-folder/wp-e-commerce/emails/hard-decline-email-it.php
 *
 * @author   WP eCommerce
 * @package  WP-e-Commerce/Templates/Emails
 * @version  3.9.0
 */
?>

Gentile Cliente,

Purtroppo Amazon Payments ha rifiutato il pagamento del suo ordine nel nostro negozio on-line <?php echo get_option( 'blogname' ); ?>.

Non esiti a contattarci per ulteriori informazioni a riguardo.

Cordiali saluti,

<?php echo get_option( 'blogname' ); ?>
