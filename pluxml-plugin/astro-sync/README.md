Astro Sync Plugin (PluXML 5.8.21)

Purpose
- Trigger a Cloudflare Pages (or compatible) build webhook when content is saved.
- Provide an admin screen to configure the webhook URL and secret.
- Optional: auto-trigger on article/page save (enable hooks once verified).

Install
1) Copy the `astro-sync` folder into your PluXML `plugins/` directory:
   - pluxml-root/plugins/astro-sync/
2) Go to PluXML admin → Plugins, enable "Astro Sync" and click "Configure".
3) Fill:
   - Webhook URL: Cloudflare Pages Build Hook
   - Secret (optional): shared token checked by your server-side trigger
   - Auto Trigger on Save: enable to trigger after save
4) Save.

Manual trigger
- From the plugin config page, click "Trigger now" to test.

Automatic trigger (hooks)
- This plugin registers hook placeholders for post-save events. Depending on your exact PluXML setup, you may need to adjust the hook names. Common admin hooks include article and static page save events.

Security
- If using `scripts/trigger-cloudflare.php`, set a strong `SYNC_SECRET` and limit origin if possible.

