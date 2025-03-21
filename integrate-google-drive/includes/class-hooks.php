<?php

namespace IGD;

defined( 'ABSPATH' ) || exit;
class Hooks {
    /**
     * @var null
     */
    protected static $instance = null;

    public function __construct() {
        // Handle uninstall
        igd_fs()->add_action( 'after_uninstall', [$this, 'uninstall'] );
        // Set custom app credentials
        $clientID = igd_get_settings( 'clientID' );
        $clientSecret = igd_get_settings( 'clientSecret' );
        $ownApp = igd_get_settings( 'ownApp' );
        if ( !empty( $ownApp ) && !empty( $clientID ) && !empty( $clientSecret ) ) {
            add_filter( 'igd/client_id', function () use($clientID) {
                return $clientID;
            } );
            add_filter( 'igd/client_secret', function () use($clientSecret) {
                return $clientSecret;
            } );
            add_filter( 'igd/redirect_uri', function () {
                return admin_url( '?action=integrate-google-drive-authorization' );
            } );
        }
        // IGD render form upload field data
        add_filter(
            'igd_render_form_field_data',
            [$this, 'render_form_field_data'],
            10,
            2
        );
        // Handle oAuth authorization
        add_action( 'admin_init', [$this, 'handle_authorization'] );
        // Register query var
        add_filter( 'query_vars', [$this, 'add_query_vars'] );
        // Get preview thumbnail
        add_action( 'template_redirect', [$this, 'preview_image'] );
        add_action( 'template_redirect', [$this, 'direct_content'] );
        add_action( 'template_redirect', [$this, 'secure_embed'] );
        // Handle direct download
        add_action( 'template_redirect', [$this, 'direct_download'] );
        // Handle direct stream
        add_action( 'template_redirect', [$this, 'direct_stream'] );
        // Update lost account
        add_action( 'igd_lost_authorization_notice', [$this, 'update_lost_account'] );
    }

    public function update_lost_account( $account_id = null ) {
        if ( !$account_id ) {
            $account_id = igd_get_active_account_id();
        }
        $account = Account::instance()->get_accounts( $account_id );
        if ( empty( $account ) ) {
            return;
        }
        $account['lost'] = true;
        Account::instance()->update_account( $account );
    }

    public function direct_download() {
        if ( get_query_var( 'igd_download' ) ) {
            $file_id = ( !empty( $_REQUEST['id'] ) ? sanitize_text_field( $_REQUEST['id'] ) : '' );
            $file_ids = ( !empty( $_REQUEST['file_ids'] ) ? json_decode( base64_decode( sanitize_text_field( $_REQUEST['file_ids'] ) ) ) : [] );
            $account_id = ( !empty( $_REQUEST['accountId'] ) ? sanitize_text_field( $_REQUEST['accountId'] ) : '' );
            $mimetype = ( !empty( $_REQUEST['mimetype'] ) ? sanitize_text_field( $_REQUEST['mimetype'] ) : 'default' );
            $ignore_limit = !empty( $_REQUEST['ignore_limit'] );
            if ( !empty( $file_ids ) ) {
                $request_id = ( !empty( $_REQUEST['id'] ) ? sanitize_text_field( $_REQUEST['id'] ) : '' );
                igd_download_zip( $file_ids, $request_id, $account_id );
            } elseif ( !empty( $file_id ) ) {
                Download::instance(
                    $file_id,
                    $account_id,
                    $mimetype,
                    false,
                    $ignore_limit
                )->start_download();
            }
            exit;
        }
    }

    public function direct_stream() {
        if ( get_query_var( 'igd_stream' ) ) {
            $file_id = ( !empty( $_REQUEST['id'] ) ? sanitize_text_field( $_REQUEST['id'] ) : '' );
            $account_id = ( !empty( $_REQUEST['account_id'] ) ? sanitize_text_field( $_REQUEST['account_id'] ) : '' );
            Stream::instance( $file_id, $account_id )->stream_content();
            exit;
        }
    }

    public function add_query_vars( $vars ) {
        $vars[] = 'igd_preview_image';
        $vars[] = 'igd_download';
        $vars[] = 'igd_stream';
        $vars[] = 'direct_file';
        $vars[] = 'secure_embed';
        return $vars;
    }

