<?php

/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package platejka_rework
 */
?>

<!doctype html>
<html <?php language_attributes(); ?> class="page">

<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<link rel="icon" href="https://platejka.com/favicon.ico" type="image/x-icon">
	<link rel="icon" href="https://platejka.com/favicon.svg" type="image/svg+xml">
	<meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
	<meta name="google-site-verification" content="l9_55mtjWXH5la5S3iZk4CsVWHDq4k_ly9UDYUaGFI0" />
	<?php wp_head(); ?>
	<!-- <script src="//code.jivo.ru/widget/oZ6zFKOOCS" async></script> -->
	<!-- Top.Mail.Ru counter -->
	<script type="text/javascript">
		var _tmr = window._tmr || (window._tmr = []);
		_tmr.push({
			id: "3554245",
			type: "pageView",
			start: (new Date()).getTime()
		});
	</script>
	<!-- /Top.Mail.Ru counter -->
	<!-- Top100 (Kraken) Counter -->
	<script>
		(function(w, c) {
			(w[c] = w[c] || []).push(function() {
				var options = {
					project: 7731957,
				};
				try {
					w.top100Counter = new top100(options);
				} catch (e) {}
			});
		})(window, "_top100q");
	</script>
	<!-- END Top100 (Kraken) Counter -->
	<!-- Marquiz script start -->
	<script>
		window.platejkaMarquizOptions = {
			host: '//quiz.marquiz.ru',
			region: 'ru',
			id: '689b95fd327d1700199c7e16',
			autoOpen: 10,
			autoOpenFreq: 'once',
			openOnExit: false,
			disableOnMobile: false
		};
	</script>
	<!-- Marquiz script end → -->
	<meta name="yandex-verification" content="fe26ea8b030ef2c2" />
	<!-- Yandex.Metrika counter -->
	<script type="text/javascript">
		window.ym = window.ym || function() {
			(window.ym.a = window.ym.a || []).push(arguments);
		};
		window.ym.l = 1 * new Date();

		ym(97235179, "init", {
			clickmap: true,
			trackLinks: true,
			accurateTrackBounce: true,
			webvisor: true
		});
	</script>
	<!-- /Yandex.Metrika counter -->
	<!-- Google tag (gtag.js) -->
	<script>
		window.dataLayer = window.dataLayer || [];

		function gtag() {
			dataLayer.push(arguments);
		}
		gtag('js', new Date());

		gtag('config', 'G-765QHYK81H');
	</script>
	<script>
		window.platejkaYourGoodId = '2d1a307b-05ec-4aec-b06e-76e872366ef5';
	</script>
	<script src="//cdn.callibri.ru/callibri.js" type="text/javascript" charset="utf-8" defer></script>
	<script src="https://www.artfut.com/static/tagtag.min.js?campaign_code=af79c4ac45" async
		onerror='var self = this;window.ADMITAD=window.ADMITAD||{},ADMITAD.Helpers=ADMITAD.Helpers||{},ADMITAD.Helpers.generateDomains=function(){for(var e=new Date,n=Math.floor(new Date(2020,e.getMonth(),e.getDate()).setUTCHours(0,0,0,0)/1e3),t=parseInt(1e12*(Math.sin(n)+1)).toString(30),i=["de"],o=[],a=0;a<i.length;++a)o.push({domain:t+"."+i[a],name:t});return o},ADMITAD.Helpers.findTodaysDomain=function(e){function n(){var o=new XMLHttpRequest,a=i[t].domain,D="https://"+a+"/";o.open("HEAD",D,!0),o.onload=function(){setTimeout(e,0,i[t])},o.onerror=function(){++t<i.length?setTimeout(n,0):setTimeout(e,0,void 0)},o.send()}var t=0,i=ADMITAD.Helpers.generateDomains();n()},window.ADMITAD=window.ADMITAD||{},ADMITAD.Helpers.findTodaysDomain(function(e){if(window.ADMITAD.dynamic=e,window.ADMITAD.dynamic){var n=function(){return function(){return self.src?self:""}}(),t=n(),i=(/campaign_code=([^&]+)/.exec(t.src)||[])[1]||"";t.parentNode.removeChild(t);var o=document.getElementsByTagName("head")[0],a=document.createElement("script");a.src="https://www."+window.ADMITAD.dynamic.domain+"/static/"+window.ADMITAD.dynamic.name.slice(1)+window.ADMITAD.dynamic.name.slice(0,1)+".min.js?campaign_code="+i,o.appendChild(a)}});'></script>
	<script type="text/javascript">
		// name of the cookie that stores the source
		// change if you have another name
		var cookie_name = 'deduplication_cookie';
		// cookie lifetime
		var days_to_store = 90;
		// expected deduplication_cookie value for Admitad
		var deduplication_cookie_value = 'admitad';
		// name of GET parameter for deduplication
		// change if you have another name
		var channel_name = 'utm_source';
		// a function to get the source from the GET parameter
		getSourceParamFromUri = function() {
			var pattern = channel_name + '=([^&]+)';
			var re = new RegExp(pattern);
			return (re.exec(document.location.search) || [])[1] || '';
		};
		// a function to get the source from the cookie named cookie_name
		getSourceCookie = function() {
			var matches = document.cookie.match(new RegExp(
				'(?:^|; )' + cookie_name.replace(/([\.$?*|{}\(\)\[\]\\/\+^])/g, '\$1') + '=([^;]*)'
			));
			return matches ? decodeURIComponent(matches[1]) : undefined;
		};
		// a function to set the source in the cookie named cookie_name
		setSourceCookie = function() {
			var param = getSourceParamFromUri();
			var params = (new URL(document.location)).searchParams;
			if (!params.get(channel_name) && params.get('gclid')) {
				param = 'advAutoMarkup'
			} else if (!params.get(channel_name) && params.get('fbclid')) {
				param = 'facebook'
			} else if (!param) {
				return;
			}
			var period = days_to_store * 60 * 60 * 24 * 1000; // in seconds
			var expiresDate = new Date((period) + +new Date);
			var cookieString = cookie_name + '=' + param + '; path=/; expires=' + expiresDate.toGMTString();
			document.cookie = cookieString;
			document.cookie = cookieString + '; domain=.' + location.host;
		};
		// set cookie
		setSourceCookie();
	</script>
	<?php if (is_page(array(1844))) : ?>
		<script type="text/javascript">
			ADMITAD = window.ADMITAD || {};
			ADMITAD.Invoice = ADMITAD.Invoice || {};

			// define a channel for Admitad
			if (!getSourceCookie(cookie_name)) {
				ADMITAD.Invoice.broker = 'na';
			} else if (getSourceCookie(cookie_name) != deduplication_cookie_value) {
				ADMITAD.Invoice.broker = getSourceCookie(cookie_name);
			} else {
				ADMITAD.Invoice.broker = 'adm';
			};

			ADMITAD.Invoice.category = '1';
			var orderedItem = []; // temporary array for product items

			// repeat for each item in the cart
			orderedItem.push({
				Product: {
					productID: '{{product_id}}', // internal item code (up to 100 characters, matches the ID from the product feed)
					category: '1',
					price: '{{price}}', // item price (if there is a discount, this is a discounted price)
					priceCurrency: '{{currency_code}}', // currency code per ISO-4217 alpha-3
				},
				orderQuantity: '{{quantity}}', // quantity
				additionalType: 'sale' // always sale
			});

			ADMITAD.Invoice.referencesOrder = ADMITAD.Invoice.referencesOrder || [];
			// adding more items
			ADMITAD.Invoice.referencesOrder.push({
				orderNumber: '{{order number}}', // order ID from your CMS (up to 100 characters)
				discountCode: '{{promocode}}', // promo code; this parameter is required if you provide Take&Go promo codes to publishers
				orderedItem: orderedItem
			});

			// Important! If you send data via AJAX or through the one-click order form, uncomment the last string:
			// ADMITAD.Tracking.processPositions();
		</script>
	<?php endif; ?>
	<!-- <script type="text/javascript">
    window._ab_id_ = 131695
  </script>
  <script src="https://cdn.botfaqtor.ru/one.js"></script> -->
