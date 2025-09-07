If you normally use FP-CLI on your web host or via Brew, you're most likely using the Phar executable (`fp-cli.phar`). This Phar executable file is the "built", singular version of FP-CLI. It is compiled from a couple dozen repositories in the FP-CLI GitHub organization.

In order to make code changes to FP-CLI, you'll need to set up this `fp-cli-dev` development environment on your local machine. The setup process will:

1. Clone all relevant packages from the `fp-cli` GitHub organization into the `fp-cli-dev` folder, and
2. Install all Composer dependencies for a complete `fp-cli-bundle` setup, while symlinking all of the previously cloned packages into the Composer `vendor` folder.
3. Symlink all folder in `vendor` into corresponding `vendor` folders in each repository, thus making the centralized functionality based on Composer available in each repository subfolder.

Before you can proceed further, you'll need to make sure you have [Composer](https://getcomposer.org/), PHP, and a functioning MySQL or MariaDB server on your local machine.

Once the prerequisites are met, clone the GitHub repository and run the installation process:

```bash
git clone https://github.com/fp-cli/fp-cli-dev fp-cli-dev
cd fp-cli-dev
composer install
composer prepare-tests
```
