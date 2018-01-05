# Armbreaker

Armbreaker is a tool to scrape and visualize like information from XenForo forums such as Spacebattles.com.

## Requirements
### Server
* PHP 7.1
* Composer
* A SQL database that doctrine/dbal can use

### Client
#### Required
* Python 3
* Flask
* npm

#### Automatically fetched
* d3.js
* babel
* webpack
* js-levenshtein
* popper.js
* d3.slider

## Installation

### Server
1. `composer install`
2. Fill your database with `armbreaker.sql`
3. Profit?

### Client
1. Navigate to `client/`
2. Init submodules, if you haven't already:
```
git submodule init  
git submodule update
```
3. `npm install` to download requirements
4. `npm start` for dev, `npm build` for prod. `npm run-script watch` for an automatically updating dev build. 
5. Run `run_webserver.bat/run_webserver.sh` to start the Flask webserver.
6. Navigate to the proper page for testing.

Page|Content
----|-------
http://localhost:5000/fault | Fault
http://localhost:5000/ringmaker | Ringmaker
http://localhost:5000/api/fic/[FICID] | API endpoint mockup
http://localhost:5000/static/dist/demo_dropdown.html | Dropdown testing.