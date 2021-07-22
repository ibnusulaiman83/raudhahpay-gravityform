<?php

GFForms::include_payment_addon_framework();

class GFRaudhahPay extends GFPaymentAddOn
{
    const DEFAULT_CURRENCY = 'MYR';
    const ORDER_NUMBER_PREFIX = 'ORD';

    protected $_version = GF_RAUDHAHPAY_VERSION;
    protected $_min_gravityforms_version = '1.9.12';
    protected $_slug = 'gravityformsraudhahpay';
    protected $_path = 'gravityformsraudhahpay/gfraudhahpay.php';
    protected $_full_path = __FILE__;
    protected $_title = 'Raudhah Pay for Gravity Forms';
    protected $_short_title = 'Raudhah Pay';

    protected $_supports_callbacks = true;

    private static $_instance = null;

    public static function get_instance()
    {
        if ( self::$_instance == null ) {
            self::$_instance = new GFRaudhahPay();
        }

        return self::$_instance;
    }

    public function init_frontend()
    {
        parent::init_frontend();

        add_filter('gform_disable_post_creation', array( $this, 'delay_post' ), 10, 3);
        add_filter('gform_disable_notification', array( $this, 'delay_notification' ), 10, 4);
    }

    public function plugin_settings_fields() {
        return [
            [
                'title' => esc_html__( 'Raudhah Pay for Gravity Forms Settings', 'gravityformsraudhahpay' ),
                'description' => esc_html__('Raudhah Pay for GravityForms requires X Signature to be enabled on your Raudhah Pay account.', 'gravityformsraudhahpay'),
                'fields' => [
                    [
                        'name' => 'gf_raudhahpay_x_signature_configured',
                        'label' => esc_html__('Raudhah Pay XSignature Setting', 'gravityformsraudhahpay'),
                        'type' => 'checkbox',
                        'choices' => [
                            [
                                'label' => esc_html__('Confirm that you have configured your Raudhah Pay account to enable XSignature Payment Completion', 'gravityformsraudhahpay'),
                                'name' => 'gf_raudhahpay_x_signature_configured'
                            ]
                        ]
                    ],
                    [
                        'type' => 'save',
                        'messages' => [
                            'success' => esc_html__('Settings have been updated.', 'gravityformsraudhahpay')
                        ],
                    ],
                ],
            ]
        ];
    }

