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
  - TOTO: write more ...

### Coding Style Guide
  - Indent by 4 spaces for all languages in this project.
  - You can follow PRS-2 format.
  - Each line of code should be no longer than 100 characters.
  - Remember to remove trailing spaces.

### How to release?
  1. Update `changelog.md` with the changes. New version is always on top.
  2. Update new plugin version in `estimate-route-planner.php`. It's important to automatically expires cached js, css in browers of our users.
  3. Compile JS and templates, run Grunt:

  ```
  $ grunt
  ```
  4. Commit and create PR
