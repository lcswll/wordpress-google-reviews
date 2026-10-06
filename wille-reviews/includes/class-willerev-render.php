<?php
/**
 * HTML for every layout × style. Shared by the shortcode, the block, the floating badge and the admin preview.
 *
 * Layouts decide the structure (grid, slider, list, wall, badge, social proof); styles are pure CSS skins
 * (class willerev--style-*), so switching a style never changes the markup.
 *
 * @package Wille_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renderer.
 */
class WILLEREV_Render {

	/**
	 * Layout slugs.
	 */
	const LAYOUTS = array( 'grid', 'carousel', 'list', 'masonry', 'badge', 'social' );

	/**
	 * Style slugs.
	 */
	const STYLES = array( 'light', 'dark', 'minimal', 'quote', 'accent' );

	/**
	 * Most reviews one widget shows (the Places API returns up to five).
	 */
	const MAX_LIMIT = 10;

	/**
	 * Default anchor of the first review section on a page.
	 */
	const ANCHOR = 'google-reviews';

	/**
	 * Number of review sections rendered in this request (only the first gets the default anchor).
	 *
	 * @var int
	 */
	protected static $instances = 0;

	/**
	 * Layout names and one-line descriptions.
	 *
	 * @return array<string,array{label:string,description:string}>
	 */
	public static function layouts() {
		return array(
			'grid'     => array(
				'label'       => __( 'Grid', 'wille-reviews' ),
				'description' => __( 'Cards in columns – the classic for a reviews section.', 'wille-reviews' ),
			),
			'carousel' => array(
				'label'       => __( 'Slider', 'wille-reviews' ),
				'description' => __( 'One row that visitors swipe or click through – saves space.', 'wille-reviews' ),
			),
			'list'     => array(
				'label'       => __( 'List', 'wille-reviews' ),
				'description' => __( 'Full-width rows, easy to read – good for sidebars and long texts.', 'wille-reviews' ),
			),
			'masonry'  => array(
				'label'       => __( 'Wall', 'wille-reviews' ),
				'description' => __( 'Cards of different heights stacked without gaps – a testimonial wall.', 'wille-reviews' ),
			),
			'badge'    => array(
				'label'       => __( 'Badge', 'wille-reviews' ),
				'description' => __( 'Compact rating badge for headers, footers and checkout pages.', 'wille-reviews' ),
			),
			'social'   => array(
				'label'       => __( 'Social proof', 'wille-reviews' ),
				'description' => __( 'Reviewer photos, stars and total count – ideal next to a call to action.', 'wille-reviews' ),
			),
		);
	}

	/**
	 * Style names.
	 *
	 * @return array<string,string>
	 */
	public static function styles() {
		return array(
			'light'   => __( 'Light', 'wille-reviews' ),
			'dark'    => __( 'Dark', 'wille-reviews' ),
			'minimal' => __( 'Minimal', 'wille-reviews' ),
			'quote'   => __( 'Quote', 'wille-reviews' ),
			'accent'  => __( 'Accent', 'wille-reviews' ),
		);
	}