    public function feed_settings_fields() {
        $default_settings = parent::feed_settings_fields();

        $fields = [
            [
                'name' => 'webservice_url',
                'label' => esc_html__('Webservice Url ', 'gravityformsraudhahpay'),
                'type' => 'text',
                'class' => 'medium',
                'required' => true,
                'tooltip' => '<h6>' . esc_html__('Raudhah Pay Webservice Url', 'gravityformsraudhahpay') . '</h6>' . esc_html__('It can be from Production or Staging. It can be retrieved from Raudhah Pay Account Settings page.', 'gravityformsraudhahpay')
            ],
            [
                'name' => 'access_token',
                'label' => esc_html__('API Access Token ', 'gravityformsraudhahpay'),
                'type' => 'text',
                'class' => 'medium',
                'required' => true,
                'tooltip' => '<h6>' . esc_html__('Raudhah Pay API Secret Key', 'gravityformsraudhahpay') . '</h6>' . esc_html__('It can be from Production or Staging. It can be retrieved from Raudhah Pay Account Settings page.', 'gravityformsraudhahpay')
            ],
            [
                'name' => 'collection_id',
                'label' => esc_html__('Collection ID ', 'gravityformsraudhahpay'),
                'type' => 'text',
                'class' => 'medium',
                'required' => true,
                'tooltip' => '<h6>' . esc_html__('Raudhah Pay Collection ID', 'gravityformsraudhahpay') . '</h6>' . esc_html__('Enter your chosen specific Billing Collection ID. It can be retrieved from Raudhah Pay Billing page.', 'gravityformsraudhahpay')
            ],
            [
                'name' => 'x_signature_key',
                'label' => esc_html__('X Signature Key ', 'gravityformsraudhahpay'),
                'type' => 'text',
                'class' => 'medium',
                'required' => true,
                'tooltip' => '<h6>' . esc_html__('Raudhah Pay X Signature Key', 'gravityformsraudhahpay') . '</h6>' . esc_html__('It can be from Production or Staging. It can be retrieved from Raudhah Pay Account Settings page.', 'gravityformsraudhahpay')
            ],
            [
                'name' => 'bill_description',
                'label' => esc_html__('Bill Description', 'gravityformsraudhahpay'),
                'type' => 'textarea',
                'tooltip' => '<h6>' . esc_html__('Raudhah Pay Bills Description', 'gravityformsraudhahpay') . '</h6>' . esc_html__('Enter your description here. It will displayed on Bill page.', 'gravityformsraudhahpay'),
                'class' => 'medium merge-tag-support mt-position-right',
                'required' => false,
            ]
        ];

        $default_settings = parent::add_field_after('feedName', $fields, $default_settings);

        $transaction_type = parent::get_field('transactionType', $default_settings);
        unset($transaction_type['choices'][2]);

        $default_settings = $this->replace_field('transactionType', $transaction_type, $default_settings);

        $fields = [
            [
                'name' => 'cancel_url',
                'label' => esc_html__('Cancel URL', 'gravityformsraudhahpay'),
                'type' => 'text',
                'class' => 'medium',
                'required' => false,
                'tooltip' => '<h6>' . esc_html__('Cancel URL', 'gravityformsraudhahpay') . '</h6>' . esc_html__('Enter the URL the user should be sent to should they cancel before completing their payment.', 'gravityformsraudhahpay')
            ],
        ];

        if ($this->get_setting('delayNotification') || ! $this->is_gravityforms_supported('1.9.12')) {
            $fields[] = [
                'name' => 'notifications',
                'label' => esc_html__('Notifications', 'gravityformsraudhahpay'),
                'type' => 'notifications',
                'tooltip' => '<h6>' . esc_html__('Notifications', 'gravityformsraudhahpay') . '</h6>' . esc_html__("Enable this option if you would like to only send out this form's notifications for the 'Form is submitted' event after payment has been received. Leaving this option disabled will send these notifications immediately after the form is submitted. Notifications which are configured for other events will not be affected by this option.", 'gravityformsraudhahpay')
            ];
        }

        //Add post fields if form has a post
        $form = $this->get_current_form();

        if (GFCommon::has_post_field($form['fields'])) {
            $post_settings = [
                'name' => 'post_checkboxes',
                'label' => esc_html__('Posts', 'gravityformsraudhahpay'),
                'type' => 'checkbox',
                'tooltip' => '<h6>' . esc_html__('Posts', 'gravityformsraudhahpay') . '</h6>' . esc_html__('Enable this option if you would like to only create the post after payment has been received.', 'gravityformsraudhahpay'),
                'choices' => [
                    [
                        'label' => esc_html__('Create post only when payment is received.', 'gravityformsraudhahpay'),
                        'name' => 'delayPost'
                    ],
                ],
            ];

            $fields[] = $post_settings;
        }

        $billing_info = parent::get_field('billingInformation', $default_settings);

        /*
         * Removing unrelated variable
         */
        unset($billing_info['field_map'][0]); //email for better arrangement
        unset($billing_info['field_map'][1]); //address 1
        unset($billing_info['field_map'][2]); //address 2
        unset($billing_info['field_map'][3]); //city
        unset($billing_info['field_map'][4]); //state
        unset($billing_info['field_map'][5]); //zip
        unset($billing_info['field_map'][6]); //country

        /*
         * Adding Raudhah Pay required variable. The last will be the first
         */
        array_unshift(
            $billing_info['field_map'],
            [
                'name' => 'address_2',
                'label' => esc_html__('Address Line 2', 'gravityformsraudhahpay'),
                'required' => false
            ]
        );
        array_unshift(
            $billing_info['field_map'],
            [
                'name' => 'address_1',
                'label' => esc_html__('Address Line 1', 'gravityformsraudhahpay'),
                'required' => true
            ]
        );
        array_unshift(
            $billing_info['field_map'],
            [
                'name' => 'phone_number',
                'label' => esc_html__('Phone Number', 'gravityformsraudhahpay'),
                'required' => true
            ]
        );
        array_unshift(
            $billing_info['field_map'],
            [
                'name' => 'email',
                'label' => esc_html__('Email', 'gravityformsraudhahpay'),
                'required' => true
            ]
        );
        array_unshift(
            $billing_info['field_map'],
            [
                'name' => 'lastname',
                'label' => esc_html__('Last Name', 'gravityformsraudhahpay'),
                'required' => true
            ]
        );
        array_unshift(
            $billing_info['field_map'],
            [
                'name' => 'firstname',
                'label' => esc_html__('First Name', 'gravityformsraudhahpay'),
                'required' => true
            ]
        );

        $default_settings = parent::replace_field('billingInformation', $billing_info, $default_settings);

        //hide default display of setup fee, not used by Raudhah Pay
        $default_settings = parent::remove_field('setupFee', $default_settings);
        $default_settings = parent::remove_field('options', $default_settings);

        /**
         * Filter through the feed settings fields for the Paypal feed
         *
         * @param array $default_settings The Default feed settings
         * @param array $form The Form object to filter through
         */
        return apply_filters('gform_raudhahpay_feed_settings_fields', $default_settings, $form);
    }

