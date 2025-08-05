# ARO (Aptive Route Optimization) - Laravel

![Continous Integration](https://github.com/aptive-env/aro-service-api/actions/workflows/ci.yml/badge.svg)
![Continous Deployment](https://github.com/aptive-env/aro-service-api/actions/workflows/cd.yml/badge.svg)
![Continous Delivery](https://github.com/aptive-env/aro-service-api/actions/workflows/cd-release.yml/badge.svg)

This Service deals with optimizing the routes that service pros use as they service appointments for customers. It interacts with a dependant Vroom Service to actually perform the route optimizations.

Optimization in this case means to: "change the order of appointments so as to minimize cost and drive time" for a service pro

## Framework and Language

This service uses the Laravel Framework based on the PHP language

Reference: https://laravel.com/docs/10.x/configuration

## Project Setup

1. Ensure that you have docker desktop and docker-compose tools installed on your host machine

    https://docs.docker.com/desktop/
    https://docs.docker.com/compose/

2. Retrieve a personal `COMPOSER_AUTH_TOKEN` that you can use to authenticate to Aptive Composer Package Repositories (You will define this ENV var in the next step in your `.env` file)
   1. Reference: https://aptive.atlassian.net/wiki/x/FYDkWg#1--Obtain-Authentication-Token-to-Package-Repository
3. Create a `.env` file in the root directory of the repository and copy the contents of `.env.example` to it. Fill out the `.env` file with any changes you need for your local environment, adjusting defaults as needed
4. Run `docker-compose build` command in the root directory to build the docker image
5. Run `docker-compose up` command to bring up the environment
6. Run `docker-compose exec aro-php bash` to enter the terminal of the container
7. The local development environment can be accessed at this urls: http://locahost:8080

### Docker Compose Profiles

The queue worker services in docker compose are under the `queue` profile and will not be brought up by default upon executing `docker-compose up`.

To bring up the stack WITH the queue workers you can execute this command `docker-compose --profile queue up` which will bring up the entire stack INCLUDING the queue workers

### Create/Update Database Schema

Project includes Bytebase repository to keep database schema sql files in one place.
That repository included as git submodule, like a nested git repository.
You still have to fetch changes for the submodule just like any other git repository:

```
git submodule update --init --recursive
```

Laravel native migration is not used, instead there is a laravel command that allows you to apply SQL updates for database.

```
php artisan db:apply --database pgsql --file ./db-schema-crm/bytebase/stage.aptive__LATEST.sql --ignore-extension-statements --ignore-existing-schema
```

Wiping database:

```
php artisan db:clean --database pgsql --schemas-to-ignore "public,information_schema" --with-publications --with-event-triggers --with-defined-functions --with-defined-sequences
```

More details about usage of the command: https://github.com/aptive-env/composer-package-laravel-db-apply-command

### Vroom

Vroom is an open source route optimization engine that is used by this service for route optimization. 

Documentation can be found here:
- Vroom API: https://github.com/VROOM-Project/vroom/blob/master/docs/API.md
- Vroom Repository: https://github.com/VROOM-Project/vroom
- Vroom Frontend: https://github.com/VROOM-Project/vroom-frontend

### OSRM

Open Source Routing Machine for Vroom (https://project-osrm.org/).

Whole US map for OSRM is rather big. Be aware that when OSRM is running it loads whole map into RAM what can significantly load OS you are working with.
There is a possibility to download only small part of the map, i.e. one US state or region. Maps for OSRM can be downloaded here: https://download.geofabrik.de/north-america/us.html

Installing map before running OSRM:

1. Select a `pbf` file for US state (e.g. `utah-latest.osm.pbf`) and download it into `.docker/osrm` folder of the project.
2. Update `OSRM_DATABASE_FILENAME` in `.env` with the file name of the downloaded map:
```dotenv
OSRM_DATABASE_FILENAME=utah-latest
```

## Environment Variables

The section describes each of the environment variables. Before you run the project, create a copy of the **.env.example** file under the name **.env**.

*\*Environment Variables which should be considered secret*

| Variable                               | Description                                                                                                                                                       | Default                                                                                         |
|----------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------|-------------------------------------------------------------------------------------------------|
| APP_NAME                               | The name of the application                                                                                                                                       | ARO Service                                                                                     |
| APP_ENV                                | The environment that the application is running in. Examples: local, testing, staging, production                                                                 | local                                                                                           |
| \*APP_KEY                              | The key used to by Laravel to encrypt and decrypt data in the database. Example: base64:bXmX7oQNxhRWcDBZg1m3rxCAJhR0oFqvkUH8Gt9PozI=                              | *null*                                                                                          |
| APP_DEBUG                              | Set to "true" to show helpful debugging information for local development. Should be set to "false" in testing and higher environments                            | true                                                                                            |
| APP_URL                                | The URL of the application. This is used by the framework for a lot of things and should be set correctly. Example: http://192.168.73.1:8080                      | http://localhost                                                                                |
| \*JWT_SECRET                           | JWT Token encryption secret                                                                                                                                       | *null*                                                                                          |
| VROOM_URL                              | The URL used to communicate with the Vroom instance. Example: https://vroom.routing.tst.goaptive.com                                                              | aro-vroom:3000                                                                                  |
| OSRM_DATABASE_FILENAME                 | File name of the map that will be added to OSRM during image build                                                                                                | *null*                                                                                          |
| \*COMPOSER_AUTH_TOKEN                  | The authentication token to access the Aptive repository of composer packages. *Only used during image build*                                                     | *null*                                                                                          |
| PESTROUTES_URL                         | The base URL to use for the pestroutes API requests.                                                                                                              | https://demoawsaptivepest.pestroutes.com/api/                                                   |
| DYNAMO_DB_OFFICE_CREDENTIALS_TABLENAME | The dynamo db table name to use for resolving pestroutes api office credentials.                                                                                  | development-01.testing-office-credentials-api-pestroutes.dynamodb_table                         |
| QUEUE_CONNECTION                       | The connection to use for queues. Set to "sync" to test in local development without queues                                                                       | sqs                                                                                             |
| SQS_PREFIX                             | The URL prefix to the sqs queue name                                                                                                                              | https://sqs.us-east-1.amazonaws.com/600580905024/                                               |
| SQS_ROUTE_OPTIMIZATION_QUEUE           | The name of the SQS queue to use for route optimization jobs.                                                                                                     | development-01-aro-route-optimization-queue                                                     |
| COLLECT_METRICS_QUEUE                  | The name of the SQS queue to use for collecting metrics                                                                                                           | development-01-aro-collect-metrics-queue                                                        |
| SERVICE_STATS_QUEUE                    | The name of the SQS queue to use for gathering service stats                                                                                                      | development-01-aro-service-stats-queue                                                          |
| SEND_NOTIFICATIONS_QUEUE               | The name of the SQS queue to send notifications                                                                                                                   | development-01-aro-service-send-notifications-queue                                             |
| SCHEDULE_APPOINTMENTS_QUEUE            | The name of the SQS queue to use for automated scheduling                                                                                                         | development-01-aro-service-schedule-appointments-queue                                          |
| CACHING_QUEUE                          | The name of the SQS queue to use for pre-populate cache                                                                                                           | development-01-aro-service-caching-queue                                                        |
| DB_CONNECTION                          | The type of database connection the application should use. Example "mysql"                                                                                       | pgsql                                                                                           |
| DB_HOST                                | The host uri for the database to connect to. Example: "my-database.com"                                                                                           | aro-postgres                                                                                    |
| DB_PORT                                | The port to use for database connection. Example: "3306"                                                                                                          | 5432                                                                                            |
| DB_DATABASE                            | The name of the Logical database to connect to. Example: "aro"                                                                                                    | aro                                                                                             |
| DB_USERNAME                            | The username to use for database connection                                                                                                                       | *null*                                                                                          |
| \*DB_PASSWORD                          | The password to use for the database connection                                                                                                                   | *null*                                                                                          |
| INFLUXDB_HOST                          | The full URL of the host for the InfluxDB database. Example: https:\\\mydatabase.influxdb.com                                                                     | aro-influxdb:8086                                                                               |
| INFLUXDB_ORGANIZATION                  | The name of the organization to connect to in the influxDB instance. Example: "My Organization"                                                                   | Application Metrics                                                                             |
| INFLUXDB_BUCKET                        | The name of the influxDB bucket to connect to. Example: my_bucket                                                                                                 | aro_service                                                                                     |
| \*INFLUXDB_TOKEN                       | The token to use to authenticate to the InfluxDB database                                                                                                         | *null*                                                                                          |
| \*CONFIGCAT_SDK_KEY                    | The SDK Key used to authenticate to a specific ConfigCat Feature Flagging environment                                                                             | *null*                                                                                          |
| \*MOTIVE_API_KEY                       | The authorization key for Motive API                                                                                                                              | *null*                                                                                          |
| CACHE_DRIVER                           | The driver to use for caching. Example: "redis"                                                                                                                   | redis                                                                                           |
| REDIS_HOST                             | The host URL for the redis cache                                                                                                                                  | redis-standalone                                                                                |
| \*REDIS_PASSWORD                       | The password used to authenticate to the redis cache. Leave blank if no password is required.                                                                     | *null*                                                                                          |
| REDIS_PORT                             | The port used to connect to the redis cache                                                                                                                       | 6379                                                                                            |
| GOOGLEAPIS_PROJECT_ID                  | The project ID                                                                                                                                                    | *null*                                                                                          |
| GOOGLEAPIS_PRIVATE_KEY_ID              | The private key ID for Google Service Account                                                                                                                     | *null*                                                                                          |
| GOOGLEAPIS_PRIVATE_KEY                 | The private key cert for Google Service Account                                                                                                                   | *null*                                                                                          |
| GOOGLEAPIS_CLIENT_EMAIL                | The client email                                                                                                                                                  | *null*                                                                                          |
| GOOGLEAPIS_CLIENT_ID                   | The client ID                                                                                                                                                     | *null*                                                                                          |
| GOOGLEAPIS_CLIENT_CERT_URL             | The client cert URL                                                                                                                                               | *null*                                                                                          |
| WORKDAY_ISU_USERNAME                   | The Workday Username                                                                                                                                              | ISU_ARO                                                                                         |
| \*WORKDAY_CLIENT_ID                    | The Workday client ID                                                                                                                                             | *null*                                                                                          |
| \*WORKDAY_PRIVATE_KEY_BASE64           | The Workday private key (base64 encoded x.509 certificate private key)                                                                                            | *null*                                                                                          |
| WORKDAY_ACCESS_TOKEN_URL               | The Workday access token URL                                                                                                                                      | https://services1.myworkday.com/ccx/oauth2/aptive/token                                         |
| WORKDAY_HUMAN_RESOURCES_URL            | The Workday Human Resources URL                                                                                                                                   | https://services1.myworkday.com/ccx/service/aptive/Human_Resources/v39.1                        |
| WORKDAY_FINANCIAL_REPORT_URL           | The Workday Financial Report URL                                                                                                                                  | https://services1.myworkday.com/ccx/service/customreport2/aptive/hailey.orton/BI___Gross_Margin |
| NOTIFICATION_SERVICE_SLACK_WEBHOOK_URL | The URL of the Slack app's incoming webhook for sending notifications. A unique URL to which can be sent by a JSON payload with the message text and some options | *null*                                                                                          |
| OPEN_WEATHER_MAP_API_KEY               | The api key for api of getting open weather map data                                                                                                              | *null*                                                                                          |
| OPEN_WEATHER_MAP_API_URL               | The api URL for getting open weather map data                                                                                                                     | *null*                                                                                          |


### AWS Environment Variables

These variables should be defined for local development in order to enable authentication with AWS resources (sqs, etc...).

*Please note that these variables are NOT required to be defined for higher tier environments. Role based or other form of authentication to AWS may be used instead*

- AWS_ACCESS_KEY_ID
- AWS_SECRET_ACCESS_KEY
- AWS_SESSION_TOKEN
- AWS_DEFAULT_REGION

### AWS SQS Visibility Timeout

When working with AWS SQS and timeout for a job set to greater than 30 sec then Visibility Timeout in AWS SQS should be set to the maximum time
that it takes application to process and delete a message from the queue.
Otherwise, message pushed to queue will become visible to other workers what can trigger some errors like: 'Job has been attempted too many times.'
(30 sec is the default value in AWS SQS)

## External Service Dependencies

Here you can find a list of all external services that this service is dependent on. External roughly meaning: "Communication occurs over a network". Examples: MySQL Database, Mongo Database, AWS S3, AWS SQS, Apache Kafka, Sendgrid, Twilio SMS, DocuSign, Google Maps, etc...

| Depends on Service           |
|------------------------------|
| Vroom                        |
| OSRM (Vroom depends on this) |
| Pestroutes                   |
| InfluxDB                     |
| AWS SQS                      |
| ConfigCat (Feature Flagging) |
| Motive                       |
| Redis (or ElastiCache)       |
| GoogleAPIs (Optimization AI) |

## Static code analysis

### Pint
Compliance of the code style with the base rules is controlled by the Laravel pint tool.

https://laravel.com/docs/10.x/pint

All code style violations can be fixed automatically two ways:

By launching the pint tool directly: `vendor/bin/pint`

or by launching the composer script: `composer pint-fix`

### Phpstan
We use phpstan tool to determine bugs on the development stage.

https://phpstan.org/

The tool can be launched to ways:

By launching the tool directly: `vendor/bin/phpstan analyze`

or by launching the composer script: `composer phpstan`

## Documentation

### Confluence

Here you can find documentation for the service as a whole in our confluence pages.

https://aptive.atlassian.net/l/cp/bYfuk91m

### Web API

Here you can find documentation for the exposed Web API of this service

https://apidocs.aptive.tech/

# Additional References

[CONTRIBUTING.md](/CONTRIBUTING.md) - Instructions on Developer Environment setup and how to contribute to this repository

[MAINTAINERS.md](/MAINTAINERS.md) - List of names and email addresses responsible for maintaining this repository
