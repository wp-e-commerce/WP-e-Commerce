<?php
/**
 * The template part for the email message for declined transactions in Amazon Payments
 * This message is for the "hard" decline, in French.
 *
 * Hard declines indicate the payment method may not be retried.
 *
 * Override this template by copying it to theme-folder/wp-e-commerce/emails/hard-decline-email-fr.php
 *
 * @author   WP eCommerce
 * @package  WP-e-Commerce/Templates/Emails
 * @version  3.9.0
 */
?>

Cher client,

Malheureusement Amazon Payments n’a pas pu traier le paiement pour votre commande auprès de <?php echo get_option( 'blogname' ); ?>. Veuillez nous contacter concernant cette commande.

Meilleures salutations,

<?php echo get_option( 'blogname' ); ?>