    public function is_valid_setting($value) {
        return strlen( $value ) > 5;
    }

    /**
     * Process payment to Raudhah Pay
     * */
    public function redirect_url($feed, $submission_data, $form, $entry)
    {
        GFAPI::update_entry_property($entry['id'], 'payment_status', 'Processing');

        $feed_meta = $feed['meta'];
        $b = 'billingInformation_';

        $int_firstname = isset($feed_meta[$b.'firstname']) ? $feed_meta[$b.'firstname'] : '';
        $int_lastname = isset($feed_meta[$b.'lastname']) ? $feed_meta[$b.'lastname'] : '';
        $int_email = isset($feed_meta[$b.'email']) ? $feed_meta[$b.'email'] : '';
        $int_phone_number = isset($feed_meta[$b.'phone_number']) ? $feed_meta[$b.'phone_number'] : '';
        $int_address1 = isset($feed_meta[$b.'address_1']) ? $feed_meta[$b.'address_1'] : '';
        $int_address2 = isset($feed_meta[$b.'address_2']) ? $feed_meta[$b.'address_2'] : '';

        $orderNumber = $this->generateOrderNumber();
        $paymentAmount = isset($submission_data['payment_amount']) ? $submission_data['payment_amount'] : 0;

        $params = [
            'due' => $this->getBillDue(),
            'currency' => isset($entry['currency']) ? $entry['currency'] : self::DEFAULT_CURRENCY,
            'ref1' => $entry['id'],
            'ref2' => $orderNumber,
            'customer' => [
                'first_name' => isset($entry[$int_firstname]) ? $entry[$int_firstname] : '',
                'last_name' => isset($entry[$int_lastname]) ? $entry[$int_lastname] : '',
                'address' => $this->buildAddress($form, $entry, $int_address1, $int_address2),
                'email' => isset($entry[$int_email]) ? $entry[$int_email] : '',
                'mobile' => isset($entry[$int_phone_number]) ? $entry[$int_phone_number] : ''
            ],
            'product' => $this->buildProduct($orderNumber, $paymentAmount),
        ];

        $raudhah = $this->initRaudhahPay(
            $feed_meta['webservice_url'],
            $feed_meta['access_token'],
            $feed_meta['collection_id']
        );

        $this->log_debug(__METHOD__ . "(): Start creating bill");
        $this->log_debug(print_r($params, true));

        list($responseCode, $body) = $raudhah->createBill($params);

        $this->log_debug(__METHOD__ . "(): Create bill response");
        $this->log_debug(print_r($body, true));

        if ($responseCode !== RaudhahPayGravityConnect::DEFAULT_SUCCESS_CODE) {
            /**
             * @todo Need to find a way to display error message and not to store the entry
             * */
            $this->log_debug(__METHOD__ . "(): Failed to connect to Raudhah Pay");
            $this->log_debug(print_r($params, true));

            return '';
        }

        gform_update_meta($entry['id'], 'bill_id', $body['id']);
        gform_update_meta($entry['id'], 'order_number', $orderNumber);

        $this->log_debug('URL: ' . $body['payment_url']);

        return $body['payment_url'];
    }

    private function buildProduct($orderNumber, $totalPrice)
    {
        return [
            [
                'title' => sprintf('Order (%s)', $orderNumber),
                'quantity' => 1, //Fix value to not calculate the price against the qty
                'price' => $totalPrice,
            ]
        ];
    }

    private function generateOrderNumber()
    {
        return self::ORDER_NUMBER_PREFIX . time();
    }

