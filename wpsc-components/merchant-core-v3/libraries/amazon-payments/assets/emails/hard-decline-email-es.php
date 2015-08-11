<?php
/**
 * The template part for the email message for declined transactions in Amazon Payments
 * This message is for the "hard" decline, in Spanish.
 *
 * Hard declines indicate the payment method may not be retried.
 *
 * Override this template by copying it to theme-folder/wp-e-commerce/emails/hard-decline-email-es.php
 *
 * @author   WP eCommerce
 * @package  WP-e-Commerce/Templates/Emails
 * @version  4.0
 */
?>

Saludos,

Desgraciadamente, Amazon Payments ha rechazado el pago de tu pedido en nuestra tienda online <?php echo get_option( 'blogname' ); ?>. Por favor, ponte en contacto con nosotros.

Gracias por elegir Pagar con Amazon,

<?php echo get_option( 'blogname' ); ?>