</head>

<body <?php body_class('page__body'); ?>>
	<?php wp_body_open(); ?>
	<?php platejka_render_section( 'preloader' ); ?>
	<div id="page" class="site">
		<header class="header">
			<div class="header__top">
				<div class="container">
					<div class="header__row header__row_top">
						<div class="header__left">
							<div class="header__loc"><?php the_field('adres', 'option'); ?></div>
							<div class="header__time"><?php the_field('vremya_raboty', 'option'); ?></div>
							<div class="header__cb">Внесен в реестр ЦБ</div>
						</div>
						<div class="header__right">
							<a href="mailto:<?php the_field('pochta', 'option'); ?>" class="header__mail"><?php the_field('pochta', 'option'); ?></a>
							<div class="header__phone">
								<?php if (have_rows('socz_seti_header', 'option')) : ?>
									<?php while (have_rows('socz_seti_header', 'option')) : the_row(); ?>
										<?php $ssylka = get_sub_field('ssylka'); ?>
										<?php if ($ssylka) : ?>
											<div class="header__tg">
												<a href="<?php echo esc_url($ssylka['url']); ?>" target="<?php echo esc_attr($ssylka['target']); ?>" class="social__link <?php the_sub_field('class'); ?>" aria-label="<?php echo esc_html($ssylka['title']); ?>" rel="nofollow">
													<?php echo esc_html($ssylka['title']); ?>
												</a>
											</div>
										<?php endif; ?>
									<?php endwhile; ?>
								<?php else : ?>
									<?php // No rows found 
									?>
								<?php endif; ?>
								<a href="tel:<?php the_field('telefon', 'option'); ?>"><?php the_field('telefon', 'option'); ?></a>
							</div>
							<div class="header__button">
								<button class="btn-reset header__button_call" data-graph-path="call">Связаться</button>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="header__bottom">
				<div class="container">
					<div class="header__row header__row_bottom">
						<div class="header__wrapper">
							<div class="header__logo">
								<a href="<?php echo esc_url(home_url('/')); ?>" class="header__logo-link" aria-label="Платёжка — на главную">
									<svg class="header__logo-mark" width="38" height="48" viewBox="0 0 38 48" fill="none"
										xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
										<path
											d="M18.3894 0.00013007C8.27342 0.036505 0 8.01536 0 17.3934V37.0148C0 43.0818 5.30514 48 11.8497 48V33.0911V17.2662C11.8497 14.1421 14.5846 11.2415 17.9449 11.0033C22.0737 10.7105 25.4918 13.9572 25.0335 17.808C24.7075 20.5482 22.3767 22.7881 19.4331 23.1803C17.1638 23.4829 15.0785 22.7041 13.6688 21.3252V28.3838C13.6688 31.7993 16.8571 34.5045 20.5183 34.1232C20.5803 34.1168 20.6425 34.11 20.7044 34.1029C29.511 33.1024 36.3897 26.3217 36.8937 18.1099C37.5021 8.20095 28.9589 -0.0377448 18.3894 0.00013007Z"
											fill="url(#paint0_linear_2068_92)" />
										<path
											d="M18.5442 0.0141597C18.1757 0.0156507 17.8108 0.0305596 17.4477 0.0514329C17.2974 0.0603786 17.1483 0.0730515 16.9993 0.0853517C16.8052 0.101007 16.6124 0.119272 16.42 0.140146C16.212 0.163255 16.004 0.187482 15.798 0.217301C15.7194 0.228483 15.6416 0.241901 15.5634 0.253829C10.2848 1.08093 5.73667 4.00878 2.92786 8.02317C5.74363 5.84974 9.38365 4.53397 13.3646 4.53397C22.2865 4.53397 29.519 11.118 29.519 19.2399C29.519 26.0745 24.3947 31.8135 17.4546 33.4625C18.4185 33.8632 19.5093 34.0417 20.6454 33.9347C20.7265 33.9265 20.8068 33.921 20.8879 33.9116C29.8019 32.9172 36.7646 26.1773 37.2747 18.0152C37.8902 8.16553 29.2427 -0.0234868 18.5442 0.0141597Z"
											fill="#218B17" />
										<path
											d="M0 28.0976V37.3041C0 43.2114 5.13034 48 11.4593 48V34.443C6.68099 34.0487 2.5286 31.6246 0 28.0976Z"
											fill="#1D9712" />
										<defs>
											<linearGradient id="paint0_linear_2068_92" x1="18.4622" y1="0" x2="18.4622" y2="106.615"
												gradientUnits="userSpaceOnUse">
												<stop stop-color="#1B4D16" />
												<stop offset="1" stop-color="#3FB333" />
											</linearGradient>
										</defs>
									</svg>
									<svg class="header__logo-word" width="120" height="21" viewBox="0 0 120 21" fill="none"
										xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
										<path d="M8.89896 8.75443H3.89159V20.1028H0V5.97139H12.7906V20.1028H8.89896V8.75443Z" fill="#25252A" />
										<path
											d="M20.3182 14.2935C20.0823 16.365 19.6469 17.8961 19.0119 18.8869C18.3769 19.8776 17.37 20.373 15.9912 20.373C15.5013 20.373 15.1566 20.3369 14.957 20.2649L14.6305 20.1568V17.0495C14.6486 17.0675 14.694 17.0856 14.7665 17.1036C14.9117 17.1396 15.0659 17.1576 15.2292 17.1576C15.7372 17.1576 16.1182 16.8604 16.3722 16.2659C16.6262 15.6535 16.8076 14.7348 16.9164 13.5099C16.9346 13.2217 16.9527 12.8434 16.9709 12.3751C17.0072 11.9067 17.0344 11.3393 17.0525 10.6728C17.0888 9.98834 17.116 9.44794 17.1342 9.05165C17.1704 7.7547 17.1886 6.72794 17.1886 5.97139H28.1558V20.1028H24.2642V8.75443H20.5903C20.554 11.1862 20.4633 13.0326 20.3182 14.2935Z"
											fill="#25252A" />
										<path
											d="M35.1215 20.4C33.7427 20.4 32.6269 20.0667 31.7742 19.4002C30.8308 18.6617 30.3591 17.5899 30.3591 16.1849C30.3591 14.6177 31.0485 13.4469 32.4273 12.6723C33.661 11.9878 35.439 11.6456 37.7613 11.6456H39.3669V11.0781C39.3669 10.1775 39.1945 9.51999 38.8498 9.10569C38.5232 8.69138 37.9245 8.48423 37.0537 8.48423C35.6567 8.48423 34.8494 9.15072 34.6316 10.4837H30.9033C31.0122 8.89854 31.6744 7.67364 32.89 6.809C33.9967 6.03443 35.4662 5.64715 37.2986 5.64715C39.131 5.64715 40.5643 6.04344 41.5984 6.83602C42.7051 7.70066 43.2585 9.05165 43.2585 10.889V20.1028H39.4213V18.4005C38.4597 19.7335 37.0265 20.4 35.1215 20.4ZM36.2917 17.671C37.1807 17.671 37.9155 17.4458 38.496 16.9955C39.0766 16.5452 39.3669 15.9327 39.3669 15.1581V13.9693H37.8429C36.6455 13.9693 35.7383 14.1224 35.1215 14.4286C34.5228 14.7348 34.2234 15.2482 34.2234 15.9687C34.2234 17.1036 34.9129 17.671 36.2917 17.671Z"
											fill="#25252A" />
										<path d="M56.881 8.75443H52.6628V20.1028H48.744V8.75443H44.5258V5.97139H56.881V8.75443Z"
											fill="#25252A" />
										<path
											d="M64.8719 20.4C62.6585 20.4 60.8896 19.7605 59.5651 18.4816C58.15 17.1846 57.4425 15.4283 57.4425 13.2127V12.9965C57.4425 10.7989 58.15 9.01562 59.5651 7.64662C60.944 6.31364 62.6766 5.64715 64.763 5.64715C66.7406 5.64715 68.3553 6.21456 69.6071 7.3494C71.0041 8.61032 71.7026 10.4387 71.7026 12.8344V13.9152H61.4429C61.4973 15.1041 61.8239 16.0228 62.4226 16.6712C63.0395 17.3017 63.8922 17.6169 64.9807 17.6169C66.6861 17.6169 67.6749 16.9595 67.947 15.6445H71.6754C71.4395 17.1756 70.7319 18.3555 69.5527 19.1841C68.3915 19.9947 66.8313 20.4 64.8719 20.4ZM67.8654 11.5105C67.7384 9.40291 66.7043 8.34913 64.763 8.34913C63.874 8.34913 63.1392 8.62834 62.5587 9.18675C61.9963 9.72715 61.6425 10.5017 61.4973 11.5105H67.8654ZM63.3207 3.32344C62.9397 3.70172 62.468 3.89086 61.9055 3.89086C61.3431 3.89086 60.8623 3.70172 60.4632 3.32344C60.0822 2.94516 59.8917 2.48583 59.8917 1.94543C59.8917 1.40503 60.0822 0.945694 60.4632 0.567416C60.8623 0.189139 61.3431 0 61.9055 0C62.468 0 62.9397 0.189139 63.3207 0.567416C63.7198 0.945694 63.9194 1.40503 63.9194 1.94543C63.9194 2.48583 63.7198 2.94516 63.3207 3.32344ZM68.8723 3.32344C68.4913 3.70172 68.0196 3.89086 67.4572 3.89086C66.8948 3.89086 66.414 3.70172 66.0149 3.32344C65.6339 2.94516 65.4434 2.48583 65.4434 1.94543C65.4434 1.40503 65.6339 0.945694 66.0149 0.567416C66.414 0.189139 66.8948 0 67.4572 0C68.0196 0 68.4913 0.189139 68.8723 0.567416C69.2715 0.945694 69.471 1.40503 69.471 1.94543C69.471 2.48583 69.2715 2.94516 68.8723 3.32344Z"
											fill="#25252A" />
										<path
											d="M79.9556 13.3478L75.9007 20.1028H71.6826L76.5267 12.5102L72.0364 5.97139H76.0912L79.9556 12.1049V5.97139H83.82V12.1319L87.7116 5.97139H91.7393L87.2489 12.4291L92.1475 20.1028H87.8477L83.82 13.2938V20.1028H79.9556V13.3478Z"
											fill="#25252A" />
										<path
											d="M97.4959 13.4289V20.1028H93.577V5.97139H97.4959V12.0238L102.095 5.97139H106.286L101.088 12.4291L106.694 20.1028H102.231L97.4959 13.4289Z"
											fill="#25252A" />
										<path
											d="M111.863 20.4C110.484 20.4 109.368 20.0667 108.515 19.4002C107.572 18.6617 107.1 17.5899 107.1 16.1849C107.1 14.6177 107.79 13.4469 109.168 12.6723C110.402 11.9878 112.18 11.6456 114.502 11.6456H116.108V11.0781C116.108 10.1775 115.936 9.51999 115.591 9.10569C115.264 8.69138 114.666 8.48423 113.795 8.48423C112.398 8.48423 111.591 9.15072 111.373 10.4837H107.645C107.753 8.89854 108.416 7.67364 109.631 6.809C110.738 6.03443 112.207 5.64715 114.04 5.64715C115.872 5.64715 117.305 6.04344 118.34 6.83602C119.446 7.70066 120 9.05165 120 10.889V20.1028H116.162V18.4005C115.201 19.7335 113.768 20.4 111.863 20.4ZM113.033 17.671C113.922 17.671 114.657 17.4458 115.237 16.9955C115.818 16.5452 116.108 15.9327 116.108 15.1581V13.9693H114.584C113.387 13.9693 112.48 14.1224 111.863 14.4286C111.264 14.7348 110.965 15.2482 110.965 15.9687C110.965 17.1036 111.654 17.671 113.033 17.671Z"
											fill="#25252A" />
									</svg>
								</a>
							</div>
							<button class="btn-reset burger" aria-label="Открыть меню" aria-expanded="false" data-burger>
							</button>
							<div class="header__navigation" data-menu>
								<nav class="nav" aria-label="<?php esc_attr_e( 'Основная навигация', 'platejka_rework' ); ?>" data-menu-content>
									<?php
									wp_nav_menu(
										array(
											'theme_location' => 'menu-1',
											'container'      => false,
											'fallback_cb'    => false,
											'depth'          => 3,
											'items_wrap'     => '<ul class="list-reset nav__list">%3$s</ul>',
											'walker'         => new Platejka_Rework_Nav_Walker(),
										)
									);
									?>
								</nav>
								<div class="header-menu-info">
									<div class="header-menu-info__contacts">
										<a class="header-menu-info__contact" href="tel:+78005338819">
											<span class="header-menu-info__icon header-menu-info__icon_phone" aria-hidden="true"></span>
											<span><strong>8 800 533-88-19</strong><small>По всем вопросам</small></span>
										</a>
										<a class="header-menu-info__contact" href="mailto:a@platejka.com">
											<span class="header-menu-info__icon header-menu-info__icon_mail" aria-hidden="true"></span>
											<span><strong>a@platejka.com</strong><small>Поддержка</small></span>
										</a>
									</div>
									<p class="header-menu-info__address">127204, Россия, Москва, Долгопрудненское шоссе, 3</p>
									<p class="header-menu-info__details">
										Расчётный счёт 40702810700000033434<br>
										ПАО «РосДорБанк»<br>
										Корреспондентский счёт 30101810945250000666<br>
										БИК 044525666
									</p>
									<a class="header-menu-info__registry" href="#">Внесён в реестр ЦБ</a>
								</div>
							</div>
						</div>
						<div class="header__wrap">
							<div class="header__tg header__tg_bottom">
								<a href="#" rel="nofollow noopener" target="_blank" title="Telegram" aria-label="Перейти в телеграм">Перейти
									в телеграм</a>
							</div>
							<div class="header__button header__button_bottom">
								<button class="btn-reset header__button_call" data-graph-path="call">Связаться</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</header>