    private function buildAddress($form, $entry, $initAddress1, $initAddress2)
    {
        $address1 = isset($entry[$initAddress1]) ? $entry[$initAddress1] : '';
        $address2 = isset($entry[$initAddress2]) ? $entry[$initAddress2] : '';

        $addressFull = !empty($address2) ? implode(' ', [$address1, $address2]) : $address1;

        if (empty($address1)) {
            /**
             * Only address_1 is required in feed settings
             * */
            $field = GFFormsModel::get_field($form, $initAddress1);
            $addressFull = $field->get_value_export($entry, $initAddress1);
        }

        return $addressFull;
    }

    private function initRaudhahPay($webServiceUrl, $accessToken, $collectionId)
    {
        return new RaudhahPayGravityApi($webServiceUrl, $accessToken, $collectionId);
    }

    private function getBillDue()
    {
        return date('Y-m-d');
    }

    public function getReturnUrl($form_id, $lead_id)
    {
        $pageURL = GFCommon::is_ssl() ? 'https://' : 'http://';

        $server_port = apply_filters('gform_raudhahpay_return_url_port', $_SERVER['SERVER_PORT']);

        if ($server_port != '80') {
            $pageURL .= $_SERVER['SERVER_NAME'] . ':' . $server_port . $_SERVER['REQUEST_URI'];
        } else {
            $pageURL .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
        }

        $ids_query = "ids={$form_id}|{$lead_id}";
        $ids_query .= '&hash=' . wp_hash($ids_query);

        $url = add_query_arg('gf_raudhahpay_return', base64_encode($ids_query), $pageURL);

        $query = 'gf_raudhahpay_return=' . base64_encode($ids_query);
        /**
         * Filters Raudhah Pay return URL, which is the URL that users will be sent to after completing the payment on Raudhah site.
         * Useful when URL isn't created correctly (could happen on some server configurations using PROXY servers).
         *
         * @since 2.4.5
         *
         * @param string  $url  The URL to be filtered.
         * @param int $form_id  The ID of the form being submitted.
         * @param int $entry_id The ID of the entry that was just created.
         * @param string $query The query string portion of the URL.
         */
        return apply_filters('gform_raudhahpay_return_url', $url, $form_id, $lead_id, $query);
    }

    public function delay_post($is_disabled, $form, $entry)
    {
        $feed = $this->get_payment_feed($entry);
        $submission_data = $this->get_submission_data($feed, $form, $entry);

        if (! $feed || empty($submission_data['payment_amount'])) {
            return $is_disabled;
        }

        return ! rgempty('delayPost', $feed['meta']);
    }

    public function delay_notification($is_disabled, $notification, $form, $entry)
    {
        if (rgar($notification, 'event') != 'form_submission') {
            return $is_disabled;
        }

        $feed = $this->get_payment_feed($entry);
        $submission_data = $this->get_submission_data($feed, $form, $entry);

        if (!$feed || empty($submission_data['payment_amount'])) {
            return $is_disabled;
        }

        $selected_notifications = is_array(rgar($feed['meta'], 'selectedNotifications')) ? rgar($feed['meta'], 'selectedNotifications') : array();

        return isset($feed['meta']['delayNotification']) && in_array($notification['id'], $selected_notifications) ? true : $is_disabled;
    }

    public function callback()
    {
        $entryId = rgpost('ref1');
        $this->log_debug(__METHOD__ . '(): Callback initiated, entry ID: ' . $entryId);

        $entry = GFAPI::get_entry($entryId);

        if ($this->validateCallback($entry) == false) {
            return false;
        }

        $feed = $this->get_payment_feed($entry);

        $raudhah = $this->initRaudhahPay(
            $feed['meta']['webservice_url'],
            $feed['meta']['access_token'],
            $feed['meta']['collection_id']
        );

        if (!$responseData = $raudhah->getIpnResponseData()) {
            $this->log_error(__METHOD__ . '(): Failed signature validation.');
            return false;
        }

        try {
            $raudhah->validateSignature($responseData, $feed['meta']['x_signature_key']);

            if (!$feed || !rgar($feed, 'is_active')) {
                $this->log_error(__METHOD__ . "(): Form no longer is configured with Billplz. Form ID: {$entry['form_id']}. Aborting.");
                return false;
            }

            if ($this->shouldRedirect()) {
                $return_url = gform_get_meta($entry['id'], 'return_url');
                if (!empty($feed['meta']['cancel_url']) && !$responseData['paid']) {
                    $return_url = $feed['meta']['cancel_url'];
                }
                header("Location: $return_url");
                exit;
            }

            if (!$this->shouldRedirect() && $responseData['paid']) {
                return array(
                    'id' => $responseData['ref_id'],
                    'transaction_id' => $responseData['bill_no'],
                    'amount' => $responseData['amount'],
                    'entry_id' => $entry['id'],
                    'payment_date' => get_the_date('Y-m-d H:i:s'),
                    'type' => 'complete_payment',
                    'payment_method' => 'Raudhah Pay',
                    'ready_to_fulfill' => !$entry['is_fulfilled'] ? true : false,
                );
            }

            return false;
        } catch (Exception $e) {
            $this->log_error(__METHOD__ . '(): ' . $e->getMessage());
            exit( $e->getMessage() );
        }
    }

