<?php
/** Install the dedicated CF7 document request once per site. */

function platejka_install_work_form(): void {
	if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
		return;
	}
	$id = absint( get_option( 'platejka_work_form_id', 0 ) );
	if ( $id && WPCF7_ContactForm::get_instance( $id ) ) {
		return;
	}
	$source_id = platejka_contact_modal_form_id( 'consultation' );
	$source = $source_id ? WPCF7_ContactForm::get_instance( $source_id ) : null;
	$source_mail = $source ? $source->prop( 'mail' ) : array();
	$recipient = sanitize_email( $source_mail['recipient'] ?? get_option( 'admin_email' ) );
	if ( ! $recipient ) {
		return;
	}

	$form = WPCF7_ContactForm::get_template( array( 'title' => 'Документы для заключения договора' ) );
	$form->set_properties( array(
		'form' => '<label>Ваше имя[text* your-name placeholder "Ваше имя"]</label>' . "\n"
			. '<label>Телефон[tel* your-phone placeholder "Телефон"]</label>' . "\n"
			. '<label>E-mail[email* your-email placeholder "E-mail"]</label>' . "\n"
			. '<label>Сумма инвойса[number* invoice-amount min:0.01 placeholder "Сумма"]</label>' . "\n"
			. '<label>Валюта[text* invoice-currency placeholder "Валюта"]</label>' . "\n"
			. '<label>Реквизиты получателя[textarea* invoice-recipient placeholder "Реквизиты получателя"]</label>' . "\n"
			. '<label>Назначение платежа[textarea* invoice-purpose placeholder "Назначение платежа"]</label>' . "\n"
			. '<label class="contact-modal__file-label">Документы для заключения договора[file* invoice-documents filetypes:pdf|docx limit:10mb]</label>' . "\n"
			. '[submit class:request__form-btn "Отправить заявку"]' . "\n"
			. '[acceptance consent] Принимаю условия политики конфиденциальности и даю согласие на обработку персональных данных. [/acceptance]',
		'mail' => array_merge( (array) $source_mail, array(
			'active' => true,
			'subject' => 'Документы для заключения договора',
			'recipient' => $recipient,
			'body' => "Имя: [your-name]\nТелефон: [your-phone]\nE-mail: [your-email]\nСумма: [invoice-amount]\nВалюта: [invoice-currency]\nРеквизиты получателя: [invoice-recipient]\nНазначение платежа: [invoice-purpose]\nСтраница: [platejka-page-url]",
			'attachments' => '[invoice-documents]',
		) ),
	) );
	$id = $form->save();
	if ( $id ) {
		update_option( 'platejka_work_form_id', $id, false );
	}
}
add_action( 'init', 'platejka_install_work_form', 30 );

function platejka_render_work_modal(): void {
	$id = platejka_contact_modal_form_id( 'work' );
	if ( ! $id ) {
		return;
	}
	?>
	<dialog class="contact-modal contact-modal--work" data-work-dialog aria-labelledby="platejka-work-modal-title">
		<div class="contact-modal__content">
			<button class="btn-reset contact-modal__close" type="button" data-contact-modal-close aria-label="<?php esc_attr_e( 'Закрыть', 'platejka_rework' ); ?>">×</button>
			<h2 class="contact-modal__title" id="platejka-work-modal-title">Документы для заключения договора</h2>
			<p class="contact-modal__text">Укажите данные из инвойса и прикрепите PDF или DOCX.</p>
			<div class="contact-modal__form" data-contact-modal-form><?php echo platejka_contact_modal_form( $id, 'work' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			<div class="contact-modal__success" data-contact-modal-success hidden><p><?php echo esc_html( platejka_contact_modal_option( 'platejka_modal_success', 'Спасибо, заявка отправлена — скоро свяжемся.' ) ); ?></p></div>
		</div>
	</dialog>
	<?php
}
add_action( 'wp_footer', 'platejka_render_work_modal', 6 );
