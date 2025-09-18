<?php
/**
 * Interfaz administrativa principal del plugin WebP
 * 
 * @package SCP_WebP_Converter
 * @version 1.3.0
 */

if (!defined('ABSPATH')) exit;

class SCP_WebP_Admin {
    
    private $converter;
    private $renamer_admin;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->converter = new SCP_WebP_Core_Converter();
        $this->renamer_admin = new SCP_WebP_Renamer_Admin();
        
        // Hooks administrativos
        add_action('admin_menu',  [$this, 'add_settings_page']);
        add_action('admin_init',  [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'admin_assets']);
    }

    /**
     * Añade página de configuración
     */
    public function add_settings_page() {
        add_options_page(
            'SCP WebP Converter',
            'SCP WebP Converter',
            'manage_options',
            'scp-webp-converter',
            [$this, 'render_settings_page']
        );

        // Añadir subpágina para limpieza de huérfanos
        add_submenu_page(
            'options-general.php',
            'Limpieza WebP Huérfanos',
            'WebP Huérfanos',
            'manage_options',
            'scp-webp-orphan-cleaner',
            [$this, 'render_orphan_cleaner_page']
        );
    }

    /**
     * Registra configuraciones
     */
    public function register_settings() {
        SCP_WebP_Config::register_settings();

        add_settings_section('scp_webp_section', 'Ajustes de calidad', function(){
            echo '<p>Configura la calidad de compresión WebP por tipo de imagen.</p>';
        }, 'scp-webp-converter');

        add_settings_section('scp_webp_content_section', 'Procesamiento de contenido', function(){
            echo '<p>Configuración para el procesamiento de imágenes hardcodeadas en el contenido.</p>';
        }, 'scp-webp-converter');

        add_settings_section('scp_webp_extension_section', 'Formato de archivos WebP', function(){
            echo '<p>Configura cómo se nombrarán los archivos WebP generados.</p>';
        }, 'scp-webp-converter');

        add_settings_field(SCP_WebP_Config::OPT_QUALITY_JPEG, 'Calidad WebP para JPEG', function(){
            $val = (int)get_option(SCP_WebP_Config::OPT_QUALITY_JPEG, SCP_WebP_Config::DEFAULT_Q_JPEG);
            echo '<input type="number" min="0" max="100" name="'.esc_attr(SCP_WebP_Config::OPT_QUALITY_JPEG).'" value="'.esc_attr($val).'" /> ';
            echo '<small>Recomendado 80–85</small>';
        }, 'scp-webp-converter', 'scp_webp_section');

        add_settings_field(SCP_WebP_Config::OPT_QUALITY_PNG, 'Calidad WebP para PNG', function(){
            $val = (int)get_option(SCP_WebP_Config::OPT_QUALITY_PNG, SCP_WebP_Config::DEFAULT_Q_PNG);
            echo '<input type="number" min="0" max="100" name="'.esc_attr(SCP_WebP_Config::OPT_QUALITY_PNG).'" value="'.esc_attr($val).'" /> ';
            echo '<small>Si buscas <em>visualmente</em> casi sin pérdidas, 90–100</small>';
        }, 'scp-webp-converter', 'scp_webp_section');

        add_settings_field(SCP_WebP_Config::OPT_PROCESS_CONTENT, 'Procesar contenido hardcodeado', function(){
            $val = (int)get_option(SCP_WebP_Config::OPT_PROCESS_CONTENT, 1);
            echo '<label><input type="checkbox" name="'.esc_attr(SCP_WebP_Config::OPT_PROCESS_CONTENT).'" value="1" '.checked($val, 1, false).' /> ';
            echo 'Reemplazar URLs de imágenes JPEG/PNG por WebP en el contenido de posts/páginas</label><br>';
            echo '<small>Procesa imágenes incrustadas directamente en el HTML del contenido (no solo las de Media Library)</small>';
        }, 'scp-webp-converter', 'scp_webp_content_section');

        add_settings_field(SCP_WebP_Config::OPT_WEBP_FORMAT, 'Formato de archivos WebP', function(){
            $current_format = get_option(SCP_WebP_Config::OPT_WEBP_FORMAT, 'double_extension');
            
            echo '<fieldset>';
            echo '<legend class="screen-reader-text">Seleccionar formato de archivos WebP</legend>';
            
            echo '<p><label>';
            echo '<input type="radio" name="'.esc_attr(SCP_WebP_Config::OPT_WEBP_FORMAT).'" value="double_extension" '.checked($current_format, 'double_extension', false).' /> ';
            echo '<strong>Doble extensión</strong> (imagen.jpg.webp)';
            echo '</label><br>';
            echo '<small style="margin-left: 25px; color: #666;">✅ Formato recomendado para compatibilidad. Mantiene información de la extensión original.</small></p>';
            
            echo '<p><label>';
            echo '<input type="radio" name="'.esc_attr(SCP_WebP_Config::OPT_WEBP_FORMAT).'" value="single_extension" '.checked($current_format, 'single_extension', false).' /> ';
            echo '<strong>Extensión única</strong> (imagen.webp)';
            echo '</label><br>';
            echo '<small style="margin-left: 25px; color: #666;">🔧 Compatible con plugins como Optimus. Nombres de archivo más cortos.</small></p>';
            
            echo '</fieldset>';
            
            echo '<div style="margin-top: 10px; padding: 10px; background: #f0f6fc; border-left: 4px solid #0073aa; font-size: 13px;">';
            echo '<strong>💡 Nota:</strong> Si cambias el formato, usa la pestaña <em>"🏷️ Unificar Formato"</em> para renombrar archivos WebP existentes.';
            echo '</div>';
        }, 'scp-webp-converter', 'scp_webp_extension_section');
    }

    /**
     * Carga assets del admin
     */
    public function admin_assets($hook) {
        if ($hook !== 'settings_page_scp-webp-converter') return;
        
        wp_enqueue_script(
            'scp-webp-admin', 
            SCP_WEBP_PLUGIN_URL . 'assets/js/admin.js', 
            ['jquery'], 
            SCP_WEBP_VERSION, 
            true
        );
        
        wp_localize_script('scp-webp-admin', 'SCP_WEBP', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('scp_webp_nonce'),
            'capabilities' => SCP_WebP_Capabilities::get_capabilities(),
        ]);
        
        // Cargar assets del renombrador si estamos en esa pestaña
        $current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'settings';
        if ($current_tab === 'renamer') {
            $this->renamer_admin->enqueue_renamer_assets();
        }
    }

    /**
     * Renderiza página de configuración principal
     */
    public function render_settings_page() {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'settings';
        ?>
        <div class="wrap">
            <h1>SCP WebP Converter <span style="font-size: 0.6em; color: #666;">v<?php echo SCP_WEBP_VERSION; ?></span></h1>
            
            <!-- Navigation Tabs -->
            <nav class="nav-tab-wrapper" style="margin-bottom: 20px;">
                <a href="?page=scp-webp-converter&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">
                    ⚙️ Configuración
                </a>
                <a href="?page=scp-webp-converter&tab=status" class="nav-tab <?php echo $active_tab == 'status' ? 'nav-tab-active' : ''; ?>">
                    📊 Estado del Sistema
                </a>
                <a href="?page=scp-webp-converter&tab=sizes" class="nav-tab <?php echo $active_tab == 'sizes' ? 'nav-tab-active' : ''; ?>">
                    🖼️ Medidas Activas
                </a>
                <a href="?page=scp-webp-converter&tab=bulk" class="nav-tab <?php echo $active_tab == 'bulk' ? 'nav-tab-active' : ''; ?>">
                    🔄 Conversión Masiva
                </a>
                <a href="?page=scp-webp-converter&tab=renamer" class="nav-tab <?php echo $active_tab == 'renamer' ? 'nav-tab-active' : ''; ?>">
                    🏷️ Unificar Formato
                </a>
                <a href="?page=scp-webp-converter&tab=help" class="nav-tab <?php echo $active_tab == 'help' ? 'nav-tab-active' : ''; ?>">
                    ❓ Ayuda
                </a>
            </nav>

            <?php
            switch ($active_tab) {
                case 'settings':
                    $this->render_settings_tab();
                    break;
                case 'status':
                    $this->render_status_tab();
                    break;
                case 'sizes':
                    $this->render_sizes_tab();
                    break;
                case 'bulk':
                    $this->render_bulk_tab();
                    break;
                case 'renamer':
                    $this->render_renamer_tab();
                    break;
                case 'help':
                    $this->render_help_tab();
                    break;
                default:
                    $this->render_settings_tab();
            }
            ?>
        </div>
        <?php
    }

    /** Pestaña: Configuración */
    private function render_settings_tab() {
        ?>
        <div class="scp-webp-tab-content">
            <h2>Configuración de Calidad y Procesamiento</h2>
            <p>Ajusta la calidad de compresión WebP y configura el procesamiento de contenido.</p>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('scp_webp_options');
                do_settings_sections('scp-webp-converter');
                submit_button('Guardar Configuración', 'primary', 'submit', true, ['style' => 'margin-top: 20px;']);
                ?>
            </form>
        </div>
        <?php
    }

    /** Pestaña: Estado del Sistema */
    private function render_status_tab() {
        ?>
        <div class="scp-webp-tab-content">
            <h2>Estado del Sistema</h2>
            <p>Verificación de compatibilidad y capacidades del servidor.</p>
            <?php $this->render_server_status(); ?>
        </div>
        <?php
    }

    /** Pestaña: Medidas Activas */
    private function render_sizes_tab() {
        ?>
        <div class="scp-webp-tab-content">
            <?php 
            require_once SCP_WEBP_PLUGIN_DIR . 'admin/class-webp-sizes-info.php';
            $sizes_info = new SCP_WebP_Sizes_Info();
            $sizes_info->render_active_sizes_info(); 
            ?>
        </div>
        <?php
    }

    /** Pestaña: Conversión Masiva */
    private function render_bulk_tab() {
        require_once SCP_WEBP_PLUGIN_DIR . 'admin/class-webp-bulk-converter.php';
        $bulk_converter = new SCP_WebP_Bulk_Converter();
        $bulk_converter->render_bulk_tab();
    }

    /** Pestaña: Renombrado/Unificar Formato */
    private function render_renamer_tab() {
        $this->renamer_admin->render_renamer_tab();
    }

    /** Pestaña: Ayuda */
    private function render_help_tab() {
        require_once SCP_WEBP_PLUGIN_DIR . 'admin/class-webp-help.php';
        $help = new SCP_WebP_Help();
        $help->render_help_tab();
    }

    /** Renderiza el estado del servidor */
    private function render_server_status() {
        $capabilities = SCP_WebP_Capabilities::get_capabilities();
        
        echo '<div class="card" style="max-width: none; margin-top: 20px;">';
        echo '<div style="padding: 20px;">';
        echo '<h3 style="margin-top: 0;">🔧 Verificación de Compatibilidad</h3>';
        
        // GD Status
        if ($capabilities['gd']['loaded'] ?? false) {
            $webp_support = $capabilities['gd']['webp_support'] ?? false;
            $icon = $webp_support ? '✅' : '⚠️';
            $status = $webp_support ? 'con soporte WebP' : 'sin soporte WebP';
            echo "<p>{$icon} <strong>GD:</strong> Disponible {$status} (" . ($capabilities['gd']['version'] ?? 'Unknown') . ")</p>";
        } else {
            echo "<p>❌ <strong>GD:</strong> No disponible</p>";
        }
        
        // Imagick Status
        if ($capabilities['imagick']['loaded'] ?? false) {
            $webp_support = $capabilities['imagick']['webp_support'] ?? false;
            $icon = $webp_support ? '✅' : '⚠️';
            $status = $webp_support ? 'con soporte WebP' : 'sin soporte WebP';
            echo "<p>{$icon} <strong>Imagick:</strong> Disponible {$status}</p>";
        } else {
            echo "<p>⚠️ <strong>Imagick:</strong> No disponible</p>";
        }
        
        // Upload directory permissions
        $writable = $capabilities['upload_writable'] ?? false;
        $icon = $writable ? '✅' : '❌';
        $status = $writable ? 'escribible' : 'sin permisos de escritura';
        echo "<p>{$icon} <strong>Directorio uploads:</strong> {$status}</p>";
        
        // Overall status
        $can_convert = SCP_WebP_Capabilities::has_webp_support() && ($capabilities['upload_writable'] ?? false);
        
        if ($can_convert) {
            echo '<p style="color: #46b450; font-weight: bold;">🎉 <strong>Estado:</strong> Listo para convertir WebP</p>';
        } else {
            echo '<p style="color: #dc3232; font-weight: bold;">⚠️ <strong>Estado:</strong> Configuración incompleta - revisa los requisitos</p>';
        }
        
        echo '</div>'; // Close padding div
        echo '</div>'; // Close card div
    }

    /**
     * Renderiza la página de limpieza de huérfanos
     */
    public function render_orphan_cleaner_page() {
        require_once SCP_WEBP_PLUGIN_DIR . 'admin/class-webp-orphan-cleaner.php';
        require_once SCP_WEBP_PLUGIN_DIR . 'admin/class-webp-orphan-cleaner-admin.php';

        $cleaner_admin = new SCP_WebP_Orphan_Cleaner_Admin();
        $cleaner_admin->render_orphan_cleaner_page();
    }
}