    private function validateCallback($entry)
    {
        if (!$this->is_gravityforms_supported()) {
            $this->log_debug(__METHOD__ . '(): Gravity Form is not supported. Aborting.');
            return false;
        }

        if (is_wp_error($entry)) {
            $this->log_debug(__METHOD__ . '(): Entry could not be found. Aborting.');
            return false;
        }

        $this->log_debug(__METHOD__ . '(): Entry has been found => ' . print_r($entry, true));

        if (!$bill_id = gform_get_meta($entry['id'], 'bill_id'))  {
            $this->log_debug(__METHOD__ . '(): Bill ID not found => ' . print_r($entry, true));
            return false;
        }

        if ($entry['status'] == 'spam') {
            $this->log_error(__METHOD__ . '(): Entry is marked as spam. Aborting.');
            return false;
        }

        return true;
    }

    public function post_callback($callback_action, $callback_result)
    {
        if (is_wp_error($callback_action) || ! $callback_action) {
            return false;
        }

        //run the necessary hooks
        $entry = GFAPI::get_entry($callback_action['entry_id']);
        $feed = $this->get_payment_feed($entry);
        $transaction_id = rgar($callback_action, 'transaction_id');
        $amount  = rgar($callback_action, 'amount');

        $this->fulfill_order($entry, $transaction_id, $amount, $feed);

        do_action('gform_raudhahpay_post_payment_status', $feed, $entry, $transaction_id, $amount);

        if (has_filter('gform_raudhahpay_post_payment_status')) {
            $this->log_debug(__METHOD__ . '(): Executing functions hooked to gform_raudhahpay_post_payment_status.');
        }
    }

    public function fulfill_order(&$entry, $transaction_id, $amount, $feed = null)
    {
        if (!$feed) {
            $feed = $this->get_payment_feed($entry);
        }

        $form = GFFormsModel::get_form_meta($entry['form_id']);
        if (rgars($feed, 'meta/delayPost')) {
            $this->log_debug(__METHOD__ . '(): Creating post.');
            $entry['post_id'] = GFFormsModel::create_post($form, $entry);
            $this->log_debug(__METHOD__ . '(): Post created.');
        }

        if (rgars($feed, 'meta/delayNotification')) {
            //sending delayed notifications
            $notifications = $this->get_notifications_to_send($form, $feed);
            GFCommon::send_notifications($notifications, $form, $entry, true, 'form_submission');
        }

        do_action('gform_raudhahpay_fulfillment', $entry, $feed, $transaction_id, $amount);
        if (has_filter('gform_raudhahpay_fulfillment')) {
            $this->log_debug(__METHOD__ . '(): Executing functions hooked to gform_raudhahpay_fulfillment.');
        }
    }

    private function shouldRedirect()
    {
        return $_SERVER['REQUEST_METHOD'] == RaudhahPayGravityConnect::METHOD_GET;
    }

    public function is_callback_valid() {
        if (rgget( 'page' ) != 'gf_raudhahpay_ipn') {
            return false;
        }

        return true;
    }

    public function get_notifications_to_send($form, $feed)
    {
        $notifications_to_send  = array();
        $selected_notifications = rgars($feed, 'meta/selectedNotifications');

        if (is_array($selected_notifications)) {
            // Make sure that the notifications being sent belong to the form submission event, just in case the notification event was changed after the feed was configured.
            foreach ($form['notifications'] as $notification) {
                if (rgar($notification, 'event') != 'form_submission' || ! in_array($notification['id'], $selected_notifications)) {
                    continue;
                }

                $notifications_to_send[] = $notification['id'];
            }
        }

        return $notifications_to_send;
    }
}
