<?php
/**
 * The template part for the email message for declined transactions in Amazon Payments
 * This message is for the "soft" decline, in Italian.
 *
 * Soft declines indicate the payment method may be retried.
 *
 * Override this template by copying it to theme-folder/wp-e-commerce/emails/soft-decline-email-it.php
 *
 * @author   WP eCommerce
 * @package  WP-e-Commerce/Templates/Emails
 * @version  3.9.0
 */
?>

Gentile Cliente,

La ringraziamo molto per il suo ordine a <?php echo get_option( 'blogname' ); ?>.

Purtroppo Amazon Payments ha rifiutato il suo pagamento.

La preghiamo di andare all'indirizzo <?php echo esc_url( $url ); ?> e aggiornare le informazioni di pagamento per il suo ordine. Automaticamente le verrà nuovamente richiesto da Amazon Payments di procedere col pagamento.

Successivamente riceverà una mail di conferma dell'avvenuto pagamento.

Cordiali saluti,

<?php echo get_option( 'blogname' ); ?>
