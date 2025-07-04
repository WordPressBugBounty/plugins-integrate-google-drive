<?php

namespace IGD;

defined( 'ABSPATH' ) || exit;
class Elementor {
    /**
     * @var null
     */
    protected static $instance = null;

    public function __construct() {
        add_action( 'elementor/editor/after_enqueue_scripts', [$this, 'editor_scripts'] );
        add_action( 'elementor/preview/enqueue_scripts', [$this, 'preview_scripts'] );
        add_action( 'elementor/frontend/before_enqueue_scripts', [$this, 'frontend_scripts'] );
        // Register default widgets
        add_action( 'elementor/elements/categories_registered', [$this, 'add_categories'] );
        if ( defined( 'ELEMENTOR_VERSION' ) ) {
            if ( version_compare( ELEMENTOR_VERSION, '3.5.0', '>=' ) ) {
                add_action( 'elementor/widgets/register', [$this, 'register_widgets'] );
            } else {
                add_action( 'elementor/widgets/widgets_registered', array($this, 'register_widgets') );
            }
        }
    }

    public function elementor_form_may_create_entry_folder( $record ) {
        $fields = $record->get( 'fields' );
        // Return early if fields are empty
        if ( empty( $fields ) ) {
            return;
        }
        // Prepare igd_fields array and populate tags array with field values
        $igd_fields = [];
        foreach ( $fields as $field ) {
            if ( $field['type'] == 'google_drive_upload' ) {
                $igd_fields[$field['id']] = $field;
            }
        }
        if ( !empty( $igd_fields ) ) {
            $form_fields = $record->get_form_settings( 'form_fields' );
            foreach ( $igd_fields as $id => $field ) {
                $field_data_key = array_search( $id, array_column( $form_fields, 'custom_id' ) );
                if ( $field_data_key === false ) {
                    continue;
                    // Exit early if field not found
                }
                $value = $field['value'];
                if ( empty( $value ) ) {
                    continue;
                }
                $files = [];
                // Fetch file ids from the value text
                preg_match_all( '/file\\/d\\/(.*?)\\/view/', $value, $matches );
                $file_ids = $matches[1];
                if ( empty( $file_ids ) ) {
                    continue;
                }
                foreach ( $file_ids as $file_id ) {
                    $files[] = App::instance()->get_file_by_id( $file_id );
                }
                if ( empty( $files ) ) {
                    continue;
                    // Exit early if no files
                }
                $field_data = $form_fields[$field_data_key];
                $module_data = Shortcode::get_shortcode( $field_data['module_id'] )['config'] ?? [];
                $tag_data = [
                    'form' => [
                        'form_title' => $record->get_form_settings( 'form_name' ),
                        'form_id'    => get_the_ID(),
                    ],
                ];
                $upload_folder = ( !empty( $module_data['folders'] ) && is_array( $module_data['folders'] ) ? reset( $module_data['folders'] ) : [
                    'id'        => 'root',
                    'accountId' => '',
                ] );
                // Rename files
                $extra_tags = $this->handle_elementor_form_field_tags( $fields );
                $rename_files = [];
                foreach ( $files as $file ) {
                    // We will rename the file name
                    $tag_data['name'] = $file['name'];
                    $name = igd_replace_template_tags( $tag_data, $extra_tags );
                    $rename_files[] = [
                        'id'   => $file['id'],
                        'name' => $name,
                    ];
                }
                if ( !empty( $rename_files ) ) {
                    App::instance( $upload_folder['accountId'] )->rename_files( $rename_files );
                }
                // Create Entry Folder
                $create_entry_folder = !empty( $module_data['createEntryFolders'] );
                $create_private_folder = !empty( $module_data['createPrivateFolder'] );
                if ( !$create_entry_folder && !$create_private_folder ) {
                    continue;
                }
                $entry_folder_name_template = $module_data['entryFolderNameTemplate'] ?? 'Entry (%entry_id%) - %form_title% ';
                if ( false !== strpos( $entry_folder_name_template, '%entry_id%' ) ) {
                    $submit_actions = $record->get_form_settings( 'submit_actions' );
                    if ( in_array( 'save-to-database', $submit_actions ) ) {
                        global $wpdb;
                        $table = "{$wpdb->prefix}e_submissions";
                        $submission_id = $wpdb->get_var( "SELECT MAX(ID) FROM {$table}" );
                        $tag_data['form']['entry_id'] = $submission_id;
                    }
                }
                if ( igd_contains_tags( 'user', $entry_folder_name_template ) ) {
                    if ( is_user_logged_in() ) {
                        $tag_data['user'] = get_userdata( get_current_user_id() );
                    }
                }
                if ( igd_contains_tags( 'post', $entry_folder_name_template ) ) {
                    $referrer = wp_get_referer();
                    if ( !empty( $referrer ) ) {
                        $post_id = url_to_postid( $referrer );
                        if ( !empty( $post_id ) ) {
                            $tag_data['post'] = get_post( $post_id );
                            if ( $tag_data['post']->post_type == 'product' ) {
                                $tag_data['wc_product'] = wc_get_product( $post_id );
                            }
                        }
                    }
                }
                $tag_data['name'] = $entry_folder_name_template;
                $folder_name = igd_replace_template_tags( $tag_data, $extra_tags );
                // Check Private Folders
                $private_folders = !empty( $module_data['privateFolders'] );
                if ( $private_folders && is_user_logged_in() ) {
                    $folders = get_user_meta( get_current_user_id(), 'igd_folders', true );
                    if ( !empty( $folders ) ) {
                        $folders = array_values( array_filter( (array) $folders, function ( $item ) {
                            return igd_is_dir( $item );
                        } ) );
                    } elseif ( $create_private_folder ) {
                        $folders = Private_Folders::instance()->create_user_folder( get_current_user_id(), $module_data );
                    }
                    if ( !empty( $folders ) ) {
                        $module_data['folders'] = $folders;
                    }
                }
                $merge_folders = ( isset( $module_data['mergeFolders'] ) ? filter_var( $module_data['mergeFolders'], FILTER_VALIDATE_BOOLEAN ) : false );
                Uploader::instance( $upload_folder['accountId'] )->create_entry_folder_and_move(
                    $files,
                    $folder_name,
                    $upload_folder,
                    $merge_folders,
                    $create_entry_folder
                );
            }
        }
    }

