<?php
/**
 * The template part for the email message for declined transactions in Amazon Payments
 * This message is for the "soft" decline, in Spanish.
 *
 * Soft declines indicate the payment method may be retried.
 *
 * Override this template by copying it to theme-folder/wp-e-commerce/emails/soft-decline-email-es.php
 *
 * @author   WP eCommerce
 * @package  WP-e-Commerce/Templates/Emails
 * @version  3.9.0
 */
?>

Saludos,

Muchas gracias por tu pedido en <?php echo get_option( 'blogname' ); ?>.

Desgraciadamente, Amazon Payments ha rechazado tu pago.

Por favor, dirígete <?php echo esc_url( $url ); ?> y revisa la información de pago de tu pedido. Una vez hayas actualizado tu información de pago, solicitaremos automáticamente un nuevo pago en Amazon Payments y recibirás un correo electrónico de confirmación.

Gracias por elegir Pagar con Amazon,

<?php echo get_option( 'blogname' ); ?>
