<?php

/**
 * Изменяет хлебные крошки Yoast.
 *
 * Вывести в шаблоне: do_action('pretty_breadcrumb');
 * или <?php do_action('pretty_breadcrumb'); ?>
 */
class Pretty_Breadcrumb
{

	/**
	 * Какую позицию занимает элемент в цепочке хлебных крошек.
	 *
	 * @var int
	 */
	private $el_position = 0;

	public function __construct()
	{
		add_action('pretty_breadcrumb', [$this, 'render']);
	}

	/**
	 * Выводит на экран сгенерированные крошки.
	 *
	 * @return void
	 */
	public function render()
	{
		if (! function_exists('yoast_breadcrumb')) {
			return;
		}

		// Регистрируем фильтры для изменения дефолтной вёрстки крошек
		add_filter('wpseo_breadcrumb_single_link', [$this, 'modify_yoast_items'], 10, 2);
		add_filter('wpseo_breadcrumb_output', [$this, 'modify_yoast_output']);
		add_filter('wpseo_breadcrumb_output_wrapper', [$this, 'modify_yoast_wrapper']);
		add_filter('wpseo_breadcrumb_separator', '__return_empty_string');

		// Выводим крошки на экран
		yoast_breadcrumb();

		// Отключаем фильтры
		remove_filter('wpseo_breadcrumb_single_link', [$this, 'modify_yoast_items']);
		remove_filter('wpseo_breadcrumb_output', [$this, 'modify_yoast_output']);
		remove_filter('wpseo_breadcrumb_output_wrapper', [$this, 'modify_yoast_wrapper']);
		remove_filter('wpseo_breadcrumb_separator', '__return_empty_string');

		// Обнуляем счётчик
		$this->el_position = 0;
	}

	/**
	 * Изменяет html код li элементов.
	 *
	 * @param string $link_html Дефолтная вёрстка элемента хлебных крошек.
	 * @param array  $link_data Массив данных об элементе хлебных крошек.
	 *
	 * @return string
	 */
	function modify_yoast_items($link_html, $link_data)
	{
		// Проверяем, является ли элемент последним
		$is_last = strpos($link_html, 'breadcrumb_last') !== false;

		// Если это последний элемент И мы на странице тега
		if ($is_last && is_tag()) {
			$current_lang = get_locale();
			$tag_name = single_tag_title('', false); // Получаем имя текущего тега

			// Текст для разных языков
			$texts = [
				'ru_RU' => 'Статьи по тегу «%s»',
				'en_US' => 'Articles by tag «%s»',
				'es_ES' => 'Artículos por etiqueta «%s»',
			];

			// Если язык не найден, используем просто имя тега
			$text = isset($texts[$current_lang])
				? sprintf($texts[$current_lang], $tag_name)
				: $tag_name;

			// Возвращаем последний элемент для страницы тега
			return sprintf(
				'<li class="breadcrumbs__item breadcrumbs__item_end" aria-current="page"><span>%s</span></li>',
				$text
			);
		}

		// Обычный элемент (не последний)
		if (! $is_last) {
			$this->el_position++;
			return sprintf(
				'<li class="breadcrumbs__item breadcrumbs__item_begin" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
					<a itemprop="item" href="%s">
						<span itemprop="name">%s</span>
					</a>
					<meta itemprop="position" content="%d"/>
				</li>',
				$link_data['url'],
				$link_data['text'],
				$this->el_position
			);
		}

		// Последний элемент (не тег)
		return sprintf(
			'<li class="breadcrumbs__item breadcrumbs__item_end" aria-current="page" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
			<span itemprop="name">%s</span>
			<meta itemprop="position" content="%d"/>
			</li>',
			$link_data['text'],
			++$this->el_position
		);
	}

	/**
	 * Возвращает псевдо wrapper, который в будущем будет вырезан из вёрстки.
	 * Если этого не сделать, то будущие li будут обёртнуты в единый span Yoast'ом.
	 *
	 * @return string
	 */
	function modify_yoast_wrapper()
	{
		return 'wrapper';
	}

	/**
	 * Изменяет дефолтный html код крошек Yoast.
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	function modify_yoast_output($html)
	{
		// Убираем псевдо wrapper
		$html = str_replace(['<wrapper>', '</wrapper>'], '', $html);

		// Формируем контейнер для li элементов
		$ul = '<ul class="breadcrumbs__items" itemscope itemtype="http://schema.org/BreadcrumbList">%s</ul>';

		// Вставляем в контейнер li элементы
		$html = sprintf($ul, $html);

		return $html;
	}
}

new Pretty_Breadcrumb();

function pretty_shortcode($atts, $content = null)
{
	ob_start();
	do_action('pretty_breadcrumb');
	return ob_get_clean();
}
add_shortcode('pretty_breadcrumb', 'pretty_shortcode');
