# Contributing

Instructions on how to contribute to this repository as well as how to setup your environment for local development can be found here.

## Contribution Process

Follow the workflow instructions below to contribute new code to this repository.

**TODO: Update the steps below to inform others how they can contribute**

### Workflow Instructions

1. Setup your local development environment as shown [below](#local-environment-setup)
2. Follow the git branching strategy outlined [below](#git-branching-strategy) to create the appropriate branch
3. Ensure any changes you deliver have automated tests which are passing
   1. From within the container terminal you can run the tests via this command "vendor/bin/phpunit"
4. Push your branch to the remote repo and ensure that the CI pipeline is passing on your branch
5. Submit a Merge Request and add one of the [maintainers](/MAINTAINERS.md) as a Reviewer 
   1. Be sure to be extremely descriptive with your MR and follow this guide: https://aptive.atlassian.net/l/cp/4ng0MBYE
6. Work with the Reviewer on any requested changes

### Git Branching Strategy

Here you will find information to the Git Branching Strategy used for this project

**TODO: Add link and other information on what git branching strategy is used. Example: GitLab Flow, Git Flow, GitHub Flow**

## Local Environment Setup

See below for information on how to setup your local development environment in preparation to contributing

**TODO: Update the steps below to illustrate how to setup the local dev environment**

### Environment Setup Instructions

1. Copy the .env.example file to a new .env file in the same location. Fill out the environment variables listed.
  1. The GITLAB_TOKEN is injected into the composer auth.json file on image build. This allows composer to authenticate to customer package repositories. You can use Personal Access Tokens or Deploy Tokens
  2. See the "auth.json" section here: https://gitlab.com/groups/aptive-environmental/composer-packages/-/wikis/Composer-Packages
2. Ensure you have Docker and Docker Compose tools installed
3. Run `docker-compose build` command in the root directory to build the docker image
4. Run `docker-compose up` command to bring up the environment
5. Run `docker-compose exec [container-name] bash` to enter the terminal of the container

