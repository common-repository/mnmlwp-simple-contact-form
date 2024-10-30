<?php

/*
Plugin Name: mnmlWP Simple Contact Form
Plugin URI: https://de.wordpress.org/plugins/mnmlwp-simple-contact-form/
Description: This plugin provides a simple contact form including shortcode, basic form fields, and spam prevention.
Author: Sebastian Honert
Version: 0.2.7
Author URI: https://sebastianhonert.com
Text Domain: mnmlwp-contact-form
License: GNU General Public License v2 or later
License URI:  http://www.gnu.org/licenses/gpl-2.0.html
*/

class MNMLWP_Contact_Form
{
    private static $instance;

    public static function get_instance()
    {
        if ( null == self::$instance ) {
            self::$instance = new MNMLWP_Contact_Form();
        }

        return self::$instance;
    }

    function __construct()
    {
        add_shortcode('contact', array( $this, 'mnmlwp_contact_form_shortcode' ));

        add_action('admin_post_mnmlwp-submit-contact-form', array( $this, 'mnmlwp_contact_form_process' ));
        add_action('admin_post_nopriv_mnmlwp-submit-contact-form', array( $this, 'mnmlwp_contact_form_process' ));

        add_action('after_setup_theme', array( $this, 'mnmlwp_contact_form_i18n' ));
        add_action('init', array( $this, 'mnmlwp_contact_form_customizer' ));
        add_action('init', array( $this, 'make_session_security_number' ));
        add_action('wp_enqueue_scripts', array( $this, 'load_scripts_and_styles' ));
    }

