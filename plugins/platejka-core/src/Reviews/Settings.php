<?php

namespace Platejka\Core\Reviews;

defined( 'ABSPATH' ) || exit;

/** Global settings for imported customer reviews. */
final class Settings {
	public const OPTION = 'platejka_review_publication_mode';

	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'addPage' ) );
		add_action( 'admin_init', array( self::class, 'registerSetting' ) );
	}

	public static function normalizeMode( $mode ): string {
		return 'automatic' === $mode ? 'automatic' : 'moderated';
	}

	public static function getMode(): string {
		return self::normalizeMode( get_option( self::OPTION, 'moderated' ) );
	}

	public static function addPage(): void {
		add_options_page(
			__( 'Настройки отзывов', 'platejka-core' ),
			__( 'Отзывы', 'platejka-core' ),
			'manage_options',
			'platejka-review-settings',
			array( self::class, 'renderPage' )
		);
	}

	public static function registerSetting(): void {
		register_setting(
			'platejka_review_settings',
			self::OPTION,
			array(
				'type'              => 'string',
				'default'           => 'moderated',
				'sanitize_callback' => array( self::class, 'normalizeMode' ),
			)
		);
	}

	public static function renderPage(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Настройки отзывов', 'platejka-core' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'platejka_review_settings' ); ?>
				<table class="form-table" role="presentation"><tbody><tr>
					<th scope="row"><?php esc_html_e( 'Публикация новых отзывов', 'platejka-core' ); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php esc_html_e( 'Публикация новых отзывов', 'platejka-core' ); ?></legend>
							<label><input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>" value="moderated" <?php checked( self::getMode(), 'moderated' ); ?>> <?php esc_html_e( 'С проверкой редактором', 'platejka-core' ); ?></label><br>
							<label><input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>" value="automatic" <?php checked( self::getMode(), 'automatic' ); ?>> <?php esc_html_e( 'Автоматическая публикация', 'platejka-core' ); ?></label>
							<p class="description"><?php esc_html_e( 'Этот режим будет применяться к новым отзывам при подключении импорта. Существующие отзывы он не меняет.', 'platejka-core' ); ?></p>
						</fieldset>
					</td>
				</tr></tbody></table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
