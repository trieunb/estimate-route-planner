## Developer instructions

### Install prerequisites:
  - Apache. You can use Nginx but need to convert `.htaccess` files to nginx syntax.
  - MySQL >= 5.4
  - PHP >= 5.4
  - PHP OAuth extension
  - WordPress 4
  - NodeJS(run Grunt tasks when build a release)
  - Memcached
    - On Ubuntu:
      ```
      sudo apt-get install php5-memcache memcached
      ```

### Development setup
  - PHP
    - Turn on `display_errors` flag.
    - Make sure the `OAuth` and `Memcache` extension is enabled.
  - Source code: go to `wp-content/plugins` folder and then clone project:

    ```
    $ git clone path/to/git estimate-route-planner
    ```
  Or you can create a symlink from plugin source code to the `plugins` directory
  Make sure the root folder is `estimate-route-planner` as the WP convention to make the plugin works.
  - Install NodeJS packages:
     ```
    $ npm install
    ```
  - QuickBooks:
    - Register a QBO account.
    - Create an QB app to get app consumer keys in development mode.
    - Create a sandbox company account.
  - Plugin setup:
    - Open `config/plugin.php` to fill appropriate values. It contains global constants of configurations the plugin:
      - `ERP_DEBUG`: should set to `false` in production mode, always set to `true` in development
      - `QB_SANDBOX_MODE`: defaultly same with `ERP_DEBUG`, but you are feell free to change it.
      - `ERP_TIMEZONE`: do not change it!
    - Login to WP admin area, navigate to any page of the plugin. A form to authorize with QB will appear.
    Enter your own consumer Key/Secret of your QB app.
    - After authorized with QB, you should trigger a manual synchronize in Quicbbook Sync tab.

### Coding Style Guide
  - Indent by 4 spaces for all languages in this project.
  - For PHP: recommend follow PRS-2 style.
  - Each line of code should be no longer than 100 characters.
  - Trailing spaces is not allowed.
  - Always end of file with a new line character.
  - Use Linux LF instead of CRLF
  - Use UTF-8 for source files

### How to release?
  1. Update `changelog.md` with the changes. Newest version is always on top.
  2. Update version number in `estimate-route-planner.php`. It's important to automatically expires cached js and css in browers of users.
  3. Run Grunt tasks to minify JS and templates:
     ```
     $ grunt
     ```
  4. Commit and create pull request.

### TODO
  - Protect gmail password in settings by encrypt before save to QB
