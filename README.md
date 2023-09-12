# prestashop-sequra

Repository for SeQura plugins for PrestaShop.

## Run with docker-compose

- Change SQ_DOCKER_PLATFORM=linux/amd64 SQ_DOCKER_PLATFORM=linux/arm64/v8 in the .env file if you are running on an M1 Mac
- Run `docker compose up` it will take some time the first time.

SeQura plugins are automatically installed.

## Prestashop back-office

Default localhost address: `127.0.0.1:8010/<PS_FOLDER_ADMIN>` where `<PS_FOLDER_ADMIN>` is set in `docker-compose.yml` file.

Default admin credentials:

- admin mail: demo@prestashop.com
- admin password: prestashop_demo

