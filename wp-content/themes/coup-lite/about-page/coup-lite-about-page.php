<?php
/**
 * Coup Lite - About page class
 *
 * @subpackage Admin
 * @since 1.0.0
 */
if ( ! class_exists( 'Coup_Lite_About_Page' ) ) {
	/**
	 * Singleton class used for generating the about page of the theme.
	 */
	class Coup_Lite_About_Page {
		/**
		 * Define the version of the class.
		 *
		 * @var string $version The Coup_Lite_About_Page class version.
		 */
		private $version = '1.0.0';
		/**
		 * Used for loading the texts and setup the actions inside the page.
		 *
		 * @var array $config The configuration array for the theme used.
		 */
		private $config;
		/**
		 * Get the theme name using wp_get_theme.
		 *
		 * @var string $theme_name The theme name.
		 */
		private $theme_name;
		/**
		 * Get the theme slug ( theme folder name ).
		 *
		 * @var string $theme_slug The theme slug.
		 */
		private $theme_slug;
		/**
		 * The current theme object.
		 *
		 * @var WP_Theme $theme The current theme.
		 */
		private $theme;
		/**
		 * Holds the theme version.
		 *
		 * @var string $theme_version The theme version.
		 */
		private $theme_version;
		/**
		 * Define the menu item name for the page.
		 *
		 * @var string $menu_name The name of the menu name under Appearance settings.
		 */
		private $menu_name;
		/**
		 * Define the page title name.
		 *
		 * @var string $page_name The title of the About page.
		 */
		private $page_name;
		/**
		 * Define the page tabs.
		 *
		 * @var array $tabs The page tabs.
		 */
		private $tabs;
		/**
		 * Define the html notification content displayed upon activation.
		 *
		 * @var string $notification The html notification content.
		 */
		private $notification;
		/**
		 * The single instance of Coup_Lite_About_Page
		 *
		 * @var Coup_Lite_About_Page $instance The  Coup_Lite_About_Page instance.
		 */
		private static $instance;

		/**
		 * The Main Coup_Lite_About_Page instance.
		 *
		 * We make sure that only one instance of Coup_Lite_About_Page exists in the memory at one time.
		 *
		 * @param array $config The configuration array.
		 */
		public static function init( $config ) {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Coup_Lite_About_Page ) ) {
				self::$instance = new Coup_Lite_About_Page;
				if ( ! empty( $config ) && is_array( $config ) ) {
					self::$instance->config = $config;
					self::$instance->setup_config();
					self::$instance->setup_actions();
				}
			}

		}

		/**
		 * Setup the class props based on the config array.
		 */
		public function setup_config() {
			$theme = wp_get_theme();
			if ( is_child_theme() ) {
				$this->theme_name = $theme->parent()->get( 'Name' );
				$this->theme      = $theme->parent();
			} else {
				$this->theme_name = $theme->get( 'Name' );
				$this->theme      = $theme->parent();
			}
			$this->theme_version = $theme->get( 'Version' );
			$this->theme_slug    = $theme->get_template();
			$this->menu_name     = isset( $this->config['menu_name'] ) ? $this->config['menu_name'] : 'About ' . $this->theme_name;
			$this->page_name     = isset( $this->config['page_name'] ) ? $this->config['page_name'] : 'About ' . $this->theme_name;
			$this->notification  = isset( $this->config['notification'] ) ? $this->config['notification'] : ( '<p>' . sprintf( 'Welcome! Thank you for choosing %1$s! To fully take advantage of the best our theme can offer please make sure you visit our %2$swelcome page%3$s.', 'Coup Lite', '<a href="' . esc_url( admin_url( 'themes.php?page=' . $this->theme_slug . '-welcome' ) ) . '">', '</a>' ) . '</p><p><a href="' . esc_url( admin_url( 'themes.php?page=' . $this->theme_slug . '-welcome' ) ) . '" class="button" style="text-decoration: none;">' . sprintf( 'Get started with %s', 'Coup Lite' ) . '</a></p>' );
			$this->tabs          = isset( $this->config['tabs'] ) ? $this->config['tabs'] : array();

		}

		/**
		 * Setup the actions used for this page.
		 */
		public function setup_actions() {

			add_action( 'admin_menu', array( $this, 'register' ) );
			/* activation notice */
			add_action( 'load-themes.php', array( $this, 'activation_admin_notice' ) );
			/* enqueue script and style for about page */
			add_action( 'admin_enqueue_scripts', array( $this, 'style_and_scripts' ) );

			/* ajax callback for dismissable required actions */
			add_action( 'wp_ajax_coup_lite_about_page_dismiss_required_action', array( $this, 'dismiss_required_action_callback' ) );
			add_action( 'wp_ajax_nopriv_coup_lite_about_page_dismiss_required_action', array( $this, 'dismiss_required_action_callback') );
		}

		/**
		 * Hide required tab if no actions present.
		 *
		 * @return bool Either hide the tab or not.
		 */
		public function hide_required( $value, $tab ) {
			if ( $tab != 'recommended_actions' ) {
				return $value;
			}
			$required = $this->get_required_actions();
			if ( count( $required ) == 0 ) {
				return false;
			} else {
				return true;
			}
		}


		/**
		 * Register the menu page under Appearance menu.
		 */
		function register() {
			if ( ! empty( $this->menu_name ) && ! empty( $this->page_name ) ) {


				$title =  $this->page_name . '<span class="badge-action-count" style="padding: 0 7px; display: inline-block; background-color: #d54e21; color: #fff; font-size: 11px; line-height: 17px; font-weight: 400; margin: 1px 0 0 2px; vertical-align: top; -webkit-border-radius: 10px; border-radius: 10px; z-index: 26; margin-top: 0; margin-left: 5px;"> i </span>' ;

				add_theme_page( $this->menu_name, $title, 'activate_plugins', $this->theme_slug . '-welcome', array(
					$this,
					'coup_lite_about_page_render',
				) );
			}
		}

		/**
		 * Adds an admin notice upon successful activation.
		 */
		public function activation_admin_notice() {
			global $pagenow;
			if ( is_admin() && ( 'themes.php' == $pagenow ) && isset( $_GET['activated'] ) ) {
				add_action( 'admin_notices', array( $this, 'coup_lite_about_page_welcome_admin_notice' ), 99 );
			}
		}

		/**
		 * Display an admin notice linking to the about page
		 */
		public function coup_lite_about_page_welcome_admin_notice() {
			if ( ! empty( $this->notification ) ) {
				echo '<div class="updated notice is-dismissible">';
				echo wp_kses_post( $this->notification );
				echo '</div>';
			}
		}

		/**
		 * Render the main content page.
		 */
		public function coup_lite_about_page_render() {

			if ( ! empty( $this->config['welcome_title'] ) ) {
				$welcome_title = $this->config['welcome_title'];
			}
			if ( ! empty( $this->config['welcome_content'] ) ) {
				$welcome_content = $this->config['welcome_content'];
			}

			if ( ! empty( $welcome_title ) || ! empty( $welcome_content ) || ! empty( $this->tabs ) ) {

				echo '<div class="wrap about-wrap epsilon-wrap">';

				if ( ! empty( $welcome_title ) ) {
					echo '<h1>';
					echo esc_html( $welcome_title );
					echo '</h1>';
				}
				if ( ! empty( $welcome_content ) ) {
					echo '<div class="about-text">' . wp_kses_post( $welcome_content ) . '</div>';
				}

				/* Display tabs */
				if ( ! empty( $this->tabs ) ) {
					$active_tab = isset( $_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab']))  : 'getting_started';

					echo '<h2 class="nav-tab-wrapper wp-clearfix">';

					$actions_count = $this->get_required_actions();

					$count = 0;

					if ( ! empty( $actions_count ) ) {
						$count = count( $actions_count );
					}


					foreach ( $this->tabs as $tab_key => $tab_name ) {

						if ( ( $count == 0 ) && ( $tab_key == 'recommended_actions' ) ) {
							continue;
						}

						echo '<a href="' . esc_url( admin_url( 'themes.php?page=' . $this->theme_slug . '-welcome' ) ) . '&tab=' . esc_attr($tab_key) . '" class="nav-tab ' . ( $active_tab == $tab_key ? 'nav-tab-active' : '' ) . '" role="tab" data-toggle="tab">';
						echo esc_html( $tab_name );
						if ( $tab_key == 'recommended_actions' ) {
							$count = 0;

							$actions_count = $this->get_required_actions();

							if ( ! empty( $actions_count ) ) {
								$count = count( $actions_count );
							}
							if ( $count > 0 ) {
								echo '<span class="badge-action-count" style="padding: 0 7px; display: inline-block; background-color: #d54e21; color: #fff; font-size: 11px; line-height: 17px; font-weight: 400; margin: 1px 0 0 2px; vertical-align: top; -webkit-border-radius: 10px; border-radius: 10px; z-index: 26; margin-top: 0; margin-left: 5px;">' . esc_html( $count ) . '</span>';
							}
						}
						echo '</a>';

					}

					echo '</h2>';

					/* Display content for current tab */
					if ( method_exists( $this, $active_tab ) ) {
						$this->$active_tab();
					}
				}

				echo '</div><!--/.wrap.about-wrap-->';
			}
		}

		public function create_action_link( $state, $slug ) {

		    if( ( $slug == 'intergeo-maps' ) || ( $slug == 'visualizer' ) ) {
				$plugin_root_file = 'index';
			} elseif ( $slug == 'adblock-notify-by-bweb' ) {
				$plugin_root_file = 'adblock-notify';
			} else {
				$plugin_root_file = $slug;
			}

			switch ( $state ) {
				case 'install':
					return wp_nonce_url(
						add_query_arg(
							array(
								'action' => 'install-plugin',
								'plugin' => $slug
							),
							network_admin_url( 'update.php' )
						),
						'install-plugin_' . $slug
					);
					break;
				case 'deactivate':
					return add_query_arg( array(
						'action'        => 'deactivate',
						'plugin'        => rawurlencode( $slug . '/' . $plugin_root_file . '.php' ),
						'plugin_status' => 'all',
						'paged'         => '1',
						'_wpnonce'      => wp_create_nonce( 'deactivate-plugin_' . $slug . '/' . $plugin_root_file . '.php' ),
					), network_admin_url( 'plugins.php' ) );
					break;
				case 'activate':
					return add_query_arg( array(
						'action'        => 'activate',
						'plugin'        =>  rawurlencode( $slug . '/' . $plugin_root_file . '.php' ),
						'plugin_status' => 'all',
						'paged'         => '1',
						'_wpnonce'      => wp_create_nonce( 'activate-plugin_' . $slug . '/' . $plugin_root_file . '.php' ),
					), network_admin_url( 'plugins.php' ) );
					break;
			}
		}

		/**
		 * Getting started tab
		 */
		public function getting_started() {

			if ( ! empty( $this->config['getting_started'] ) ) {

				$getting_started = $this->config['getting_started'];

				if ( ! empty( $getting_started ) ) {

					echo '<div class="feature-section about-section three-col">';

					foreach ( $getting_started as $getting_started_item ) {

						echo '<div class="col">';
						if ( ! empty( $getting_started_item['title'] ) ) {
							echo '<h3>' . esc_html($getting_started_item['title']) . '</h3>';
						}
						if ( ! empty( $getting_started_item['text'] ) ) {
							echo '<p>' . esc_html($getting_started_item['text']) . '</p>';
						}
						if ( ! empty( $getting_started_item['button_link'] ) && ! empty( $getting_started_item['button_label'] ) ) {

							echo '<p>';
							$button_class = '';
							if ( $getting_started_item['is_button'] ) {
								$button_class = 'button button-primary';
							}

							$count = 0;

							$actions_count = $this->get_required_actions();

							if ( ! empty( $actions_count ) ) {
								$count = count( $actions_count );
							}

							if ( $count > 0 ) {
								echo '<span class="dashicons dashicons-no-alt"></span>';
								$button_new_tab = '_self';
								if ( isset( $getting_started_item['is_new_tab'] ) ) {
									if ( $getting_started_item['is_new_tab'] ) {
										$button_new_tab = '_blank';
									}
								}

								if ( isset( $getting_started_item['button_link'] ) && isset( $getting_started_item['button_label'] ) ) {
									echo '<a target="' . esc_html($button_new_tab) . '" href="' . esc_html($getting_started_item['button_link']) . '"class="' . esc_attr($button_class) . '">' . esc_html($getting_started_item['button_label']) . '</a>';
								}
							}



							echo '</p>';
						}

						echo '</div><!-- .col -->';
					}
					echo '</div><!-- .feature-section three-col -->';
				}

			}
		}

		/**
		 * Recommended Actions tab
		 */
		public function recommended_actions() {

			$recommended_actions = isset( $this->config['recommended_actions'] ) ? $this->config['recommended_actions'] : array();

			if ( ! empty( $recommended_actions ) ) {

				echo '<div class="feature-section action-required demo-import-boxed" id="plugin-filter">';

				$actions = array();
				$req_actions = isset( $this->config['recommended_actions'] ) ? $this->config['recommended_actions'] : array();
				foreach ( $req_actions['content'] as $req_action ) {
					$actions[] = $req_action;
				}

				if ( ! empty( $actions ) && is_array( $actions ) ) {

					$coup_lite_about_page_show_required_actions = get_option( $this->theme_slug . '_required_actions' );

					$hooray = true;

					foreach ( $actions as $action_key => $action_value ) {

						$hidden = false;

						if ( $coup_lite_about_page_show_required_actions[ $action_value['id'] ] === false ) {
							$hidden = true;
						}
						if ( $action_value['check'] ) {
							continue;
						}

						echo '<div class="coup-lite-about-page-action-required-box">';

						if ( ! $hidden ) {
							echo '<span data-action="dismiss" class="dashicons dashicons-visibility coup-lite-about-page-required-action-button" id="' . esc_attr( $action_value['id'] ) . '"></span>';
						} else {
							echo '<span data-action="add" class="dashicons dashicons-hidden coup-lite-about-page-required-action-button" id="' . esc_attr( $action_value['id'] ) .'"></span>';
						}

						if ( ! empty( $action_value['title'] ) ) {
							echo '<h3>' . wp_kses_post( $action_value['title'] ) . '</h3>';
						}

						if ( ! empty( $action_value['description'] ) ) {
							echo '<p>' . wp_kses_post( $action_value['description'] ) . '</p>';
						}

						if ( ! empty( $action_value['plugin_slug'] ) ) {

							$active = $this->check_if_plugin_active( $action_value['plugin_slug'] );
							$url    = $this->create_action_link( $active['needs'], $action_value['plugin_slug'] );
							$label  = '';

							switch ( $active['needs'] ) {

								case 'install':
									$class = 'install-now button';
									if ( ! empty( $this->config['recommended_actions']['install_label'] ) ) {
										$label = $this->config['recommended_actions']['install_label'];
									}
									break;
								case 'activate':
									$class = 'activate-now button button-primary';
									if ( ! empty( $this->config['recommended_actions']['activate_label'] ) ) {
										$label = $this->config['recommended_actions']['activate_label'];
									}
									break;
								case 'deactivate':
									$class = 'deactivate-now button';
									if ( ! empty( $this->config['recommended_actions']['deactivate_label'] ) ) {
										$label = $this->config['recommended_actions']['deactivate_label'];
									}
									break;
							}

							?>
							<p class="plugin-card-<?php echo esc_attr( $action_value['plugin_slug'] ) ?> action_button <?php echo ( $active['needs'] !== 'install' && $active['status'] ) ? 'active' : '' ?>">
								<a data-slug="<?php echo esc_attr( $action_value['plugin_slug'] ) ?>"
								   class="<?php echo esc_attr( $class ); ?>"
								   href="<?php echo esc_url( $url ) ?>"> <?php echo esc_html( $label ) ?> </a>
							</p>

							<?php

						}
						echo '</div>';
					}
				}
				echo '</div>';
			}
		}

		/**
		 * Child themes
		 */
		public function child_themes() {
			echo '<div id="child-themes" class="coup-lite-about-page-tab-pane">';
			$child_themes = isset( $this->config['child_themes'] ) ? $this->config['child_themes'] : array();
			if ( ! empty( $child_themes ) ) {
				if ( ! empty( $child_themes['content'] ) && is_array( $child_themes['content'] ) ) {
					echo '<div class="coup-lite-about-row">';
					for ( $i = 0; $i < count( $child_themes['content'] ); $i ++ ) {
						if( ( $i !== 0 ) && ( $i / 3 === 0 ) ) {
							echo '</div>';
							echo '<div class="coup-lite-about-row">';
						}
						$child = $child_themes['content'][ $i ];
						if ( ! empty( $child['image'] ) ) {
							echo '<div class="coup-lite-about-child-theme">';
							echo '<div class="coup-lite-about-page-child-theme-image">';
							echo '<img src="' . esc_url( $child['image'] ) . '" alt="' . ( ! empty( $child['image_alt'] ) ? esc_html( $child['image_alt'] ) : '' ) . '" />';
							if ( ! empty( $child['title'] ) ) {
								echo '<div class="coup-lite-about-page-child-theme-details">';
								if ( $child['title'] != $this->theme_name ) {
									echo '<div class="theme-details">';
									echo '<span class="theme-name">' . esc_html($child['title']) . '</span>';
									if ( ! empty( $child['download_link'] ) && ! empty( $child_themes['download_button_label'] ) ) {
										echo '<a href="' . esc_url( $child['download_link'] ) . '" class="button button-primary install right">' . esc_html( $child_themes['download_button_label'] ) . '</a>';
									}
									if ( ! empty( $child['preview_link'] ) && ! empty( $child_themes['preview_button_label'] ) ) {
										echo '<a class="button button-secondary preview right" target="_blank" href="' . esc_html($child['preview_link']) . '">' . esc_html( $child_themes['preview_button_label'] ) . '</a>';
									}
									echo '</div>';
								}
								echo '</div>';
							}
							echo '</div><!--coup-lite-about-page-child-theme-image-->';
							echo '</div><!--coup-lite-about-child-theme-->';
						}// End if().
					}// End for().
					echo '</div>';
				}// End if().
			}// End if().
			echo '</div>';
		}

		/**
		 * Support tab
		 */
		public function support() {
			echo '<div class="feature-section three-col">';

			if ( ! empty( $this->config['support_content'] ) ) {

				$support_steps = $this->config['support_content'];

				if ( ! empty( $support_steps ) ) {

					foreach ( $support_steps as $support_step ) {

						echo '<div class="col">';

						if ( ! empty( $support_step['title'] ) ) {
							echo '<h3>';
							if ( ! empty( $support_step['icon'] ) ) {
								echo '<i class="' . esc_html($support_step['icon']) . '"></i>';
							}
							echo esc_html($support_step['title']);
							echo '</h3>';
						}

						if ( ! empty( $support_step['text'] ) ) {
							echo '<p><i>' . esc_html($support_step['text']) . '</i></p>';
						}

						if ( ! empty( $support_step['button_link'] ) && ! empty( $support_step['button_label'] ) ) {

							echo '<p>';
							$button_class = '';
							if ( $support_step['is_button'] ) {
								$button_class = 'button button-primary';
							}

							$button_new_tab = '_self';
							if ( isset( $support_step['is_new_tab'] ) ) {
								if ( $support_step['is_new_tab'] ) {
									$button_new_tab = '_blank';
								}
							}
							echo '<a target="' . esc_html($button_new_tab) . '" href="' . esc_html($support_step['button_link']) . '"class="' . esc_attr($button_class) . '">' . esc_html($support_step['button_label']) . '</a>';
							echo '</p>';
						}

						echo '</div>';

					}

				}

			}

			echo '</div>';
		}

		/**
		 * Changelog tab
		 */
		public function changelog() {
			$changelog = $this->parse_changelog();
			if ( ! empty( $changelog ) ) {
				echo '<div class="featured-section changelog">';
				foreach ( $changelog as $release ) {
					if ( ! empty( $release['title'] ) ) {
						echo '<h2>' . esc_html($release['title']) . ' </h2 > ';
					}
					if ( ! empty( $release['date'] ) ) {
						echo '<small>' . esc_html($release['date']) . ' </small ><br/>';
					}
					if ( ! empty( $release['changes'] ) ) {
						echo '<p>' . wp_kses_post(implode( '<br/>', $release['changes'] )) . '</p>';
					}
				}
				echo '</div><!-- .featured-section.changelog -->';
			}
		}

		/**
		 * Return the releases changes array.
		 *
		 * @return array The releases array.
		 */
		private function parse_changelog() {
			WP_Filesystem();
			global $wp_filesystem;
			$changelog = $wp_filesystem->get_contents( get_template_directory() . '/CHANGELOG.md' );
			if ( is_wp_error( $changelog ) ) {
				$changelog = '';
			}
			$changelog = explode( PHP_EOL, $changelog );
			$releases  = array();
			foreach ( $changelog as $changelog_line ) {
				if ( strpos( $changelog_line, 'Changes:' ) !== false || empty( $changelog_line ) ) {
					continue;
				}
				if ( substr( $changelog_line, 0, 3 ) === '###' ) {
					if ( isset( $release ) ) {
						$releases[] = $release;
					}
					$release = array(
						'title'   => substr( $changelog_line, 3 ),
						'changes' => array(),
					);
				} else if ( substr( $changelog_line, 0, 2 ) == '--' ) {
					if ( isset( $release ) ) {
						$releases[] = $release;
					}
					$release = array(
						'date'   => substr( $changelog_line, 3 ),
						'changes' => array(),
					);
				} else {
					$release['changes'][] = $changelog_line;
				}
			}

			return $releases;
		}

		/**
		 * Free vs PRO tab
		 */
		public function free_pro() {
			$free_pro = isset( $this->config['free_pro'] ) ? $this->config['free_pro'] : array();
			if ( ! empty( $free_pro ) ) {
				if ( ! empty( $free_pro['free_theme_name'] ) && ! empty( $free_pro['pro_theme_name'] ) && ! empty( $free_pro['features'] ) && is_array( $free_pro['features'] ) ) {
					echo '<div class="feature-section">';
					echo '<div id="free_pro" class="coup-lite-about-page-tab-pane coup-lite-about-page-fre-pro">';
					echo '<table class="free-pro-table">';
					echo '<thead>';
					echo '<tr>';
					echo '<th></th>';
					echo '<th>' . esc_html( $free_pro['free_theme_name'] ) . '</th>';
					echo '<th>' . esc_html( $free_pro['pro_theme_name'] ) . '</th>';
					echo '<th>' . esc_html( $free_pro['shop_theme_name'] ) . '</th>';
					echo '</tr>';
					echo '</thead>';
					echo '<tbody>';
					foreach ( $free_pro['features'] as $feature ) {
						echo '<tr>';
						if ( ! empty( $feature['title'] ) || ! empty( $feature['description'] ) ) {
							echo '<td>';
							if ( ! empty( $feature['title'] ) ) {
								echo '<h3>' . wp_kses_post( $feature['title'] ) . '</h3>';
							}
							if ( ! empty( $feature['description'] ) ) {
								echo '<p>' . wp_kses_post( $feature['description'] ) . '</p>';
							}
							echo '</td>';
						}
						if ( ! empty( $feature['is_in_lite'] ) && ( $feature['is_in_lite'] == 'true' ) ) {
							echo '<td class="only-lite"><span class="dashicons-before dashicons-yes"></span></td>';
						} else {
							echo '<td class="only-pro"><span class="dashicons-before dashicons-no-alt"></span></td>';
						}
						if ( ! empty( $feature['is_in_pro'] ) && ( $feature['is_in_pro'] == 'true' ) ) {
							echo '<td class="only-lite"><span class="dashicons-before dashicons-yes"></span></td>';
						} else {
							echo '<td class="only-pro"><span class="dashicons-before dashicons-no-alt"></span></td>';
						}
						if ( ! empty( $feature['is_in_shop'] ) && ( $feature['is_in_shop'] == 'true' ) ) {
							echo '<td class="only-lite"><span class="dashicons-before dashicons-yes"></span></td>';
						} else {
							echo '<td class="only-pro"><span class="dashicons-before dashicons-no-alt"></span></td>';
						}
						echo '</tr>';

					}
					if ( ! empty( $free_pro['pro_theme_link'] ) && ! empty( $free_pro['get_pro_theme_label'] ) ) {
						echo '<tr class="coup-lite-about-page-text-right">';
						echo '<td></td>';
						echo '<td></td>';
						echo '<td><a href="' . esc_url( $free_pro['pro_theme_link'] ) . '" target="_blank" class="button button-primary button-hero">' . wp_kses_post( $free_pro['get_pro_theme_label'] ) . '</a></td>';
						echo '<td><a href="' . esc_url( $free_pro['shop_theme_link'] ) . '" target="_blank" class="button button-primary button-hero">' . wp_kses_post( $free_pro['get_shop_theme_label'] ) . '</a></td>';
						echo '</tr>';
					}
					echo '</tbody>';
					echo '</table>';
					echo '</div>';
					echo '</div>';

				}
			}
		}

		/**
		 * Load css and scripts for the about page
		 */
		public function style_and_scripts( $hook_suffix ) {

			if ( 'appearance_page_' . $this->theme_slug . '-welcome' == $hook_suffix ) {

				wp_enqueue_style( 'coup-lite-about-page-css', get_template_directory_uri() . '/about-page/css/coup_lite_about_page_css.css' );

				wp_enqueue_script( 'coup-lite-about-page-js', get_template_directory_uri() . '/about-page/js/coup_lite_about_page_scripts.js', array( 'jquery' ) );

				wp_enqueue_style( 'plugin-install' );
				wp_enqueue_script( 'plugin-install' );
				wp_enqueue_script( 'updates' );

				$recommended_actions         = isset( $this->config['recommended_actions'] ) ? $this->config['recommended_actions'] : array();
				$required_actions = $this->get_required_actions();
				wp_localize_script( 'coup-lite-about-page-js', 'coupAboutPageObject', array(
					'nr_actions_required'      => count( $required_actions ),
					'ajaxurl'                  => admin_url( 'admin-ajax.php' ),
					'template_directory'       => get_template_directory_uri(),
					'activating_string'        => __( 'Activating', 'coup' )
				) );

			}

		}

		/**
		 * Return the valid array of required actions.
		 *
		 * @return array The valid array of required actions.
		 */
		private function get_required_actions() {
			$saved_actions = get_option( $this->theme_slug . '_required_actions' );
			if ( ! is_array( $saved_actions ) ) {
				$saved_actions = array();
			}
			$req_actions = isset( $this->config['recommended_actions'] ) ? $this->config['recommended_actions'] : array();
			$valid       = array();
			foreach ( $req_actions['content'] as $req_action ) {
				if ( ( ! isset( $req_action['check'] ) || ( isset( $req_action['check'] ) && ( $req_action['check'] == false ) ) ) && ( ! isset( $saved_actions[ $req_action['id'] ] ) ) ) {
					$valid[] = $req_action;
				}
			}

			return $valid;
		}

		/**
		 * Dismiss required actions
		 */
		public function dismiss_required_action_callback() {

			$recommended_actions = array();
			$req_actions = isset( $this->config['recommended_actions'] ) ? $this->config['recommended_actions'] : array();
			foreach ( $req_actions['content'] as $req_action ) {
				$recommended_actions[] = $req_action;
			}

			$action_id = isset( $_GET['id']) ? sanitize_text_field(wp_unslash($_GET['id'])) : 0;

			echo esc_html( wp_unslash( $action_id ) ); /* this is needed and it's the id of the dismissable required action */

			if ( ! empty( $action_id ) ) {

				/* if the option exists, update the record for the specified id */
				if ( get_option( $this->theme_slug . '_required_actions' ) ) {

					$coup_lite_about_page_show_required_actions = get_option( $this->theme_slug . '_required_actions' );

					$coup_lite_about_todo = isset( $_GET['todo'] ) ? sanitize_text_field(wp_unslash($_GET['todo'])) : 'dismiss';

					switch ( $coup_lite_about_todo ) {
						case 'add';
							$coup_lite_about_page_show_required_actions[ absint( $action_id ) ] = true;
							break;
						case 'dismiss';
							$coup_lite_about_page_show_required_actions[ absint( $action_id ) ] = false;
							break;
					}

					update_option( $this->theme_slug . '_required_actions', $coup_lite_about_page_show_required_actions );

					/* create the new option,with false for the specified id */
				} else {

					$coup_lite_about_page_show_required_actions_new = array();

					if ( ! empty( $recommended_actions ) ) {

						foreach ( $recommended_actions as $coup_lite_about_page_required_action ) {

							if ( $coup_lite_about_page_required_action['id'] == $action_id ) {
								$coup_lite_about_page_show_required_actions_new[ $coup_lite_about_page_required_action['id'] ] = false;
							} else {
								$coup_lite_about_page_show_required_actions_new[ $coup_lite_about_page_required_action['id'] ] = true;
							}

						}

						update_option( $this->theme_slug . '_required_actions', $coup_lite_about_page_show_required_actions_new );

					}

				}

			}
		}

	}
}
