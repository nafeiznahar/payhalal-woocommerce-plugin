# Wordpress WooCommerce Plugin for Payhalal

## Note

SouqaFintech SDN BHD **IS NOT RESPONSIBLE** for any problems that may arise from the use of this extension. Use this at your own risk. For any assistance, please email <mark>salam@payhalal.my</mark>.

## Installation Instructions

*NOTE: You will need to have Woocommerce installed for this to work.*

You can either Download the zip file from [here](https://github.com/SouqaFintech/woocommerce-plugin) or run the following command in `/wp-content/plugins`:

```bash
git clone https://github.com/SouqaFintech/woocommerce-plugin.git
```

After you have activated the plugin and created your Payhalal account, head to the Payhalal Merchant Dashboard and click on Developer tools. Add the following URLs:

- Return URL: https://your-website/shop/?wc-api=payhalalcallback
- Success URL: https://your-website/shop/?wc-api=payhalalcallback
- Notification URL: https://your-website/shop/?wc-api=payhalalcallback
- Cancel URL: https://your-website/shop/?wc-api=payhalalcallback

If you have any troubles with installation or have any questions, please contact salam@payhalal.my
