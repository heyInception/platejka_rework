<?php
/** Global Contact Form 7 modal settings and rendering. */

function platejka_contact_modal_option( string $name, string $fallback = '' ): string {
	if ( ! function_exists( 'get_field' ) ) {
		return $fallback;
	}

	$value = get_field( $name, 'option' );
	if ( is_object( $value ) && isset( $value->ID ) ) {
		return (string) absint( $value->ID );
	}
	return is_scalar( $value ) && '' !== (string) $value ? (string) $value : $fallback;
}

function platejka_contact_modal_form_id( string $type, $fallback = 0 ): int {
	$field = 'calculator' === $type ? 'platejka_calculator_form' : 'platejka_consultation_form';
	$form_id = absint( platejka_contact_modal_option( $field, (string) absint( $fallback ) ) );
	if ( ! $form_id && function_exists( 'platejka_resolve_section_data' ) ) {
		$slug    = 'calculator' === $type ? 'calculator' : 'call';
		$data    = platejka_resolve_section_data( array( 'slug' => $slug, 'mode' => 'default', 'row' => array() ), (int) get_queried_object_id() );
		$form_id = absint( $data['form'] ?? 0 );
	}
	return $form_id;
}

function platejka_contact_modal_hidden_fields( string $context ): string {
	$fields = array(
		'platejka-request-source'       => $context,
		'platejka-page-title'           => wp_get_document_title(),
		'platejka-page-url'             => home_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ) ),
		'platejka-calculator-currency'  => '',
		'platejka-calculator-amount'    => '',
		'platejka-calculator-rate'      => '',
		'platejka-calculator-commission'=> '',
		'platejka-calculator-total'     => '',
		'platejka-calculator-summary'   => '',
	);
	$html = '';
	foreach ( $fields as $name => $value ) {
		$html .= sprintf( '<input type="hidden" name="%s" value="%s">', esc_attr( $name ), esc_attr( $value ) );
	}
	return $html;
}

function platejka_contact_modal_style_form_html( string $html ): string {
	$html = str_replace( 'wpcf7-text wpcf7-validates-as-required', 'wpcf7-text wpcf7-validates-as-required ui-input', $html );
	$html = str_replace( 'request__form-tel', 'request__form-tel ui-input js-phone-mask', $html );
	$html = str_replace( 'wpcf7-email', 'wpcf7-email ui-input', $html );
	$html = str_replace( 'wpcf7-textarea', 'wpcf7-textarea ui-input contact-modal__message', $html );
	$html = str_replace( 'request__form-btn', 'request__form-btn ui-button contact-modal__submit', $html );
	$html = str_replace( 'wpcf7-list-item"><label>', 'wpcf7-list-item contact-modal__consent"><label class="custom-checkbox">', $html );
	$html = str_replace( '<input type="checkbox"', '<input class="custom-checkbox__field" type="checkbox"', $html );
	$html = str_replace( 'class="wpcf7-list-item-label"', 'class="wpcf7-list-item-label custom-checkbox__content"', $html );
	return $html;
}

function platejka_contact_modal_form( int $form_id, string $context ): string {
	$html = platejka_cf7_form( $form_id );
	if ( '' === $html ) {
		return '';
	}

	$html = platejka_contact_modal_style_form_html( $html );
	return str_replace( '</form>', platejka_contact_modal_hidden_fields( $context ) . '</form>', $html );
}

function platejka_cf7_remove_field_paragraph( string $html, string $field_name ): string {
	$name = preg_quote( $field_name, '~' );
	$html = (string) preg_replace(
		'~<p\b[^>]*>(?:(?!</p>).)*\bname=(["\'])' . $name . '\1(?:(?!</p>).)*</p>~si',
		'',
		$html
	);
	return (string) preg_replace(
		'~<span\b[^>]*class=(["\'])[^"\']*wpcf7-form-control-wrap[^"\']*\1[^>]*>\s*<textarea\b(?=[^>]*\bname=(["\'])' . $name . '\2)[^>]*>.*?</textarea>\s*</span>~si',
		'',
		$html
	);
}