    public function preview_image() {
        // Check if the query variable exists
        if ( !get_query_var( 'igd_preview_image' ) ) {
            return;
        }
        // Sanitize and validate input
        $id = sanitize_text_field( ( isset( $_GET['id'] ) ? $_GET['id'] : '' ) );
        $account_id = sanitize_text_field( ( isset( $_GET['accountId'] ) ? $_GET['accountId'] : '' ) );
        $size = sanitize_key( ( isset( $_GET['size'] ) ? $_GET['size'] : 'medium' ) );
        $w = ( isset( $_GET['w'] ) ? intval( $_GET['w'] ) : 300 );
        $h = ( isset( $_GET['h'] ) ? intval( $_GET['h'] ) : 300 );
        if ( empty( $id ) ) {
            header( "HTTP/1.1 400 Bad Request" );
            echo "File ID is required.";
            exit;
        }
        // Create app instance and fetch file
        $app = App::instance( $account_id );
        $file = $app->get_file_by_id( $id );
        if ( !$file ) {
            header( "HTTP/1.1 404 Not Found" );
            echo "File not found.";
            exit;
        }
        // Caching headers: Last-Modified and ETag
        $lastModified = strtotime( $file['updated'] );
        $etagFile = md5( $lastModified );
        $ifModifiedSince = ( isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false );
        $etagHeader = ( isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) ? trim( $_SERVER['HTTP_IF_NONE_MATCH'] ) : '' );
        if ( $ifModifiedSince && strtotime( $ifModifiedSince ) === $lastModified || $etagHeader === $etagFile ) {
            header( 'HTTP/1.1 304 Not Modified' );
            exit;
        }
        header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $lastModified ) . ' GMT' );
        header( "Etag: {$etagFile}" );
        header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + 300 ) . ' GMT' );
        header( 'Cache-Control: must-revalidate' );
        // Determine thumbnail attributes
        switch ( $size ) {
            case 'custom':
                $thumbnail_attributes = "=w{$w}-h{$h}";
                break;
            case 'small':
                $thumbnail_attributes = '=w300-h300';
                break;
            case 'medium':
                $thumbnail_attributes = '=h600-nu';
                break;
            case 'large':
                $thumbnail_attributes = '=w1024-h768-p-k-nu';
                break;
            case 'full':
                $thumbnail_attributes = '=s0';
                break;
            default:
                $thumbnail_attributes = '=w200-h190-p-k-nu-iv1';
                break;
        }
        $thumbnail_file = $id . $thumbnail_attributes . '.png';
        $thumbnail_path = IGD_CACHE_DIR . '/' . $thumbnail_file;
        // Serve cached thumbnail if available
        if ( file_exists( $thumbnail_path ) && filemtime( $thumbnail_path ) === $lastModified ) {
            $img_info = getimagesize( $thumbnail_path );
            if ( $img_info ) {
                header( "Content-Type: {$img_info['mime']}" );
                readfile( $thumbnail_path );
                exit;
            }
        }
        // Fetch thumbnail from external source
        $download_link = "https://lh3.google.com/u/0/d/{$id}{$thumbnail_attributes}";
        try {
            $client = $app->client;
            $request = new \IGDGoogle_Http_Request($download_link, 'GET');
            $client->getIo()->setOptions( [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_FOLLOWLOCATION => true,
            ] );
            $httpRequest = $client->getAuth()->authenticatedRequest( $request );
            $headers = $httpRequest->getResponseHeaders();
            // Validate response content type
            $content_type = ( isset( $headers['content-type'] ) ? $headers['content-type'] : '' );
            if ( strpos( $content_type, 'image' ) === false ) {
                header( "HTTP/1.1 400 Bad Request" );
                echo "Invalid response type.";
                exit;
            }
            // Save and serve the thumbnail
            file_put_contents( $thumbnail_path, $httpRequest->getResponseBody() );
            touch( $thumbnail_path, $lastModified );
            $img_info = getimagesize( $thumbnail_path );
            if ( $img_info ) {
                header( "Content-Type: {$img_info['mime']}" );
                readfile( $thumbnail_path );
                exit;
            }
            header( "HTTP/1.1 500 Internal Server Error" );
            echo "Failed to process the thumbnail.";
            exit;
        } catch ( \Exception $e ) {
            header( "HTTP/1.1 500 Internal Server Error" );
            echo "Error: " . esc_html( $e->getMessage() );
            exit;
        }
    }

    public function handle_authorization() {
        if ( empty( $_GET['action'] ) ) {
            return;
        }
        if ( 'authorization' == sanitize_key( $_GET['action'] ) ) {
            $client = Client::instance();
            $client->create_access_token();
            echo '<script type="text/javascript">window.opener.parent.location.reload(); window.close();</script>';
            exit;
        }
    }

    public function create_user_folder( $user_id ) {
        $allowed_user_roles = igd_get_settings( 'privateFolderRoles', ['editor', 'contributor', 'author'] );
        // Check if user role is allowed
        if ( !in_array( 'all', $allowed_user_roles ) ) {
            $user = get_user_by( 'id', $user_id );
            if ( !in_array( $user->roles[0], $allowed_user_roles ) ) {
                return;
            }
        }
        Private_Folders::instance()->create_user_folder( $user_id );
    }

    public function delete_user_folder( $user_id ) {
        Private_Folders::instance()->delete_user_folder( $user_id );
    }

    public function direct_content() {
        if ( $direct_file = get_query_var( 'direct_file' ) ) {
            $file = json_decode( base64_decode( $direct_file ), true );
            if ( empty( $file['id'] ) ) {
                wp_die( 'Invalid file ID', 400 );
            }
            $file_id = $file['id'];
            $account_id = $file['accountId'] ?? '';
            // Retrieve the file and check permissions
            $file = App::instance( $account_id )->get_file_by_id( $file_id );
            $permissions = Permissions::instance( $account_id );
            if ( !igd_is_dir( $file ) && !$permissions->has_permission( $file ) ) {
                $permissions->set_permission( $file );
            }
            $is_dir = igd_is_dir( $file );
            add_filter( 'show_admin_bar', '__return_false' );
            // Remove all WordPress actions
            remove_all_actions( 'wp_head' );
            remove_all_actions( 'wp_print_styles' );
            remove_all_actions( 'wp_print_head_scripts' );
            remove_all_actions( 'wp_footer' );
            // Handle `wp_head`
            add_action( 'wp_head', 'wp_enqueue_scripts', 1 );
            add_action( 'wp_head', 'wp_print_styles', 8 );
            add_action( 'wp_head', 'wp_print_head_scripts', 9 );
            add_action( 'wp_head', 'wp_site_icon' );
            // Handle `wp_footer`
            add_action( 'wp_footer', 'wp_print_footer_scripts', 20 );
            // Handle `wp_enqueue_scripts`
            remove_all_actions( 'wp_enqueue_scripts' );
            // Also remove all scripts hooked into after_wp_tiny_mce.
            remove_all_actions( 'after_wp_tiny_mce' );
            if ( $is_dir ) {
                Enqueue::instance()->frontend_scripts();
            }
            $type = ( $is_dir ? 'browser' : 'embed' );
            ?>

            <!doctype html>
            <html lang="<?php 
            language_attributes();
            ?>">
            <head>
                <meta charset="<?php 
            bloginfo( 'charset' );
            ?>">
                <meta name="viewport"
                      content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
                <meta http-equiv="X-UA-Compatible" content="ie=edge">
                <title><?php 
            echo esc_html( $file['name'] );
            ?></title>

				<?php 
            wp_enqueue_style( 'google-font-roboto', 'https://fonts.googleapis.com/css?family=Roboto:400,500,700&display=swap' );
            ?>

				<?php 
            do_action( 'wp_head' );
            ?>

				<?php 
            if ( 'embed' == $type ) {
                ?>
                    <style>
                        html, body {
                            margin: 0;
                            padding: 0;
                            width: 100%;
                            height: 100%;
                        }

                        #igd-direct-content {
                            width: 100%;
                            height: 100vh;
                            overflow: hidden;
                            position: relative;
                        }

                        #igd-direct-content .igd-embed {
                            width: 100%;
                            height: 100%;
                            border: none;
                        }
                    </style>
				<?php 
            }
            ?>

            </head>
            <body>

            <div id="igd-direct-content">
				<?php 
            $data = [
                'folders'            => [$file],
                'type'               => $type,
                'allowPreviewPopout' => false,
            ];
            echo Shortcode::instance()->render_shortcode( [], $data );
            ?>
            </div>

			<?php 
            do_action( 'wp_footer' );
            ?>

            </body>
            </html>

			<?php 
            exit;
        }
    }

    public function secure_embed() {
        // Check if the query variable 'secure_embed' is set
        if ( !get_query_var( 'secure_embed' ) ) {
            return;
        }
        // Validate the referer to ensure the request originates from your website
        $referer = ( isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '' );
        $home_url = esc_url_raw( home_url() );
        // Deny access if the referer is not from your website
        if ( strpos( $referer, $home_url ) !== 0 ) {
            wp_die( esc_html__( 'Sorry, you are not allowed to access this page directly.', 'integrate-google-drive' ), 403 );
        }
        // Validate nonce for security
        $nonce = ( isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '' );
        if ( !wp_verify_nonce( $nonce, 'igd' ) ) {
            wp_die( esc_html__( 'Invalid embed URL.', 'integrate-google-drive' ), 400 );
        }
        // Ensure 'secure_embed' and 'id' parameters are provided
        $secure_embed = sanitize_text_field( $_REQUEST['secure_embed'] ?? '' );
        $encoded_file_id = sanitize_text_field( $_REQUEST['id'] ?? '' );
        if ( empty( $secure_embed ) || empty( $encoded_file_id ) ) {
            wp_die( esc_html__( 'Invalid embed URL.', 'integrate-google-drive' ), 400 );
        }
        // Decode and validate the file ID and account ID
        $file_id = base64_decode( $encoded_file_id, true );
        $encoded_account_id = sanitize_text_field( $_REQUEST['account_id'] ?? '' );
        $account_id = ( !empty( $encoded_account_id ) ? base64_decode( $encoded_account_id, true ) : '' );
        if ( !$file_id ) {
            wp_die( esc_html__( 'Invalid embed URL.', 'integrate-google-drive' ), 400 );
        }
        // Retrieve the file and check permissions
        $file = App::instance( $account_id )->get_file_by_id( $file_id );
        $permissions = Permissions::instance( $account_id );
        if ( !$permissions->has_permission( $file ) ) {
            $permissions->set_permission( $file );
        }
        // Redirect to the secure embed URL
        $redirect_url = "https://drive.google.com/file/d/{$file_id}/preview";
        wp_redirect( esc_url_raw( $redirect_url ) );
        exit;
    }

    public function render_form_field_data( $data, $as_html ) {
        $uploaded_files = json_decode( $data, 1 );
        if ( empty( $uploaded_files ) ) {
            return $data;
        }
        $file_count = count( $uploaded_files );
        // Render TEXT only
        if ( !$as_html ) {
            $formatted_value = sprintf( _n(
                '%d file uploaded to Google Drive',
                '%d files uploaded to Google Drive',
                $file_count,
                'integrate-google-drive'
            ), $file_count );
            $formatted_value .= "\r\n";
            foreach ( $uploaded_files as $file ) {
                $view_link = sprintf( 'https://drive.google.com/file/d/%1$s/view', $file['id'] );
                $formatted_value .= $file['name'] . " - (" . $view_link . "), \r\n";
            }
            return $formatted_value;
        }
        $heading = sprintf( '<h3 style="margin-bottom: 15px;">%s</h3>', sprintf( 
            // translators: %d: number of files
            _n(
                '%d file uploaded to Google Drive',
                '%d files uploaded to Google Drive',
                $file_count,
                'integrate-google-drive'
            ),
            $file_count
         ) );
        // Render HTML
        ob_start();
        echo $heading;
        foreach ( $uploaded_files as $file ) {
            $file_url = sprintf( 'https://drive.google.com/file/d/%1$s/view', $file['id'] );
            ?>
            <div style="display: block; margin-bottom: 5px;font-weight: 600;">
				<?php 
            echo esc_html( $file['name'] );
            ?> -
                <a style="text-decoration: none;font-weight: 400;"
                   href="<?php 
            echo esc_url_raw( $file_url );
            ?>"
                   target="_blank"><?php 
            echo esc_url_raw( $file_url );
            ?></a>
            </div>
		<?php 
        }
        //Remove any newlines
        return trim( preg_replace( '/\\s+/', ' ', ob_get_clean() ) );
    }

    public function uninstall() {
        // Remove cron
        $timestamp = wp_next_scheduled( 'igd_sync_interval' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'igd_sync_interval' );
        }
        //Delete data
        if ( igd_get_settings( 'deleteData', false ) ) {
            delete_option( 'igd_tokens' );
            delete_option( 'igd_accounts' );
            delete_option( 'igd_settings' );
            delete_option( 'igd_cached_folders' );
            igd_delete_cache();
            // Clear Attachments
            Ajax::instance()->clear_attachments();
        }
    }

    /**
     * @return Hooks|null
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

}

Hooks::instance();