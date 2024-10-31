<?php
/*
Plugin Name: Gravity Forms IDX Broker Connector
Plugin URI: realtycandy.com
Description: Automatically add leads to your IDX Broker account when a Gravity Form form is submitted. Very simple to setup and easy to use.  Go to the form ->Settings->IDX Broker and click on the checkbox.  That's it!
Version: 1.1
Author: RealtyCandy
Author URI: http://realtycandy.com/
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if(!class_exists('Gf_Idxbroker_forms')) {
    class Gf_Idxbroker_Forms{

        public static $instance;
        public static function init() {
            if (is_null(self::$instance))
                self::$instance = new Gf_Idxbroker_Forms();
            return self::$instance;
        }
        private function __construct() {
            register_activation_hook( __FILE__, array( $this, 'activation') );

            //initialize this function only when your subscription form data has been posted
            add_filter('gform_form_settings_menu', array($this,'Idx_Addons_Gf_settings_menu_item'));
            // handle displaying content for our custom menu when selected
            add_action('gform_form_settings_page_my_custom_form_idx_broker', array($this,'Idx_Addons_Gf_form_idx_broker'));

            add_action('gform_after_submission', array($this,'Idx_Addons_Gf_Set_post_content'), 10, 2);
        }


        /**
         * Check dependencies if have idx-broker-platinum
         */
        public function activation() {
            if( !is_plugin_active('idx-broker-platinum/idx-broker-platinum.php') ){
                deactivate_plugins( plugin_basename( __FILE__ ) );
                wp_die( __( 'Sorry, you can\'t activate unless you have activated IDX Broker Platinum plugin') );exit;
            }
            if( !is_plugin_active('gravityforms/gravityforms.php') ){
                deactivate_plugins( plugin_basename( __FILE__ ) );
                wp_die( __( 'Sorry, you can\'t activate unless you have activated Gravity Forms plugin ') );exit;
            }
            include_once 'import-forms.php';
        }

        public function Idx_Addons_Gf_settings_menu_item($menu_items){
            $menu_items[] = array(
                'name' => 'my_custom_form_idx_broker',
                'label' => __('IDX Broker')
            );
            return $menu_items;
        }

        /**
         * Show this option in admin GF
         */
        public function Idx_Addons_Gf_form_idx_broker(){
            $form_id = rgget('id');
            GFFormSettings::page_header();

            $option_name = $form_id . '_option_idx';
            if (isset($_POST['submit'])) {
                $new_value = array();
                $new_value['enable_lead'] = (isset($_POST["enable_lead"]) && intval($_POST["enable_lead"]) == 1) ? 1 : 0;

                if (get_option($option_name) !== false) {
                    update_option($option_name, $new_value);
                } else {
                    add_option($option_name, $new_value);
                }
                $values_idx = get_option($option_name);
                $checked = $values_idx['enable_lead'];
            }
            $values_idx = get_option($option_name);
            $checked = $values_idx['enable_lead'];
            ?>
            <h3><span><i class="fa fa-cogs"></i> IDX Broker Settings</span></h3>
            <form action="" method="post" id="gform_form_settings">

                <table class="gforms_form_settings" cellpadding="0" cellspacing="0">
                    <tbody>
                    <tr>
                        <td colspan="2">
                            <h4 class="gf_settings_subgroup_title">Enable Lead Imports</h4>
                        </td>
                    </tr>
                    <tr>
                        <th>Enable Lead Imports</th>
                        <td>
                            <input id="enable_lead" name="enable_lead" value="1"
                                   type="checkbox" <?php checked($checked, '1', true); ?>>
                            <label for="enable_lead">Import Leads</label>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <button type="submit" name="submit" class="btn button-primary gfbutton">Update Settings</button>
            </form>
            <?php
            GFFormSettings::page_footer();
        }


        /**
         * Send Information after send form
         * @param $entry
         * @param $form
         */
        public function Idx_Addons_Gf_Set_post_content($entry, $form){
            $form_id = $form['id'];
            $option_name = $form_id . '_option_idx';
            $values_idx = get_option($option_name);

            $checked = $values_idx['enable_lead'];
            $apikey_idx = get_option('idx_broker_apikey');

            if ($checked) {
                if (!empty($apikey_idx)) {

                    $fields = $this->Idx_Addons_Gf_Get_all_form_fields($form_id);

                    $code_firstname = (string)$this->Idx_Addons_Gf_Findfield($fields, 'Name (First)');
                    $firstname = filter_var($entry[$code_firstname], FILTER_SANITIZE_STRING);

                    $code_lastname = (string)$this->Idx_Addons_Gf_Findfield($fields, 'Name (Last)');
                    $lastname = filter_var($entry[$code_lastname], FILTER_SANITIZE_STRING);

                    $code_email = (string)$this->Idx_Addons_Gf_Findfield($fields, 'Email');
                    $email = filter_var($entry[$code_email], FILTER_SANITIZE_STRING);

                    $code_phone = (string)$this->Idx_Addons_Gf_Findfield($fields, 'Phone');
                    $phone = filter_var($entry[$code_phone], FILTER_SANITIZE_STRING);

                    $code_streetAddress = (string)$this->Idx_Addons_Gf_Findfield($fields, 'Address (Street Address)');
                    $streetAddress = filter_var($entry[$code_streetAddress], FILTER_SANITIZE_STRING);

                    $code_addressLine = (string)$this->Idx_Addons_Gf_Findfield($fields, 'Address (Address Line 2)');
                    $addressLine = filter_var($entry[$code_addressLine], FILTER_SANITIZE_STRING);

                    $code_city = (string)$this->Idx_Addons_Gf_Findfield($fields, 'Address (City)');
                    $city = filter_var($entry[$code_city], FILTER_SANITIZE_STRING);

                    $code_state = (string)$this->Idx_Addons_Gf_Findfield($fields, 'Address (State / Province)');
                    $state = filter_var($entry[$code_state], FILTER_SANITIZE_STRING);

                    $code_zip = (string)$this->Idx_Addons_Gf_Findfield($fields, 'Address (ZIP / Postal Code)');
                    $zip = filter_var($entry[$code_zip], FILTER_SANITIZE_STRING);

                    $code_country = (string)$this->Idx_Addons_Gf_Findfield($fields, 'Address (Country)');
                    $country = filter_var($entry[$code_country], FILTER_SANITIZE_STRING);

                    $domain_url = $_SERVER['SERVER_NAME'] . "/inbound-api/v1/";

                    $data = array(
                        'firstName' => $firstname,
                        'lastName' => $lastname,
                        'email' => $email,
                        'phone' => $phone,
                        'address' => $streetAddress,
                        'city' => $city,
                        'stateProvince' => $state,
                        'zipCode' => $zip,
                        'country' => $country,
                        'base_api_url' => $domain_url,

                    );
                    //$data['apikey'] = $apikey_idx;
                    $data['addons_apikey'] = get_option('idx_addons_apikey');
                    $data['domain'] = $domain_url;
                    $data['idxdev'] = '0';

                    $result_sever = $this->Idx_Addons_Gf_Curl_connect("https://api.idxbroker.com/leads/lead", $apikey_idx, $method="PUT", $data);

                }
            }
        }

        /**
         * Helper Get names and Ids GF
         * @param $form_id
         * @return array
         */
        public function Idx_Addons_Gf_Get_all_form_fields($form_id){
            $form = RGFormsModel::get_form_meta($form_id);
            $fields = array();

            if (is_array($form["fields"])) {
                foreach ($form["fields"] as $field) {
                    if (isset($field["inputs"]) && is_array($field["inputs"])) {

                        foreach ($field["inputs"] as $input)
                            $fields[] = array('id' => $input["id"], 'name' => GFCommon::get_label($field, $input["id"]));
                    } else if (!rgar($field, 'displayOnly')) {
                        $fields[] = array('id' => $field["id"], 'name' => GFCommon::get_label($field));
                    }
                }
            }
            return $fields;
        }

        public function Idx_Addons_Gf_Findfield($fields, $fid){
            foreach ($fields as $field) {
                if ($field['name'] == $fid) return $field['id'];
            }
            return false;
        }

        public function Idx_Addons_Gf_Curl_connect($url, $apikey, $method="POST", $data=array()){
            if ($method == 'PUT' || $method == 'POST') {
                $data = http_build_query($data); // encode and & delineate
            }
            $headers = array(
                'Content-Type: application/x-www-form-urlencoded', // required
                'accesskey: '.$apikey,
                'outputtype: json' // optional - overrides the preferences in our API control page
            );

            // set up cURL
            $handle = curl_init();
            curl_setopt($handle, CURLOPT_URL, $url);
            curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

            if ($method != 'GET' AND !empty($data)) {
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, $method);
                curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
            }
            $response = curl_exec($handle);
            $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

            if ($code >= 200 || $code < 300) {
                return json_decode($response,true);
            } else {
                return $code;
            }
        }
    }
    Gf_Idxbroker_forms::init();
}