function platejka_register_contact_modal_settings(): void {
	if ( ! function_exists( 'acf_add_options_page' ) || ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_options_page(
		array(
			'page_title' => __( 'Формы и модальные окна', 'platejka_rework' ),
			'menu_title' => __( 'Формы и модальные окна', 'platejka_rework' ),
			'menu_slug'  => 'platejka-contact-modals',
			'capability' => 'manage_options',
			'redirect'   => false,
		)
	);

	$form_field = static function ( string $key, string $name, string $label ): array {
		return array(
			'key'           => $key,
			'label'         => $label,
			'name'          => $name,
			'type'          => 'post_object',
			'post_type'     => array( 'wpcf7_contact_form' ),
			'return_format' => 'id',
			'ui'            => 1,
		);
	};
	$text_field = static fn( string $key, string $name, string $label, string $default ): array => array(
		'key' => $key, 'label' => $label, 'name' => $name, 'type' => 'text', 'default_value' => $default,
	);

	$fields = array(
		$form_field( 'field_platejka_modal_consultation_form_v1', 'platejka_consultation_form', __( 'Форма консультации', 'platejka_rework' ) ),
		$form_field( 'field_platejka_modal_calculator_form_v1', 'platejka_calculator_form', __( 'Форма расчёта', 'platejka_rework' ) ),
		$text_field( 'field_platejka_modal_default_title_v1', 'platejka_modal_default_title', __( 'Базовый заголовок', 'platejka_rework' ), __( 'Связаться с нами', 'platejka_rework' ) ),
		$text_field( 'field_platejka_modal_default_text_v1', 'platejka_modal_default_text', __( 'Базовое описание', 'platejka_rework' ), __( 'Оставьте контакты — мы свяжемся с вами и ответим на вопросы.', 'platejka_rework' ) ),
		$text_field( 'field_platejka_modal_success_v1', 'platejka_modal_success', __( 'Сообщение об успешной отправке', 'platejka_rework' ), __( 'Спасибо, заявка отправлена — скоро свяжемся.', 'platejka_rework' ) ),
	);
	foreach ( array(
		'header'             => array( 'Связаться с нами', 'Оставьте контакты — мы ответим на ваши вопросы.' ),
		'blog'               => array( 'Обсудить задачу', 'Расскажите о задаче — команда «Платёжки» предложит решение.' ),
		'about'              => array( 'Назначить встречу', 'Оставьте контакты, чтобы согласовать удобное время.' ),
		'problems'           => array( 'Получить консультацию', 'Опишите задачу — мы поможем подобрать решение.' ),
		'compliance_contact' => array( 'Обсудить подключение', 'Оставьте контакты — расскажем о подключении и требованиях.' ),
		'compliance_test'    => array( 'Запустить тестовый платёж', 'Оставьте контакты — подготовим тестовый платёж.' ),
	) as $context => $copy ) {
		$fields[] = $text_field( 'field_platejka_modal_' . $context . '_title_v1', 'platejka_modal_' . $context . '_title', sprintf( __( '%s: заголовок', 'platejka_rework' ), $context ), $copy[0] );
		$fields[] = $text_field( 'field_platejka_modal_' . $context . '_text_v1', 'platejka_modal_' . $context . '_text', sprintf( __( '%s: описание', 'platejka_rework' ), $context ), $copy[1] );
	}

	acf_add_local_field_group( array(
		'key' => 'group_platejka_contact_modals_v1', 'title' => __( 'Формы и модальные окна', 'platejka_rework' ),
		'fields' => $fields, 'location' => array( array( array( 'param' => 'options_page', 'operator' => '==', 'value' => 'platejka-contact-modals' ) ) ),
	) );
}
add_action( 'acf/init', 'platejka_register_contact_modal_settings' );

function platejka_render_consultation_modal(): void {
	$form_id = platejka_contact_modal_form_id( 'consultation' );
	$config  = array();
	$defaults = array(
		'header'             => array( 'Связаться с нами', 'Оставьте контакты — мы ответим на ваши вопросы.' ),
		'blog'               => array( 'Обсудить задачу', 'Расскажите о задаче — команда «Платёжки» предложит решение.' ),
		'about'              => array( 'Назначить встречу', 'Оставьте контакты, чтобы согласовать удобное время.' ),
		'problems'           => array( 'Получить консультацию', 'Опишите задачу — мы поможем подобрать решение.' ),
		'compliance_contact' => array( 'Обсудить подключение', 'Оставьте контакты — расскажем о подключении и требованиях.' ),
		'compliance_test'    => array( 'Запустить тестовый платёж', 'Оставьте контакты — подготовим тестовый платёж.' ),
	);
	foreach ( $defaults as $context => $copy ) {
		$config[ $context ] = array(
			'title' => platejka_contact_modal_option( 'platejka_modal_' . $context . '_title', $copy[0] ),
			'text'  => platejka_contact_modal_option( 'platejka_modal_' . $context . '_text', $copy[1] ),
		);
	}
	$title   = platejka_contact_modal_option( 'platejka_modal_default_title', __( 'Связаться с нами', 'platejka_rework' ) );
	$text    = platejka_contact_modal_option( 'platejka_modal_default_text', __( 'Оставьте контакты — мы свяжемся с вами и ответим на вопросы.', 'platejka_rework' ) );
	$success = platejka_contact_modal_option( 'platejka_modal_success', __( 'Спасибо, заявка отправлена — скоро свяжемся.', 'platejka_rework' ) );
	?>
	<dialog class="contact-modal" data-consultation-dialog aria-labelledby="platejka-contact-modal-title">
		<div class="contact-modal__content">
			<button class="btn-reset contact-modal__close" type="button" data-contact-modal-close aria-label="<?php esc_attr_e( 'Закрыть', 'platejka_rework' ); ?>">×</button>
			<h2 class="contact-modal__title" id="platejka-contact-modal-title" data-contact-modal-title><?php echo esc_html( $title ); ?></h2>
			<p class="contact-modal__text" data-contact-modal-text><?php echo esc_html( $text ); ?></p>
			<div class="contact-modal__form" data-contact-modal-form><?php echo platejka_contact_modal_form( $form_id, 'consultation' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			<div class="contact-modal__success" data-contact-modal-success hidden><p><?php echo esc_html( $success ); ?></p></div>
		</div>
	</dialog>
	<script type="application/json" data-contact-modal-config><?php echo wp_json_encode( array( 'contexts' => $config, 'success' => $success ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></script>
	<?php
}
add_action( 'wp_footer', 'platejka_render_consultation_modal', 5 );

function platejka_contact_modal_mail_context( array $components ): array {
	if ( ! class_exists( 'WPCF7_Submission' ) ) {
		return $components;
	}
	$submission = WPCF7_Submission::get_instance();
	$data       = $submission ? $submission->get_posted_data() : array();
	$labels     = array(
		'your-name' => 'Имя', 'your-phone' => 'Телефон', 'your-email' => 'Email', 'your-message' => 'Комментарий',
		'platejka-request-source' => 'Источник', 'platejka-page-title' => 'Страница', 'platejka-page-url' => 'URL',
		'platejka-calculator-currency' => 'Валюта', 'platejka-calculator-amount' => 'Сумма', 'platejka-calculator-rate' => 'Курс',
		'platejka-calculator-commission' => 'Комиссия', 'platejka-calculator-total' => 'Итого', 'platejka-calculator-summary' => 'Расчёт',
	);
	$lines = array();
	foreach ( $labels as $key => $label ) {
		$value = isset( $data[ $key ] ) && is_scalar( $data[ $key ] ) ? sanitize_textarea_field( (string) $data[ $key ] ) : '';
		if ( '' !== $value ) {
			$lines[] = $label . ': ' . $value;
		}
	}
	if ( $lines ) {
		$components['body'] .= "\n\n---\n" . implode( "\n", $lines );
	}
	return $components;
}
add_filter( 'wpcf7_mail_components', 'platejka_contact_modal_mail_context' );
