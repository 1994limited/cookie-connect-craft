# The demo

Dog-ear Books is a made-up bookshop for trying Cookie Connect: a video held back until marketing cookies are allowed, an analytics script, a cookie policy page with the cookie declaration, and a Cookie settings link in the footer. It comes with a pretend consent platform, so you can try everything with the Custom platform before setting up a real one.

It's at [github.com/1994limited/cookie-connect-craft-demo](https://github.com/1994limited/cookie-connect-craft-demo). To run it with Herd or Valet, make a MySQL database called `cookie_connect_craft_demo`, then:

```bash
git clone https://github.com/1994limited/cookie-connect-craft-demo.git
cd cookie-connect-craft-demo
composer install
cp .env.example .env
php craft setup/keys
php craft install --username=admin --email=admin@example.com --password=password --site-name="Dog-ear Books" --site-url='$PRIMARY_SITE_URL' --language=en-GB --interactive=0
php craft demo/content
```

Or with DDEV: `ddev start`, `ddev composer install`, then the same commands with `ddev craft`. The password above is for a local copy only. See [its README](https://github.com/1994limited/cookie-connect-craft-demo#readme) for what to try.
