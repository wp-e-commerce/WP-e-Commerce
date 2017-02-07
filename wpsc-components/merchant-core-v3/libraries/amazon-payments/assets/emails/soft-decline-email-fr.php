<?php
/**
 * The template part for the email message for declined transactions in Amazon Payments
 * This message is for the "soft" decline, in French.
 *
 * Soft declines indicate the payment method may be retried.
 *
 * Override this template by copying it to theme-folder/wp-e-commerce/emails/soft-decline-email-fr.php
 *
 * @author   WP eCommerce
 * @package  WP-e-Commerce/Templates/Emails
 * @version  3.9.0
 */
?>

Cher client,

Merci pour votre commande auprès de <?php echo get_option( 'blogname' ); ?>.

Malheureusement Amazon Payments n’a pas pu traiter le paiement.

Veuillez aller sur <?php echo esc_url( $url ); ?> et mettez à jour les informations de paiement pour votre commande. Dans la suite une nouvelle demande de paiement va être  demandé à Amazon Payments et vous allez recevoir un email de confirmation.

Meilleures salutations,

<?php echo get_option( 'blogname' ); ?>
