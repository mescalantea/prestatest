# prestashop-sequra

Repository for SeQura plugins for PrestaShop.

## Run with docker-compose

- Change any parameter in the docker-compose.yml if needed. You might want to change SEQURA_SANDBOX_ENDPOINT or SEQURA_SANDBOX_SCRIPT_BASE_URI so that they point to your development environment.
- Run `composer install` and then `docker compose up`
- In order to test the configuration pages, `cd _dev` and `npm install && npm run dev`

SeQura plugins are automatically installed.

## Prestashop back-office

Default localhost address: `127.0.0.1:8010/<PS_FOLDER_ADMIN>` where `<PS_FOLDER_ADMIN>` is set in `docker-compose.yml` file.

Default admin credentials:

- admin mail: demo@prestashop.com
- admin password: prestashop_demo