	/**
	 * Interpret a yes/no attribute value (pure).
	 *
	 * @param mixed $value Attribute value.
	 * @return bool
	 */
	public static function to_bool( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}
		return in_array( strtolower( trim( (string) $value ) ), array( '1', 'yes', 'true', 'on' ), true );
	}

	/**
	 * Normalize shortcode/block attributes on top of the saved design defaults (pure).
	 *
	 * @param array<string,mixed> $atts     Attributes (any subset).
	 * @param array<string,mixed> $defaults Settings (WILLEREV_Install::defaults() keys).
	 * @return array{layout:string,style:string,limit:int,min_rating:int,columns:int,lines:int,header:bool,avatars:bool,cta:bool,accent:string,count_color:string,radius:int,sort:string,link:string,align:string,id:string,class:string}
	 */
	public static function args( array $atts, array $defaults ) {
		$pick = static function ( $key, $fallback ) use ( $atts ) {
			return array_key_exists( $key, $atts ) && null !== $atts[ $key ] && '' !== $atts[ $key ] ? $atts[ $key ] : $fallback;
		};

		$layout = sanitize_key( (string) $pick( 'layout', $defaults['layout'] ) );
		$style  = sanitize_key( (string) $pick( 'style', $defaults['style'] ) );
		$style  = 'bubble' === $style ? 'quote' : $style; // Name of the quote style in pre-release builds.
		$accent = (string) $pick( 'accent', $defaults['accent'] );
		$sort   = sanitize_key( (string) $pick( 'sort', 'newest' ) );
		$align  = sanitize_key( (string) $pick( 'align', 'left' ) );

		return array(
			'layout'      => in_array( $layout, self::LAYOUTS, true ) ? $layout : 'grid',
			'style'       => in_array( $style, self::STYLES, true ) ? $style : 'light',
			'limit'       => min( self::MAX_LIMIT, max( 1, (int) $pick( 'limit', $defaults['limit'] ) ) ),
			'min_rating'  => min( 5, max( 0, (int) $pick( 'min_rating', $defaults['min_rating'] ) ) ),
			'columns'     => min( 4, max( 1, (int) $pick( 'columns', $defaults['columns'] ) ) ),
			'lines'       => min( 30, max( 0, (int) $pick( 'lines', $defaults['lines'] ) ) ),
			'header'      => self::to_bool( $pick( 'header', $defaults['show_header'] ) ),
			'avatars'     => self::to_bool( $pick( 'avatars', $defaults['show_avatars'] ) ) && empty( $defaults['hide_avatars'] ),
			'cta'         => self::to_bool( $pick( 'cta', $defaults['show_cta'] ) ),
			'accent'      => self::hex_color( $accent, '#1a73e8' ),
			// Colour of the number of reviews ('' = text colour of the style).
			'count_color' => self::hex_color( (string) $pick( 'count_color', '' ), '' ),
			'radius'      => min( 32, max( 0, (int) $pick( 'radius', $defaults['radius'] ) ) ),
			'sort'        => in_array( $sort, array( 'newest', 'rating' ), true ) ? $sort : 'newest',
			'link'        => trim( (string) $pick( 'link', 'auto' ) ),
			'align'       => in_array( $align, array( 'left', 'center', 'right' ), true ) ? $align : 'left',
			'id'          => sanitize_html_class( (string) $pick( 'id', '' ) ),
			'class'       => trim( implode( ' ', array_map( 'sanitize_html_class', explode( ' ', (string) $pick( 'class', '' ) ) ) ) ),
		);
	}

	/**
	 * Valid #rgb/#rrggbb color or the fallback (pure).
	 *
	 * @param string $color    Input.
	 * @param string $fallback Fallback color.
	 * @return string
	 */
	public static function hex_color( $color, $fallback ) {
		$color = trim( $color );
		return preg_match( '/^#(?:[0-9a-fA-F]{3}){1,2}$/', $color ) ? strtolower( $color ) : $fallback;
	}

	/**
	 * Readable text color on top of the accent color: dark or white, by relative luminance (pure).
	 *
	 * @param string $hex #rgb or #rrggbb.
	 * @return string
	 */
	public static function ink_on( $hex ) {
		$hex = ltrim( self::hex_color( $hex, '#1a73e8' ), '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		$channel   = static function ( $value ) {
			$value /= 255;
			return $value <= 0.03928 ? $value / 12.92 : pow( ( $value + 0.055 ) / 1.055, 2.4 );
		};
		$luminance = 0.2126 * $channel( hexdec( substr( $hex, 0, 2 ) ) ) + 0.7152 * $channel( hexdec( substr( $hex, 2, 2 ) ) ) + 0.0722 * $channel( hexdec( substr( $hex, 4, 2 ) ) );
		// Contrast against white vs. against #1f2328 – whichever is higher.
		return ( 1.05 / ( $luminance + 0.05 ) ) >= ( ( $luminance + 0.05 ) / 0.0656 ) ? '#ffffff' : '#1f2328';
	}

	/**
	 * Reviews to show: minimum rating, order, limit (pure).
	 *
	 * @param array<int,array<string,mixed>> $reviews    Normalized reviews.
	 * @param int                            $min_rating Minimum stars (0 = all).
	 * @param int                            $limit      Maximum count.
	 * @param string                         $sort       newest|rating.
	 * @return array<int,array<string,mixed>>
	 */
	public static function select( array $reviews, $min_rating, $limit, $sort ) {
		$reviews = array_values(
			array_filter(
				$reviews,
				static function ( $review ) use ( $min_rating ) {
					return (int) $review['rating'] >= $min_rating;
				}
			)
		);
		usort(
			$reviews,
			static function ( $a, $b ) use ( $sort ) {
				if ( 'rating' === $sort && $a['rating'] !== $b['rating'] ) {
					return $b['rating'] <=> $a['rating'];
				}
				return $b['time'] <=> $a['time'];
			}
		);
		return array_slice( $reviews, 0, max( 1, $limit ) );
	}

	/**
	 * Register the front-end assets (enqueued only where a widget is rendered).
	 *
	 * @return void
	 */
	public static function register_assets() {
		wp_register_style( 'willerev', WILLEREV_URL . 'assets/css/willerev.css', array(), WILLEREV_VERSION );
		wp_register_script(
			'willerev',
			WILLEREV_URL . 'assets/js/willerev.js',
			array(),
			WILLEREV_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	/**
	 * Render a widget.
	 *
	 * @param array<string,mixed>      $args Normalized arguments (see args()).
	 * @param array<string,mixed>|null $data Place data; null = load it (WILLEREV_Places::data()).
	 * @param bool                     $is_demo Data are the built-in samples (admin preview).
	 * @return string
	 */
	public static function render( array $args, $data = null, $is_demo = false ) {
		if ( null === $data ) {
			$data = WILLEREV_Places::data();
		}
		if ( null === $data ) {
			return self::notice();
		}

		wp_enqueue_style( 'willerev' );
		wp_enqueue_script( 'willerev' );

		switch ( $args['layout'] ) {
			case 'badge':
				$html = self::badge( $args, $data );
				break;
			case 'social':
				$html = self::social( $args, $data );
				break;
			default:
				$html = self::section( $args, $data, $is_demo );
		}

		/**
		 * Filters the HTML of a review widget.
		 *
		 * @param string              $html Widget HTML.
		 * @param array<string,mixed> $args Normalized arguments.
		 * @param array<string,mixed> $data Place data.
		 */
		return (string) apply_filters( 'willerev_html', $html, $args, $data );
	}

	/**
	 * Hint for admins when there is nothing to show (visitors see nothing).
	 *
	 * @return string
	 */
	protected static function notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return '';
		}
		return '<p class="willerev-notice">' . sprintf(
			/* translators: %s: link to the settings page */
			esc_html__( 'Wille Reviews: no reviews to show yet. Connect your Google business in the %s. (Only administrators see this note.)', 'wille-reviews' ),
			'<a href="' . esc_url( admin_url( 'admin.php?page=wille-reviews-settings' ) ) . '">' . esc_html__( 'plugin settings', 'wille-reviews' ) . '</a>'
		) . '</p>';
	}

	/**
	 * Class list + CSS variables shared by every layout.
	 *
	 * @param array<string,mixed> $args  Normalized arguments.
	 * @param string[]            $extra Extra classes.
	 * @return string Attributes (class + style), escaped.
	 */
	protected static function root_attributes( array $args, array $extra = array() ) {
		$classes = array_merge(
			array(
				'willerev',
				'willerev--layout-' . $args['layout'],
				'willerev--style-' . $args['style'],
				'willerev--cols-' . $args['columns'],
				'willerev--align-' . $args['align'],
			),
			$args['header'] ? array() : array( 'willerev--no-header' ),
			$args['avatars'] ? array() : array( 'willerev--no-avatars' ),
			$args['cta'] ? array() : array( 'willerev--no-cta' ),
			$extra,
			'' !== $args['class'] ? array( $args['class'] ) : array()
		);
		$style   = sprintf(
			'--willerev-accent:%1$s;--willerev-accent-ink:%2$s;--willerev-radius:%3$dpx;--willerev-cols:%4$d;--willerev-lines:%5$d',
			$args['accent'],
			self::ink_on( $args['accent'] ),
			$args['radius'],
			$args['columns'],
			$args['lines'] > 0 ? $args['lines'] : 999
		) . ( '' !== $args['count_color'] ? ';--willerev-count:' . $args['count_color'] : '' );
		return 'class="' . esc_attr( implode( ' ', $classes ) ) . '" style="' . esc_attr( $style ) . '"';
	}

	/**
	 * Grid, slider, list and wall: header + review cards.
	 *
	 * @param array<string,mixed> $args    Normalized arguments.
	 * @param array<string,mixed> $data    Place data.
	 * @param bool                $is_demo Sample data (adds a label).
	 * @return string
	 */
	protected static function section( array $args, array $data, $is_demo ) {
		$reviews = self::select( (array) $data['reviews'], $args['min_rating'], $args['limit'], $args['sort'] );
		if ( empty( $reviews ) && ! $args['header'] ) {
			return '';
		}
		++self::$instances;
		$anchor   = '' !== $args['id'] ? $args['id'] : ( 1 === self::$instances ? self::ANCHOR : '' );
		$carousel = 'carousel' === $args['layout'];

		ob_start();
		?>
		<section <?php echo self::root_attributes( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in root_attributes(). ?><?php echo '' !== $anchor ? ' id="' . esc_attr( $anchor ) . '"' : ''; ?> aria-label="<?php esc_attr_e( 'Google reviews', 'wille-reviews' ); ?>" data-more="<?php esc_attr_e( 'Read more', 'wille-reviews' ); ?>" data-less="<?php esc_attr_e( 'Show less', 'wille-reviews' ); ?>">
			<?php
			if ( $args['header'] ) {
				echo self::summary( $data, $is_demo ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in summary().
			}
			?>
			<?php if ( ! empty( $reviews ) ) : ?>
				<div class="willerev__viewport">
					<div class="willerev__items"<?php echo $carousel ? ' tabindex="0" role="group" aria-label="' . esc_attr__( 'Reviews, scroll horizontally', 'wille-reviews' ) . '"' : ''; ?>>
						<?php
						foreach ( $reviews as $rank => $review ) {
							echo self::card( $review, $rank ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in card().
						}
						?>
					</div>
					<?php if ( $carousel && count( $reviews ) > 1 ) : ?>
						<button type="button" class="willerev__nav willerev__nav--prev" aria-label="<?php esc_attr_e( 'Previous reviews', 'wille-reviews' ); ?>" disabled>
							<svg viewBox="0 0 20 20" width="18" height="18" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12.7 15.3a1 1 0 0 1-1.4 1.4l-6-6a1 1 0 0 1 0-1.4l6-6a1 1 0 1 1 1.4 1.4L7.42 10l5.3 5.3Z"/></svg>
						</button>
						<button type="button" class="willerev__nav willerev__nav--next" aria-label="<?php esc_attr_e( 'Next reviews', 'wille-reviews' ); ?>">
							<svg viewBox="0 0 20 20" width="18" height="18" aria-hidden="true" focusable="false"><path fill="currentColor" d="M7.3 4.7a1 1 0 0 1 1.4-1.4l6 6a1 1 0 0 1 0 1.4l-6 6a1 1 0 1 1-1.4-1.4L12.58 10l-5.3-5.3Z"/></svg>
						</button>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Summary header: rating, stars, count, links to Google.
	 *
	 * @param array<string,mixed> $data    Place data.
	 * @param bool                $is_demo Sample data.
	 * @return string
	 */
	protected static function summary( array $data, $is_demo ) {
		$rating = (float) $data['rating'];
		ob_start();
		?>
		<header class="willerev__header">
			<div class="willerev__brand">
				<?php echo self::google_logo( 22 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
				<span class="willerev__brand-text"><?php esc_html_e( 'Google reviews', 'wille-reviews' ); ?></span>
				<?php if ( $is_demo ) : ?>
					<span class="willerev__demo"><?php esc_html_e( 'Sample data', 'wille-reviews' ); ?></span>
				<?php endif; ?>
			</div>
			<div class="willerev__score">
				<span class="willerev__rating"><?php echo esc_html( number_format_i18n( $rating, 1 ) ); ?></span>
				<?php echo self::stars( $rating ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in stars(). ?>
				<span class="willerev__count">
					<?php
					/* translators: %s: number of reviews */
					printf( esc_html( _n( 'Based on %s review', 'Based on %s reviews', (int) $data['count'], 'wille-reviews' ) ), self::count_number( (int) $data['count'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- format escaped, number escaped in count_number().
					?>
				</span>
			</div>
			<div class="willerev__actions">
				<?php if ( '' !== (string) $data['url'] ) : ?>
					<a class="willerev__button" href="<?php echo esc_url( (string) $data['url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'See all on Google', 'wille-reviews' ); ?></a>
				<?php endif; ?>
				<?php if ( '' !== (string) $data['write_url'] ) : ?>
					<a class="willerev__button willerev__button--primary" href="<?php echo esc_url( (string) $data['write_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Write a review', 'wille-reviews' ); ?></a>
				<?php endif; ?>
			</div>
		</header>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * One review card.
	 *
	 * @param array<string,mixed> $review Normalized review.
	 * @param int                 $rank   Position (the admin preview hides cards beyond the limit by it).
	 * @return string
	 */
	public static function card( array $review, $rank = 0 ) {
		$author = '' !== (string) $review['author'] ? (string) $review['author'] : __( 'Google user', 'wille-reviews' );
		ob_start();
		?>
		<article class="willerev-card" data-rating="<?php echo esc_attr( (string) (int) $review['rating'] ); ?>" data-rank="<?php echo esc_attr( (string) (int) $rank ); ?>">
			<div class="willerev-card__head">
				<?php echo self::avatar( $author, (string) $review['avatar'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in avatar(). ?>
				<div class="willerev-card__meta">
					<?php if ( '' !== (string) $review['author_url'] ) : ?>
						<a class="willerev-card__author" href="<?php echo esc_url( (string) $review['author_url'] ); ?>" target="_blank" rel="noopener nofollow"><?php echo esc_html( $author ); ?></a>
					<?php else : ?>
						<span class="willerev-card__author"><?php echo esc_html( $author ); ?></span>
					<?php endif; ?>
					<span class="willerev-card__time"><?php echo esc_html( self::when( $review ) ); ?></span>
				</div>
				<?php echo self::google_logo( 18 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
			</div>
			<div class="willerev-card__body">
				<?php echo self::stars( (float) $review['rating'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in stars(). ?>
				<?php if ( '' !== (string) $review['text'] ) : ?>
					<p class="willerev-card__text"><?php echo nl2br( esc_html( (string) $review['text'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped before nl2br(). ?></p>
					<button type="button" class="willerev-card__more" aria-expanded="false" hidden><?php esc_html_e( 'Read more', 'wille-reviews' ); ?></button>
				<?php endif; ?>
			</div>
		</article>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * "3 weeks ago" from the publish time (localized by WordPress), else Google's own description.
	 *
	 * @param array<string,mixed> $review Normalized review.
	 * @return string
	 */
	protected static function when( array $review ) {
		$time = (int) $review['time'];
		if ( $time > 0 ) {
			/* translators: %s: time span, e.g. "3 weeks" */
			return sprintf( __( '%s ago', 'wille-reviews' ), human_time_diff( $time, time() ) );
		}
		return (string) $review['relative'];
	}

	/**
	 * Profile photo, or the initial on a color derived from the name.
	 *
	 * @param string $name Author name.
	 * @param string $url  Local photo URL ('' = initial).
	 * @return string
	 */
	protected static function avatar( $name, $url ) {
		if ( '' !== $url ) {
			return '<img class="willerev-avatar" src="' . esc_url( $url ) . '" alt="" width="40" height="40" loading="lazy" decoding="async" />';
		}
		$initial = function_exists( 'mb_substr' ) ? mb_substr( $name, 0, 1 ) : substr( $name, 0, 1 );
		$hue     = hexdec( substr( md5( $name ), 0, 4 ) ) % 360;
		return '<span class="willerev-avatar willerev-avatar--initial" style="--willerev-hue:' . esc_attr( (string) $hue ) . '" aria-hidden="true">' . esc_html( strtoupper( $initial ) ) . '</span>';
	}

	/**
	 * Five stars, partially filled for fractions; announced as "4.8 out of 5 stars".
	 *
	 * @param float $rating 0–5.
	 * @return string
	 */
	public static function stars( $rating ) {
		$rating = max( 0.0, min( 5.0, (float) $rating ) );
		$star   = '<svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M10 1.6l2.35 4.76 5.25.76-3.8 3.7.9 5.23L10 14.5l-4.7 2.55.9-5.23-3.8-3.7 5.25-.76L10 1.6z"/></svg>';
		$row    = str_repeat( $star, 5 );
		/* translators: %s: rating, e.g. 4.8 */
		$label = sprintf( __( '%s out of 5 stars', 'wille-reviews' ), number_format_i18n( $rating, 1 ) );
		return sprintf(
			'<span class="willerev-stars" role="img" aria-label="%1$s"><span class="willerev-stars__empty">%2$s</span><span class="willerev-stars__full" style="width:%3$s%%">%2$s</span></span>',
			esc_attr( $label ),
			$row,
			esc_attr( (string) round( $rating * 20, 1 ) )
		);
	}

	/**
	 * Where the badge and social proof link to.
	 *
	 * @param string              $link auto|none|URL.
	 * @param array<string,mixed> $data Place data.
	 * @return string URL or ''.
	 */
	public static function link_target( $link, array $data ) {
		$lower = strtolower( $link );
		if ( 'none' === $lower ) {
			return '';
		}
		if ( '' === $lower || 'auto' === $lower ) {
			$page = (string) willerev()->settings()['reviews_url'];
			if ( '' !== $page ) {
				return false === strpos( $page, '#' ) ? $page . '#' . self::ANCHOR : $page;
			}
			return (string) $data['url'];
		}
		if ( 'google' === $lower ) {
			return (string) $data['url'];
		}
		return esc_url_raw( $link );
	}

	/**
	 * Opening tag of the clickable wrapper (link or plain element).
	 *
	 * @param string $url   Target ('' = not clickable).
	 * @param string $classes Classes.
	 * @param string $label Accessible name.
	 * @return string[] Opening and closing tag.
	 */
	protected static function wrapper( $url, $classes, $label ) {
		if ( '' === $url ) {
			return array( '<div class="' . esc_attr( $classes ) . '" role="group" aria-label="' . esc_attr( $label ) . '">', '</div>' );
		}
		$host     = wp_parse_url( $url, PHP_URL_HOST );
		$external = $host && wp_parse_url( home_url(), PHP_URL_HOST ) !== $host;
		return array(
			'<a class="' . esc_attr( $classes ) . '" href="' . esc_url( $url ) . '" aria-label="' . esc_attr( $label ) . '"' . ( $external ? ' target="_blank" rel="noopener"' : '' ) . '>',
			'</a>',
		);
	}

	/**
	 * Accessible summary of rating and count.
	 *
	 * @param array<string,mixed> $data Place data.
	 * @return string
	 */
	protected static function summary_label( array $data ) {
		return sprintf(
			/* translators: 1: rating, 2: number of reviews */
			_n( 'Rated %1$s out of 5 on Google, %2$s review', 'Rated %1$s out of 5 on Google, %2$s reviews', (int) $data['count'], 'wille-reviews' ),
			number_format_i18n( (float) $data['rating'], 1 ),
			number_format_i18n( (int) $data['count'] )
		);
	}

	/**
	 * Compact badge.
	 *
	 * @param array<string,mixed> $args  Normalized arguments.
	 * @param array<string,mixed> $data  Place data.
	 * @param string[]            $extra Extra root classes (floating badge).
	 * @return string
	 */
	public static function badge( array $args, array $data, array $extra = array() ) {
		$wrap = self::wrapper( self::link_target( $args['link'], $data ), 'willerev-badge', self::summary_label( $data ) );
		ob_start();
		?>
		<div <?php echo self::root_attributes( $args, $extra ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in root_attributes(). ?>>
			<?php echo $wrap[0]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in wrapper(). ?>
				<?php echo self::google_logo( 26 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
				<span class="willerev-badge__body" aria-hidden="true">
					<span class="willerev-badge__title"><?php esc_html_e( 'Google rating', 'wille-reviews' ); ?></span>
					<span class="willerev-badge__score">
						<span class="willerev-badge__rating"><?php echo esc_html( number_format_i18n( (float) $data['rating'], 1 ) ); ?></span>
						<?php echo self::stars( (float) $data['rating'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in stars(). ?>
					</span>
					<span class="willerev-badge__count">
						<?php
						/* translators: %s: number of reviews */
						printf( esc_html( _n( '%s review', '%s reviews', (int) $data['count'], 'wille-reviews' ) ), self::count_number( (int) $data['count'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- format escaped, number escaped in count_number().
						?>
					</span>
				</span>
			<?php echo $wrap[1]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static closing tag. ?>
			<?php if ( in_array( 'willerev--floating', $extra, true ) ) : ?>
				<button type="button" class="willerev-badge__close" aria-label="<?php esc_attr_e( 'Hide rating', 'wille-reviews' ); ?>">
					<svg viewBox="0 0 20 20" width="12" height="12" aria-hidden="true" focusable="false"><path fill="currentColor" d="M4.3 4.3a1 1 0 0 1 1.4 0L10 8.6l4.3-4.3a1 1 0 1 1 1.4 1.4L11.4 10l4.3 4.3a1 1 0 0 1-1.4 1.4L10 11.4l-4.3 4.3a1 1 0 0 1-1.4-1.4L8.6 10 4.3 5.7a1 1 0 0 1 0-1.4Z"/></svg>
				</button>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Social proof: overlapping reviewer photos, stars, rating and total count.
	 *
	 * @param array<string,mixed> $args Normalized arguments.
	 * @param array<string,mixed> $data Place data.
	 * @return string
	 */
	protected static function social( array $args, array $data ) {
		$faces = $args['avatars'] ? array_slice( self::select( (array) $data['reviews'], max( 4, $args['min_rating'] ), 5, 'newest' ), 0, 5 ) : array();
		$wrap  = self::wrapper( self::link_target( $args['link'], $data ), 'willerev-social', self::summary_label( $data ) );
		ob_start();
		?>
		<div <?php echo self::root_attributes( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in root_attributes(). ?>>
			<?php echo $wrap[0]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in wrapper(). ?>
				<?php if ( ! empty( $faces ) ) : ?>
					<span class="willerev-social__faces" aria-hidden="true">
						<?php
						foreach ( $faces as $face ) {
							echo self::avatar( '' !== (string) $face['author'] ? (string) $face['author'] : '?', (string) $face['avatar'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in avatar().
						}
						?>
					</span>
				<?php endif; ?>
				<span class="willerev-social__body" aria-hidden="true">
					<span class="willerev-social__score">
						<?php echo self::stars( (float) $data['rating'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in stars(). ?>
						<span class="willerev-social__rating"><?php echo esc_html( number_format_i18n( (float) $data['rating'], 1 ) ); ?><span class="willerev-social__max">/5</span></span>
					</span>
					<span class="willerev-social__caption">
						<?php echo self::google_logo( 14 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
						<span>
							<?php
							/* translators: %s: number of reviews (bold) */
							printf( esc_html( _n( '%s review on Google', '%s reviews on Google', (int) $data['count'], 'wille-reviews' ) ), self::count_number( (int) $data['count'], 'strong' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- format escaped, number escaped in count_number().
							?>
						</span>
					</span>
				</span>
			<?php echo $wrap[1]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static closing tag. ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The number of reviews inside a sentence, colourable via --willerev-count (count_color).
	 *
	 * @param int    $count Number of reviews.
	 * @param string $tag   span|strong.
	 * @return string
	 */
	protected static function count_number( $count, $tag = 'span' ) {
		$tag = 'strong' === $tag ? 'strong' : 'span';
		return '<' . $tag . ' class="willerev-num">' . esc_html( number_format_i18n( $count ) ) . '</' . $tag . '>';
	}

	/**
	 * The Google "G" (attribution: the reviews come from Google).
	 *
	 * @param int $size Pixel size.
	 * @return string
	 */
	public static function google_logo( $size = 20 ) {
		return sprintf(
			'<svg class="willerev-g" width="%1$d" height="%1$d" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
			. '<path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/>'
			. '<path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>'
			. '<path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>'
			. '<path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>'
			. '</svg>',
			(int) $size
		);
	}

	/**
	 * Sample data for the admin preview before a place is connected.
	 *
	 * @return array<string,mixed>
	 */
	public static function demo_data() {
		$now     = time();
		$samples = array(
			array( 'Anna Becker', 5, 3, __( 'Fantastic service from start to finish. The team took time for our questions and the result exceeded our expectations. Clear recommendation!', 'wille-reviews' ) ),
			array( 'Jonas Weber', 5, 9, __( 'Fast, friendly and fair prices. Appointment the same week.', 'wille-reviews' ) ),
			array( 'Mira Schulz', 4, 17, __( 'Very good advice and a pleasant atmosphere. Parking is a bit tight, otherwise everything was perfect – we will definitely come back and have already recommended the shop to friends and family. Thanks again to the whole team for the great support!', 'wille-reviews' ) ),
			array( 'Lukas Hoffmann', 5, 32, __( 'Professional, punctual and tidy work. Exactly as agreed.', 'wille-reviews' ) ),
			array( 'Sophie Wagner', 5, 58, __( 'I have been a customer for years and have never been disappointed. Always reachable, always helpful.', 'wille-reviews' ) ),
		);
		$reviews = array();
		foreach ( $samples as $index => $sample ) {
			$reviews[] = array(
				'id'         => 'demo' . $index,
				'author'     => $sample[0],
				'author_url' => '',
				'avatar'     => '',
				'rating'     => $sample[1],
				'text'       => $sample[3],
				'time'       => $now - $sample[2] * DAY_IN_SECONDS,
				'relative'   => '',
			);
		}
		return array(
			'name'       => __( 'Your business', 'wille-reviews' ),
			'rating'     => 4.8,
			'count'      => 127,
			'url'        => '',
			'write_url'  => '',
			'reviews'    => $reviews,
			'fetched_at' => $now,
		);
	}
}
