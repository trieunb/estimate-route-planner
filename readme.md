## Developer instructions

### Install prerequisites:
  - Apache(Recommended) or Nginx
  - MySQL >= 5.4
  - PHP >= 5.4
  - PHP OAuth extension
  - WordPress 4
  - NodeJS(run Grunt tasks when build a release)

### Development setup
  - Source code: go to `wp-content/plugins` folder and then clone project:

  ```
  $ git clone path/to/git estimate-route-planner
  ```
  Make sure the root folder is `estimate-route-planner` as follow the WP convention to make the plugin works.
  - Install NodeJS packages:

  ```
  $ npm install
  ```
  - QuickBooks:
    - Register a QBO account.
    - Create an QB app to get app consumer keys in development.
    - Create a company in sandbox mode.
  - Plugin setup:
    - Login to WP admin area, then navigate to any page of plugin. A form to authorize with QB will appear.
    Enter your own Consumer Key/Secret of your QB app in above step.
    - After authorized with QB, you can now start to develop.


### Coding Style Guide
  - Indent by 4 spaces for all languages in this project.
  - For PHP: recommend follow PRS-2 style.
  - Each line of code should be no longer than 100 characters.
  - Trailing spaces is not allowed.
  - Always end of file with a new line character.

### How to release?
  1. Update `changelog.md` with the changes. Newest version is always on top.
  2. Update version number in `estimate-route-planner.php`. It's important to automatically expires cached js, css in browers of users.
  3. Compile JS and templates, run Grunt task:

  ```
  $ grunt
  ```
  4. Commit and create pull request.