    private function handle_elementor_form_field_tags( $fields ) {
        $extra_tags = [];
        if ( !empty( $fields ) ) {
            foreach ( $fields as $field ) {
                if ( $field['type'] == 'google_drive_upload' ) {
                    continue;
                }
                $field_id = $field['id'];
                $field_value = $field['value'];
                // Handle array values, such as checkboxes
                if ( is_array( $field_value ) ) {
                    $field_value = implode( ', ', $field_value );
                }
                $extra_tags['%' . $field_id . '%'] = $field_value;
            }
        }
        return $extra_tags;
    }

    public function metform_may_create_entry_folder(
        $form_id,
        $form_data,
        $form_settings,
        $attributes
    ) {
        $input_widgets = \Metform\Widgets\Manifest::instance()->get_input_widgets();
        $widget_input_data = get_post_meta( $form_id, '_elementor_data', true );
        $widget_input_data = json_decode( $widget_input_data );
        $widgets = \MetForm\Core\Entries\Map_El::data( $widget_input_data, $input_widgets )->get_el();
        // Return early if fields are empty
        if ( empty( $widgets ) ) {
            return;
        }
        // Prepare igd_fields array and populate tags array with field values
        $igd_widgets = [];
        foreach ( $widgets as $key => $field ) {
            if ( $field->widgetType == 'mf-igd-uploader' ) {
                $igd_widgets[$key] = $field;
            }
        }
        if ( empty( $igd_widgets ) ) {
            return;
        }
        foreach ( $igd_widgets as $key => $widget ) {
            if ( !isset( $form_data[$key] ) ) {
                continue;
            }
            $value = $form_data[$key];
            if ( empty( $value ) ) {
                continue;
            }
            $files = [];
            // Fetch file ids from the value text
            preg_match_all( '/file\\/d\\/(.*?)\\/view/', $value, $matches );
            $file_ids = $matches[1];
            if ( empty( $file_ids ) ) {
                continue;
            }
            foreach ( $file_ids as $file_id ) {
                $files[] = App::instance()->get_file_by_id( $file_id );
            }
            $module_data = Shortcode::get_shortcode( $widget->module_id )['config'] ?? [];
            $tag_data = [
                'form' => [
                    'form_title' => $form_settings['form_title'],
                    'form_id'    => $form_id,
                ],
            ];
            $upload_folder = ( !empty( $module_data['folders'][0] ) ? $module_data['folders'][0] : [
                'id'        => 'root',
                'accountId' => '',
            ] );
            // Rename files
            $extra_tags = $this->handle_met_form_field_tags( $widgets, $form_data );
            $rename_files = [];
            foreach ( $files as $file ) {
                // We will rename the file name
                $tag_data['name'] = $file['name'];
                $name = igd_replace_template_tags( $tag_data, $extra_tags );
                $rename_files[] = [
                    'id'   => $file['id'],
                    'name' => $name,
                ];
            }
            if ( !empty( $rename_files ) ) {
                App::instance( $upload_folder['accountId'] )->rename_files( $rename_files );
            }
            // Create Entry Folder
            $create_entry_folder = !empty( $module_data['createEntryFolders'] );
            $create_private_folder = !empty( $module_data['createPrivateFolder'] );
            if ( !$create_entry_folder && !$create_private_folder ) {
                continue;
            }
            $entry_folder_name_template = $module_data['entryFolderNameTemplate'] ?? 'Entry (%entry_id%) - %form_title%';
            if ( false !== strpos( $entry_folder_name_template, '%entry_id%' ) ) {
                if ( $form_settings['store_entries'] ) {
                    global $wpdb;
                    $entry_id = $wpdb->get_var( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'metform-entry' ORDER BY ID DESC LIMIT 1" );
                    $tag_data['form']['entry_id'] = $entry_id;
                }
            }
            if ( igd_contains_tags( 'user', $entry_folder_name_template ) ) {
                if ( is_user_logged_in() ) {
                    $tag_data['user'] = get_userdata( get_current_user_id() );
                }
            }
            if ( igd_contains_tags( 'post', $entry_folder_name_template ) ) {
                $referrer = wp_get_referer();
                if ( !empty( $referrer ) ) {
                    $post_id = url_to_postid( $referrer );
                    if ( !empty( $post_id ) ) {
                        $tag_data['post'] = get_post( $post_id );
                        if ( $tag_data['post']->post_type == 'product' ) {
                            $tag_data['wc_product'] = wc_get_product( $post_id );
                        }
                    }
                }
            }
            $tag_data['name'] = $entry_folder_name_template;
            $folder_name = igd_replace_template_tags( $tag_data, $extra_tags );
            // Check Private Folders
            $private_folders = !empty( $module_data['privateFolders'] );
            if ( $private_folders && is_user_logged_in() ) {
                $folders = get_user_meta( get_current_user_id(), 'igd_folders', true );
                if ( !empty( $folders ) ) {
                    $folders = array_values( array_filter( (array) $folders, function ( $item ) {
                        return igd_is_dir( $item );
                    } ) );
                } elseif ( $create_private_folder ) {
                    $folders = Private_Folders::instance()->create_user_folder( get_current_user_id(), $module_data );
                }
                if ( !empty( $folders ) ) {
                    $module_data['folders'] = $folders;
                }
            }
            $merge_folders = ( isset( $module_data['mergeFolders'] ) ? filter_var( $module_data['mergeFolders'], FILTER_VALIDATE_BOOLEAN ) : false );
            Uploader::instance( $upload_folder['accountId'] )->create_entry_folder_and_move(
                $files,
                $folder_name,
                $upload_folder,
                $merge_folders,
                $create_entry_folder
            );
        }
    }

    private function handle_met_form_field_tags( $widgets, $form_data ) {
        $extra_tags = [];
        if ( !empty( $widgets ) ) {
            foreach ( $widgets as $key => $field ) {
                if ( $field->widgetType == 'mf-igd-uploader' ) {
                    continue;
                }
                $field_value = $form_data[$key];
                // Handle array values, such as checkboxes
                if ( is_array( $field_value ) ) {
                    $field_value = implode( ', ', $field_value );
                }
                $extra_tags['%' . $key . '%'] = $field_value;
            }
        }
        return $extra_tags;
    }

    public function metform_register_widgets( $widget_list ) {
        $widget_list[] = 'mf-igd-uploader';
        return $widget_list;
    }

    public function register_form_fields( $fields_manager ) {
        include_once IGD_INCLUDES . '/elementor/class-elementor-form__premium_only.php';
        $fields_manager->register( new Google_Drive_Upload() );
    }

    public function editor_scripts() {
        wp_enqueue_style(
            'igd-elementor-editor',
            IGD_ASSETS . '/css/elementor-editor.css',
            [],
            IGD_VERSION
        );
        wp_style_add_data( 'igd-elementor-editor', 'rtl', 'replace' );
    }

    public function frontend_scripts() {
        Enqueue::instance()->frontend_scripts();
    }

    public function preview_scripts() {
        // Check if select2 is already registered
        wp_enqueue_style(
            'igd-select2',
            IGD_ASSETS . '/vendor/select2/css/select2.min.css',
            [],
            '4.0.13'
        );
        wp_enqueue_script(
            'igd-select2',
            IGD_ASSETS . '/vendor/select2/js/select2.full.min.js',
            ['jquery'],
            '4.0.13',
            true
        );
        Enqueue::instance()->admin_scripts();
        wp_enqueue_script(
            'igd-elementor',
            IGD_ASSETS . '/js/elementor.js',
            [
                'jquery',
                'react',
                'react-dom',
                'wp-components'
            ],
            IGD_VERSION,
            true
        );
    }

    public function register_widgets( $widgets_manager ) {
        include_once IGD_INCLUDES . '/elementor/class-elementor-shortcodes-widget.php';
        if ( method_exists( $widgets_manager, 'register' ) ) {
            $widgets_manager->register( new Shortcodes_Widget() );
        } else {
            $widgets_manager->register_widget_type( new Shortcodes_Widget() );
        }
    }

    public function add_categories( $elements_manager ) {
        $elements_manager->add_category( 'integrate_google_drive', [
            'title' => __( 'Integrate Google Drive', 'integrate-google-drive' ),
            'icon'  => 'fa fa-plug',
        ] );
    }

    public static function builder_empty_placeholder( $type, $module_id = null ) {
        $key = str_replace( 'igd_', '', $type );
        $titles = [
            'igd_shortcodes' => __( 'Google Drive Modules', 'integrate-google-drive' ),
        ];
        $title = $titles[$type] ?? __( 'Google Drive Module', 'integrate-google-drive' );
        $shortcodes = Shortcode::get_shortcodes();
        $description = ( 'shortcodes' == $key ? __( 'Choose a saved module to insert.', 'integrate-google-drive' ) : sprintf( __( 'Choose a saved %s module to insert.', 'integrate-google-drive' ), $title ) );
        $description2 = ( 'shortcodes' == $key ? __( 'Build a new Google Drive module from scratch.', 'integrate-google-drive' ) : sprintf( __( 'Build a new %s module from scratch.', 'integrate-google-drive' ), $title ) );
        ?>

        <div class="igd-module-placeholder">

            <div class="module-icon icon-<?php 
        echo esc_attr( $key );
        ?>">
                <img src="<?php 
        echo esc_url( IGD_ASSETS . '/images/shortcode-builder/types/' . $key . '.svg' );
        ?>">
            </div>

            <h2 class="title"><?php 
        echo esc_html( $title );
        ?></h2>

            <h3 class="subtitle"><?php 
        esc_html_e( 'Insert Existing Module', 'integrate-google-drive' );
        ?></h3>

            <p class="description"><?php 
        echo esc_html( $description );
        ?></p>

            <select class="form-control module_id" name="module_id">

                <option><?php 
        esc_html_e( 'Select Shortcode', 'integrate-google-drive' );
        ?></option>

				<?php 
        foreach ( $shortcodes as $shortcode ) {
            ?>
                    <option
                            value="<?php 
            echo esc_attr( $shortcode['id'] );
            ?>" <?php 
            selected( $module_id, $shortcode['id'] );
            ?>
                            data-image="<?php 
            echo esc_url( IGD_ASSETS . '/images/shortcode-builder/types/' . $shortcode['type'] . '.svg' );
            ?>"
                    >
						<?php 
            echo esc_html( $shortcode['title'] );
            ?>
                    </option>
				<?php 
        }
        ?>
            </select>

			<?php 
        if ( $module_id ) {
            ?>
                <button type="button" class="igd-btn btn-primary btn-configure"
                        onclick="setTimeout(() => { window.parent.jQuery(`[data-event='igd:editor:edit_module']`).trigger('click') }, 100)">
                    <i class="dashicons dashicons-admin-generic"></i>
                    <span><?php 
            esc_html_e( 'Configure Module', 'integrate-google-drive' );
            ?></span>
                </button>
			<?php 
        } else {
            ?>
                <div class="divider"><?php 
            esc_html_e( 'OR', 'integrate-google-drive' );
            ?></div>
                <h3 class="subtitle"><?php 
            esc_html_e( 'Create New Module', 'integrate-google-drive' );
            ?></h3>

                <p class="description"><?php 
            echo esc_html( $description2 );
            ?></p>

                <button type="button" class="igd-btn btn-primary"
                        onclick="setTimeout(() => { window.parent.elementor.channels.editor.trigger('igd:editor:add_module') }, 100)">
                    <i class="dashicons dashicons-plus"></i>
                    <span><?php 
            esc_html_e( 'Add New Module', 'integrate-google-drive' );
            ?></span>
                </button>
			<?php 
        }
        ?>

        </div>
		<?php 
    }

    /**
     * @return Elementor|null
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

}

Elementor::instance();