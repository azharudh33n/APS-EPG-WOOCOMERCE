== woocommerce-aps-epg ==

Plugin name: WooCommerce APS Payment Gateway
Version: 1.0.0


== Installation ==

= Uploading in WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Navigate to the 'Upload' area
3. Select `woocommerce-aps-epg.zip` from your computer
4. Click 'Install Now'
5. Activate the plugin in the Plugin dashboard

= Using File Manager =

1. Download `woocommerce-aps-epg.zip`
2. Extract the `woocommerce-aps-epg.zip` directory to your computer
3. Upload the `woocommerce-aps-epg.zip` directory to the `/wp-content/plugins/` directory
4. Activate the plugin in the Plugin dashboard


== Settings ==
1- Got to 'woocommerce->setting->payment->APS gateway' and click manage
2- In manage we need set up the below fields
	* Title : tilte will showed to customer in checkout from
	* Description : Description will showed to customer in checkout from
	* testmode : checkbox for set if you are work on test or live mode, when the value is checked that meanse you will work in testmode
	* test_url : APS test payment gateway Url use "https://proxy.ait.iq"
	* test_username : EPG test user name provided by APS
	* test_password : EPG test user password provided by APS
	* live_url : APS live payment gateway Url
	* live_username : EPG test user name provided by APS
	* live_password : EPG test user password provided by APS

3- Save changes

== Notes ==
1- This plugin works for USD & IQD only.