    function mnmlwp_contact_form_i18n()
    {
        load_plugin_textdomain('mnmlwp-contact-form', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
    }

    function make_session_security_number()
    {
        if( ! isset( $_SESSION ) ) {
            session_start();

            $_SESSION['mnmlwp-contact-form-security-number'] = isset( $_SESSION['mnmlwp-contact-form-security-number'] ) ? $_SESSION['mnmlwp-contact-form-security-number'] : rand(100, 999) * 973;

            session_write_close();
        }
    }

    function load_scripts_and_styles()
    {
        wp_enqueue_script('mnmlwp-contact-form', plugins_url('assets/js/mnmlwp-contact-form.js',  __FILE__), array('jquery'), '0.0.1', true);
        wp_enqueue_style('mnmlwp-contact-form', plugins_url('assets/css/mnmlwp-contact-form.css', __FILE__ ), null, null, 'screen');
    }

    function mnmlwp_contact_form_shortcode( $atts )
    {
        extract( shortcode_atts( array (
            'message' => '',
            'phone' => '',
            'address' => '',
            'mailto' => '',
            'dummy' => '',
        ), $atts ) );
        
        $recipients = '';
        $recipients_error = false;
        
        if( $mailto )
        {
            $recipients = explode(',', $mailto);

            foreach( $recipients as $email )
            {
                if( ! filter_var($email, FILTER_VALIDATE_EMAIL) ) {
                    $recipients_error = true;
                    $recipients = array();
                    break;
                }
            }
            
            $recipients = empty( $recipients ) ? '' : implode(',', $recipients);
        }

        $html = '<div id="mnmlwp-contact-form-anchor"></div>';
        
        if( $recipients_error )
            $html .= '<div class="mnmlwp-msg mnmlwp-msg-error">' . esc_html__('Error: The mailto parameter needs to include a single email address or a comma separated list of email addresses.', 'mnmlWP-shortcodes') . '</div>';
        
        // Invalid token
        if( isset( $_GET['message'] ) && $_GET['message'] === 'token' ) {
            $html .= '<p class="mnmlwp-msg mnmlwp-msg-error">' . esc_html__('Invalid token.', 'mnmlwp-contact-form') . '</p>';
        }
        
        // Success message
        if( isset( $_GET['success'] ) && $_GET['success'] === '1' ) {
            $html .= '<p class="mnmlwp-msg mnmlwp-msg-success mnmlwp-msg-no-icon">' . get_theme_mod('mnmlwp_contact_form_success_message', 'Thank you for your message. We have received your information and will get back to you as soon as possible. You will receive a confirmation e-mail shortly.') . '</p>';
        }
        
        // GDPR checkbox not checked
        if( get_theme_mod('mnmlwp_contact_form_use_data_privacy_checkbox', true) === true && isset( $_GET['message'] ) && $_GET['message'] === 'gdpr' ) {
            $html .= '<p class="mnmlwp-msg mnmlwp-msg-error">' . esc_html__('Please accept our terms and conditions before submitting the form.', 'mnmlwp-contact-form') . '</p>';
        }

        // Wrong security number
        if( get_theme_mod('mnmlwp_contact_form_use_security_number', true) === true && isset( $_GET['message'] ) && $_GET['message'] === 'security' ) {
            $html .= '<p class="mnmlwp-msg mnmlwp-msg-error">' . esc_html__('Please enter the correct security number before sending the form.', 'mnmlwp-contact-form') . '</p>';
        }

        // E-Mails don't match
        if( isset( $_GET['message'] ) && $_GET['message'] === 'emails_dont_match' ) {
            $html .= '<p class="mnmlwp-msg mnmlwp-msg-error">' . esc_html__('The e-mail addresses you provided do not match.', 'mnmlwp-contact-form') . '</p>';
        }

        // Previous form data
        $form_data = array(
            'name' => '',
            'email' => '',
            'email_confirmation' => '',
            'phone' => '',
            'address' => '',
            'message' => sanitize_textarea_field( $message ),
            'security' => '',
            'gdpr' => '',
        );

        if( isset( $_GET['form_data'] ) )
        {
            $form_data['name'] = isset( $_GET['form_data']['name'] ) ? sanitize_text_field( $_GET['form_data']['name'] ) : '';
            $form_data['email'] = isset( $_GET['form_data']['email'] ) ? sanitize_email( $_GET['form_data']['email'] ) : '';
            $form_data['email_confirmation'] = isset( $_GET['form_data']['email_confirmation'] ) ? sanitize_email( $_GET['form_data']['email_confirmation'] ) : '';
            $form_data['phone'] = isset( $_GET['form_data']['phone'] ) ? sanitize_text_field( $_GET['form_data']['phone'] ) : '';
            $form_data['address'] = isset( $_GET['form_data']['address'] ) ? sanitize_text_field( $_GET['form_data']['address'] ) : '';
            $form_data['message'] = isset( $_GET['form_data']['message'] ) ? stripslashes( stripslashes( sanitize_textarea_field( $_GET['form_data']['message'] ) ) ) : '';
            $form_data['security'] = isset( $_GET['form_data']['security'] ) ? sanitize_textarea_field( $_GET['form_data']['security'] ) : '';
            $form_data['gdpr'] = isset( $_GET['form_data']['gdpr'] ) ? sanitize_textarea_field( $_GET['form_data']['gdpr'] ) : '';
        }

        // Contact form
        $html .= '<form class="mnmlwp-contact-form" action="' . admin_url('admin-post.php') . '" method="post">
            <label class="mnmlwp-contact-form-special mnmlwp-required-field">First Name</label>
            <input class="mnmlwp-contact-form-special" type="text" name="firstname" value="">

            <label class="mnmlwp-contact-form-special mnmlwp-required-field">Last Name</label>
            <input class="mnmlwp-contact-form-special" type="text" name="lastname" value="">

            <label class="mnmlwp-contact-form-special mnmlwp-required-field">Website</label>
            <input class="mnmlwp-contact-form-special" type="text" name="website" value="">

            <label for="name" class="mnmlwp-required-field">' . __('Name', 'mnmlwp-contact-form') . '</label>
            <input type="text" id="name" name="name" value="' . $form_data['name'] . '"  maxlength="48" required>

            <label for="email" class="mnmlwp-required-field">' . __('E-Mail', 'mnmlwp-contact-form') . '</label>
            <input type="email" id="email" name="email" value="' . $form_data['email'] . '"  maxlength="64" required>

            <label for="email_confirmation" class="mnmlwp-required-field">' . __('Confirm E-Mail', 'mnmlwp-contact-form') . '</label>
            <input type="email" id="email_confirmation" name="email_confirmation" value="' . $form_data['email_confirmation'] . '"  maxlength="64" required>';

            if( filter_var($phone, FILTER_VALIDATE_BOOLEAN) || ! empty($form_data['phone']) ) {
                $html .= '<label for="phone">' . __('Phone Number', 'mnmlwp-contact-form') . '</label>
                <input type="tel" id="phone" name="phone" value="' . $form_data['phone'] . '"  maxlength="24">';
            }

            if( filter_var($address, FILTER_VALIDATE_BOOLEAN) || ! empty($form_data['address']) ) {
                $html .= '<label for="address">' . __('Address', 'mnmlwp-contact-form') . '</label>
                <input type="text" id="address" name="address" value="' . $form_data['address'] . '"  maxlength="128">';
            }

            $html .= '<label for="message" class="mnmlwp-required-field">' . __('Message', 'mnmlwp-contact-form') . '</label>
                <textarea id="message" name="message" maxlength="1024" required style="font-family:inherit">' . $form_data['message'] . '</textarea>';
            
            if( get_theme_mod('mnmlwp_contact_form_use_security_number', true) === true ) {
                $html .= '<label for="security" class="mnmlwp-required-field">' . __('Security Number', 'mnmlwp-contact-form') . ':&nbsp;'  . $_SESSION['mnmlwp-contact-form-security-number'] . '</label>
                <input type="text" id="security" name="security" value="' . $form_data['security'] . '" maxlength="16" required>';
            }
            
            $html .= '<input type="hidden" name="redirect_id" value="' . get_the_ID() . '">';
            
            if( ! empty( $recipients ) ) {
                $html .= '<input type="hidden" name="recipients" value="' . $recipients . '">';
            }
            
            $html .= '<input type="hidden" name="token" value="' . MNMLWP_Contact_Form::mnmlwp_contact_form_get_token() . '">
            <input type="hidden" name="action" value="mnmlwp-submit-contact-form">
            <input type="hidden" name="dummy" value="' . sanitize_text_field( $dummy ) . '">';
            
            if( get_theme_mod('mnmlwp_contact_form_use_data_privacy_checkbox', true ) === true ) {
                $gdpr_checked = ! empty( $form_data['gdpr'] ) ? 'checked' : '';
                $gdpr_text = ! empty( get_theme_mod('mnmlwp_contact_form_data_privacy_checkbox_text' ) ) ? get_theme_mod('mnmlwp_contact_form_data_privacy_checkbox_text') : wp_kses_post( __('By submitting the form you are agreeing to our data privacy <a href="#">terms and conditions</a>.', 'mnmlwp-contact-form' ) );
                $html .= '<label for="gdpr" class="mnmlwp-required-field">' . __('Data Privacy', 'mnmlwp-contact-form') . '</label>';
                $html .= '<p id="mnmlwp-contact-form-gdpr-checkbox"><input type="checkbox" id="gdpr" name="gdpr" value="1" ' . $gdpr_checked . '> ' . $gdpr_text . '</p>';
            }

            $html .= '<input class="mnmlwp-contact-form-submit" type="submit" value="' . esc_html__('Submit Form', 'mnmlwp-contact-form') . '">
        </form>';

        // jQuery disable submit button after form has been submitted
        $html .= '<script>
        jQuery(document).ready(function($) {
            $(".mnmlwp-contact-form").on("submit", function() {
                $(this).find("input[type=\'submit\']").prop(\'disabled\', true);
            });
        });
        </script>';

        return preg_replace( '/\r|\n/', '', $html );
    }

    function mnmlwp_contact_form_process()
    {
        // Check if a form has been sent
        $postedToken = filter_input(INPUT_POST, 'token');

        if( ! empty( $postedToken ) )
        {
            $redirect = get_permalink( (int)$_POST['redirect_id'] );
            $htmlAnchor = '#mnmlwp-contact-form-anchor';
            
            if( MNMLWP_Contact_Form::mnmlwp_contact_form_is_token_valid( $postedToken ) )
            {
                // Dummy
                if( ! empty( $_POST['dummy'] ) ) {
                    wp_redirect( $redirect . '?message=dummy' );
                    exit();
                }

                // Honeypot
                if( ! empty( $_POST['firstname'] ) || ! empty( $_POST['lastname'] ) || ! empty( $_POST['website'] ) ) {
                    wp_redirect( $redirect . '?message=honeypot' );
                    exit();
                }

                // Get form fields
                $name = sanitize_text_field( $_POST['name'] );
                $email = sanitize_email( $_POST['email'] );
                $email_confirmation = sanitize_email( $_POST['email_confirmation'] );
                $phone = sanitize_text_field( $_POST['phone'] );
                $address = sanitize_text_field( $_POST['address'] );
                $message = stripslashes( stripslashes( sanitize_textarea_field( $_POST['message'] ) ) );
                $recipients = isset( $_POST['recipients'] ) ? explode(',', $_POST['recipients'] ) : '';
                $gdpr = isset( $_POST['gdpr'] ) ? true : false;
                
                if( ! empty( $recipients ) ) {
                    foreach( $recipients as $key => $recipient ) {
                        if( ! filter_var($email, FILTER_VALIDATE_EMAIL) ) {
                            unset( $recipients[$key] );
                        }
                    }
                }
                
                $recipients = ! empty( $recipients ) ? implode(',', $recipients ) : '';
                
                if( get_theme_mod('mnmlwp_contact_form_use_security_number', true) === true ) {
                    $security = sanitize_text_field( $_POST['security'] );
                } else {
                    $security = true;
                }

                // Form data and quer for redirects
                $form_data = array(
                    'name' => $name,
                    'email' => $email,
                    'email_confirmation' => $email_confirmation,
                    'phone' => $phone,
                    'address' => $address,
                    'message' => $message,
                    'security' => $security,
                    'gdpr' => $gdpr,
                );

                $query = http_build_query(
                    array( 'form_data' => $form_data )
                );

                // Check if form fields are all set
                if( ! ( $name && $email && $email_confirmation && $message && $security ) ) {
                    wp_redirect( $redirect . '?message=incomplete&' . $query . $htmlAnchor );
                    exit();
                }
                
                // Validate GDPR checkbox
                if( get_theme_mod('mnmlwp_contact_form_use_data_privacy_checkbox', true) === true) {
                    if( ! isset( $_POST['gdpr'] ) ) {
                        wp_redirect( $redirect . '?message=gdpr&' . $query . $htmlAnchor );
                        exit();
                    }
                }

                // Validate security number
                if( get_theme_mod('mnmlwp_contact_form_use_security_number', true) === true ) {
                    if( (int)$security !== $_SESSION['mnmlwp-contact-form-security-number'] )
                    {
                        wp_redirect( $redirect . '?message=security&' . $query . $htmlAnchor );
                        exit();
                    }
                }

                // Validate e-mail
                if( $email !== $email_confirmation ) {
                    wp_redirect( $redirect . '?message=emails_dont_match&' . $query . $htmlAnchor );
                    exit();
                }

                // Send form to admin
                $to = ! empty( $recipients ) ? $recipients : get_bloginfo('admin_email');
                $subject = esc_html__('New Message', 'mnmlwp-contact-form');
                $body = '<p>' . esc_html__('Someone sent you a message through your contact form.', 'mnmlwp-contact-form') . ' (' . $redirect . ')</p><p>' . $message . '<br><br>' . $name . ' (' . $email . ')</p>';

                if( ! empty( $phone ) ) {
                    $body .= '<p>' . esc_html__('Phone Number', 'mnmlwp-contact-form') . ': ' . $phone . '</p>';
                }

                if( ! empty( $address ) ) {
                    $body .= '<p>' . esc_html__('Address', 'mnmlwp-contact-form') . ': ' . $address . '</p>';
                }

                $headers = array('Content-Type: text/html; charset=UTF-8','From: ' . get_bloginfo('name') . ' <' . get_bloginfo('admin_email') . '>');

                wp_mail( $to, $subject, $body, $headers );

                // Send confirmation mail to sender
                $to = $email;
                $subject = esc_html__('Your Message', 'mnmlwp-contact-form');
                $body = wpautop( esc_html( get_theme_mod('mnmlwp_contact_form_reply_text') ) );
                $body .= '<p>' . $message . '</p><p>' . $name . ' (' . $email . ')</p>';

                if( ! empty( $phone ) ) {
                    $body .= '<p>' . esc_html__('Phone Number', 'mnmlwp-contact-form') . ': ' . $phone . '</p>';
                }

                if( ! empty( $address ) ) {
                    $body .= '<p>' . esc_html__('Address', 'mnmlwp-contact-form') . ': ' . $address . '</p>';
                }

                $headers = array('Content-Type: text/html; charset=UTF-8','From: ' . get_bloginfo('name') . ' <' . get_bloginfo('admin_email') . '>');

                wp_mail( $to, $subject, $body, $headers );

                // Done.
                wp_redirect( $redirect . '?success=1' . $htmlAnchor );
                exit();
            }
            
            // Multiple submit
            else {
                wp_redirect( $redirect . '?message=token' );
                exit();
            }
        }
    }

    /**
     * Creates a token usable in a form
     * @return string
     */
    public static function mnmlwp_contact_form_get_token()
    {
        $token = sha1(mt_rand());

        if( ! isset( $_SESSION['tokens'] ) ) {
            $_SESSION['tokens'] = array($token => 1);
        } else{
            $_SESSION['tokens'][$token] = 1;
        }

        return $token;
    }

    /**
     * Check if a token is valid and remove it from the valid tokens list
     * @param string $token The token
     * @return bool
     */
    public static function mnmlwp_contact_form_is_token_valid( $token )
    {
        if( get_theme_mod('mnmlwp_contact_form_use_token', false) === false )
            return true;
        
        if( ! empty( $_SESSION['tokens'][$token] ) )
        {
            unset( $_SESSION['tokens'][$token] );
            return true;
        }

        return false;
    }

    function mnmlwp_contact_form_customizer()
    {
        function mnmlwp_contact_form_customize_register( $wp_customize )
        {
            // Settings
            
            $wp_customize->add_section( 'mnmlwp_contact_form_section' , array(
                'title' => esc_html__( 'mnmlWP Contact Form', 'mnmlwp-contact-form' ),
                'priority' => 30,
            ) );

            $wp_customize->add_setting( 'mnmlwp_contact_form_success_message' , array(
                'default'   => 'Thank you for your message. We have received your information and will get back to you as soon as possible. You will receive a confirmation e-mail shortly.',
                'transport' => 'refresh',
                'sanitize_callback' => 'wp_kses_post',
            ) );
            
            $wp_customize->add_setting( 'mnmlwp_email_signature' , array(
                'default'   => null,
                'transport' => 'refresh',
                'sanitize_callback' => 'wp_kses_post',
            ) );

            $wp_customize->add_setting( 'mnmlwp_contact_form_reply_text' , array(
                'default'   => 'Thank you for your message. We have received your information and will get back to you as soon as possible. Please find a copy of your message below.',
                'transport' => 'refresh',
                'sanitize_callback' => 'wp_kses_post',
            ) );
            
            $wp_customize->add_setting( 'mnmlwp_contact_form_use_token', array(
                'default' => false,
                'capability' => 'edit_theme_options',
                'sanitize_callback' => 'mnmlwp_contact_form_sanitize_checkbox',
            ) );
            
            $wp_customize->add_setting( 'mnmlwp_contact_form_use_security_number', array(
                'default' => true,
                'capability' => 'edit_theme_options',
                'sanitize_callback' => 'mnmlwp_contact_form_sanitize_checkbox',
            ) );
            
            $wp_customize->add_setting( 'mnmlwp_contact_form_use_data_privacy_checkbox', array(
                'default' => true,
                'capability' => 'edit_theme_options',
                'sanitize_callback' => 'mnmlwp_contact_form_sanitize_checkbox',
            ) );
            
            $wp_customize->add_setting( 'mnmlwp_contact_form_data_privacy_checkbox_text' , array(
                'default' => wp_kses_post( __('By submitting the form you are agreeing to our data privacy <a href="#">terms and conditions</a>.', 'mnmlwp-contact-form' ) ),
                'transport' => 'refresh',
                'sanitize_callback' => 'wp_kses_post',
            ) );
            
            // Controls

            $wp_customize->add_control( 'mnmlwp_contact_form_success_message', array(
                'type' => 'textarea',
                'section' => 'mnmlwp_contact_form_section',
                'label' => esc_html__( 'Success Message', 'mnmlwp-contact-form'),
                'description' => esc_html__( 'This message will show on the website after the form was sucessfully submitted.', 'mnmlwp-contact-form' ),
            ) );
            
            $wp_customize->add_control( 'mnmlwp_email_signature', array(
                'type' => 'textarea',
                'section' => 'mnmlwp_contact_form_section',
                'label' => esc_html__( 'E-Mail Signature', 'mnmlwp-contact-form'),
                'description' => esc_html__( 'If this field is set, it\'s content will be added to all e-mails sent through your website.', 'mnmlwp-contact-form' ),
            ) );

            $wp_customize->add_control( 'mnmlwp_contact_form_reply_text', array(
                'type' => 'textarea',
                'section' => 'mnmlwp_contact_form_section',
                'label' => esc_html__( 'Contact form reply text', 'mnmlwp-contact-form'),
                'description' => esc_html__( 'The content of this field will be added to the auto reply e-mail sent through the contact form.', 'mnmlwp-contact-form' ),
            ) );
            
            $wp_customize->add_control( 'mnmlwp_contact_form_use_token', array(
                'type' => 'checkbox',
                'section' => 'mnmlwp_contact_form_section',
                'label' => esc_html__( 'Use token to prevent multiple submits?', 'mnmlwp-contact-form'),
                'description' => esc_html__('Disable this option if you get a token error when submitting the form.', 'mnmlwp-contact-form')
            ) );
            
            $wp_customize->add_control( 'mnmlwp_contact_form_use_security_number', array(
                'type' => 'checkbox',
                'section' => 'mnmlwp_contact_form_section',
                'label' => esc_html__( 'Use security number field?', 'mnmlwp-contact-form'),
                'description' => esc_html__('Enabling this option will add a security number to your forms.', 'mnmlwp-contact-form')
            ) );
            
            $wp_customize->add_control( 'mnmlwp_contact_form_use_data_privacy_checkbox', array(
                'type' => 'checkbox',
                'section' => 'mnmlwp_contact_form_section',
                'label' => esc_html__( 'Use GDPR privacy checkbox?', 'mnmlwp-contact-form'),
            ) );
            
            $wp_customize->add_control( 'mnmlwp_contact_form_data_privacy_checkbox_text', array(
                'type' => 'textarea',
                'section' => 'mnmlwp_contact_form_section',
                'label' => esc_html__( 'GDPR checkbox description', 'mnmlwp-contact-form'),
            ) );
        }

        add_action( 'customize_register', 'mnmlwp_contact_form_customize_register' );
        
        // Checkbox sanitization
        function mnmlwp_contact_form_sanitize_checkbox( $checked ) {
            return ( ( isset( $checked ) && true == $checked ) ? true : false );
        }

        // Add e-mail signature if available
        function mnmlwp_wp_mail_filter_signature( $args ) {

            $new_wp_mail = array(
                'to' => $args['to'],
                'subject' => $args['subject'],
                'headers' => $args['headers'],
                'message' => $args['message'],
                'attachments' => $args['attachments']
            );

            if( get_theme_mod( 'mnmlwp_email_signature' ) ) {
                $new_wp_mail['message'] = $args['message'] . wpautop( get_theme_mod( 'mnmlwp_email_signature' ) );
            }

            return $new_wp_mail;
        }

        add_filter( 'wp_mail', 'mnmlwp_wp_mail_filter_signature' );
    }
}

add_action('plugins_loaded', array( 'MNMLWP_Contact_Form', 'get_instance' ));
